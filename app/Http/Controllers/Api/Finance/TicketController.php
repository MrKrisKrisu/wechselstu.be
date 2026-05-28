<?php

namespace App\Http\Controllers\Api\Finance;

use App\Enums\TicketType;
use App\Events\TicketCreated;
use App\Http\Controllers\Controller;
use App\Jobs\PrintTicketJob;
use App\Models\Ticket;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Repositories\StationRepository;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly TicketService $ticketService,
        private readonly StationRepository $stations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'status', 'station_id']);
        $tickets = $this->tickets->allForFinance($filters);

        return response()->json([
            'tickets' => $tickets->map(fn ($t) => TicketCreated::serializeTicket($t))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'station_id' => ['nullable', 'exists:stations,id'],
            'type' => ['required', 'in:cash_full,change_request,other'],
            'message' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $station = isset($validated['station_id']) ? $this->stations->findById($validated['station_id']) : null;
        $type = TicketType::from($validated['type']);

        $ticket = $this->ticketService->createTicket($station, $type, $validated);

        if ($request->boolean('accept', true)) {
            $ticket = $this->ticketService->acceptTicket($ticket, $request->user());
        }

        return response()->json(['ticket' => TicketCreated::serializeTicket($ticket)], 201);
    }

    public function accept(Request $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->status->value !== 'open') {
            return response()->json(['message' => 'Ticket is not open.'], 422);
        }

        $updated = $this->ticketService->acceptTicket($ticket, $request->user());

        return response()->json(['ticket' => TicketCreated::serializeTicket($updated)]);
    }

    public function complete(Ticket $ticket): JsonResponse
    {
        if ($ticket->status->value !== 'accepted') {
            return response()->json(['message' => 'Ticket is not in accepted state.'], 422);
        }

        $updated = $this->ticketService->completeTicket($ticket);

        return response()->json(['ticket' => TicketCreated::serializeTicket($updated)]);
    }

    public function print(Request $request, Ticket $ticket): JsonResponse
    {
        $request->validate([
            'printer' => ['required', 'in:station,office'],
        ]);

        if ($request->input('printer') === 'station') {
            if (! $ticket->station) {
                return response()->json(['message' => 'Diesem Ticket ist keine Kasse zugewiesen.'], 422);
            }

            $ip = $ticket->station->printer_ip;

            if (empty($ip)) {
                return response()->json(['message' => 'Dieser Kasse ist kein Drucker zugewiesen.'], 422);
            }

        } else {
            $ip = config('printer.office_ip');

            if (empty($ip)) {
                return response()->json(['message' => 'Kein Bürodrucker konfiguriert (OFFICE_PRINTER_IP).'], 422);
            }

        }
        PrintTicketJob::dispatch($ticket, $ip, $request->user()->name);

        return response()->json(['message' => 'Druckauftrag gesendet.']);
    }
}
