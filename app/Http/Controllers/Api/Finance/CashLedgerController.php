<?php

namespace App\Http\Controllers\Api\Finance;

use App\Enums\CashEntryType;
use App\Enums\TicketType;
use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Models\CashEntry;
use App\Models\Station;
use App\Models\Ticket;
use App\Repositories\CashClosingRepository;
use App\Repositories\CashEntryRepository;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Repositories\StationRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class CashLedgerController extends Controller
{
    public function __construct(
        private readonly CashEntryRepository $cashEntries,
        private readonly CashClosingRepository $cashClosings,
        private readonly StationRepository $stations,
        private readonly TicketRepositoryInterface $tickets,
    ) {}

    public function index(): JsonResponse
    {
        $entries = $this->cashEntries->allWithRelations();
        $closings = $this->cashClosings->allWithCreator();
        $stations = $this->stations->allForLedger();

        $lastClosing = $closings->last();
        $lockedUntil = $lastClosing ? Carbon::instance($lastClosing->locked_until) : null;

        $balance = $entries->sum('amount_cents');

        $stationBalances = $stations->map(function (Station $station) use ($entries) {
            $balance = $entries
                ->where('counterpart_station_id', $station->id)
                ->whereNull('reversed_at')
                ->sum(fn ($e) => -$e->amount_cents);

            return [
                'id' => $station->id,
                'name' => $station->name,
                'location' => $station->location,
                'balance_cents' => $balance,
            ];
        })->values()->all();

        $suggestions = $this->buildSuggestions($lockedUntil);

        return response()->json([
            'entries' => $entries->map(fn ($e) => $this->serializeEntry($e))->values(),
            'closings' => $closings->map(fn ($c) => $this->serializeClosing($c))->values(),
            'stations' => $stationBalances,
            'balance_cents' => $balance,
            'locked_until' => $lockedUntil?->toIso8601String(),
            'suggestions' => $suggestions,
        ]);
    }

    public function storeEntry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', array_column(CashEntryType::cases(), 'value'))],
            'amount_cents' => ['required', 'integer', 'not_in:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'ticket_id' => ['nullable', 'uuid', 'exists:tickets,id'],
            'counterpart_station_id' => ['nullable', 'uuid', 'exists:stations,id'],
        ]);

        $cashEntry = $this->cashEntries->create([
            'type' => $validated['type'],
            'amount_cents' => $validated['amount_cents'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()->id,
            'ticket_id' => $validated['ticket_id'] ?? null,
            'counterpart_station_id' => $validated['counterpart_station_id'] ?? null,
        ]);

        return response()->json(['entry' => $this->serializeEntry($cashEntry)], 201);
    }

    public function storeReversal(Request $request, CashEntry $entry): JsonResponse
    {
        if ($entry->isReversed()) {
            return response()->json(['message' => 'Buchung wurde bereits storniert.'], 422);
        }

        $lastClosing = $this->cashClosings->latest();

        if ($lastClosing && $entry->created_at <= $lastClosing->locked_until) {
            return response()->json(['message' => 'Buchungen vor dem letzten Abschluss können nicht storniert werden.'], 422);
        }

        $reversal = $this->cashEntries->createReversal($entry, $request->user()->id);

        return response()->json(['entry' => $this->serializeEntry($reversal)], 201);
    }

    public function storeClosing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'closing_date' => ['required', 'date'],
            'locked_until' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
        ]);

        $lockedUntil = Carbon::parse($validated['locked_until']);

        $closing = $this->cashClosings->create([
            'label' => $validated['label'],
            'closing_date' => $validated['closing_date'],
            'locked_until' => $lockedUntil,
            'balance_cents' => $this->cashEntries->balanceUpTo($lockedUntil),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['closing' => $this->serializeClosing($closing)], 201);
    }

    public function exportCsv(): Response
    {
        $entries = $this->cashEntries->allWithRelations();

        $rows = [];
        $rows[] = implode(';', ['Datum', 'Typ', 'Gegenkasse', 'Betrag (€)', 'Saldo (€)', 'Beschreibung', 'Erfasst von', 'Storniert am']);

        $running = 0;

        foreach ($entries as $entry) {
            $running += $entry->amount_cents;
            $rows[] = implode(';', [
                $entry->created_at->format('d.m.Y H:i:s'),
                $entry->type->label(),
                $entry->counterpartStation?->name ?? '',
                number_format($entry->amount_cents / 100, 2, ',', '.'),
                number_format($running / 100, 2, ',', '.'),
                '"'.str_replace('"', '""', $entry->description ?? '').'"',
                $entry->creator->name,
                $entry->reversed_at?->format('d.m.Y H:i:s') ?? '',
            ]);
        }

        return response(implode("\n", $rows), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="kassenbuch-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function exportPdf(): Response
    {
        $entries = $this->cashEntries->allWithRelations();
        $closings = $this->cashClosings->allWithCreator();

        $pdf = Pdf::loadView('pdf.kassenbuch', [
            'entries' => $entries,
            'closings' => $closings,
            'balance_cents' => $entries->sum('amount_cents'),
            'generated_at' => now(),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="kassenbuch-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    private function buildSuggestions(?Carbon $lockedUntil): array
    {
        $linkedIds = $this->cashEntries->linkedTicketIds();
        $suggestions = $this->tickets->unlinkedDoneSuggestions($linkedIds, $lockedUntil);

        return $suggestions->map(function (Ticket $ticket) {
            $amountCents = null;
            $suggestedType = null;

            if ($ticket->type === TicketType::ChangeRequest) {
                $suggestedType = CashEntryType::TransferOut->value;
                $amountCents = -$ticket->denominations->sum(fn ($d) => $d->denomination_cents * $d->quantity);
            } elseif ($ticket->type === TicketType::CashFull) {
                $suggestedType = CashEntryType::TransferIn->value;
            }

            return [
                'ticket_id' => $ticket->id,
                'ticket_type' => $ticket->type->value,
                'station_id' => $ticket->station->id,
                'station_name' => $ticket->station->name,
                'done_at' => $ticket->done_at?->toIso8601String(),
                'suggested_type' => $suggestedType,
                'suggested_amount_cents' => $amountCents,
            ];
        })->values()->all();
    }

    private function serializeEntry(CashEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'entry_number' => $entry->entry_number,
            'type' => $entry->type->value,
            'type_label' => $entry->type->label(),
            'amount_cents' => $entry->amount_cents,
            'description' => $entry->description,
            'created_by' => $entry->creator->name,
            'created_at' => $entry->created_at->toIso8601String(),
            'ticket_id' => $entry->ticket_id,
            'ticket_done_at' => $entry->ticket?->done_at?->toIso8601String(),
            'counterpart_station_id' => $entry->counterpart_station_id,
            'counterpart_station_name' => $entry->counterpartStation?->name,
            'reversed_at' => $entry->reversed_at?->toIso8601String(),
            'reversed_by_entry_id' => $entry->reversed_by_entry_id,
        ];
    }

    private function serializeClosing(CashClosing $closing): array
    {
        return [
            'id' => $closing->id,
            'label' => $closing->label,
            'closing_date' => $closing->closing_date->toDateString(),
            'locked_until' => $closing->locked_until->toIso8601String(),
            'balance_cents' => $closing->balance_cents,
            'created_by' => $closing->creator->name,
            'created_at' => $closing->created_at->toIso8601String(),
        ];
    }
}
