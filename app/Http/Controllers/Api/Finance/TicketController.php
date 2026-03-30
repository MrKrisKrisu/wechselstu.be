<?php

namespace App\Http\Controllers\Api\Finance;

use App\Events\TicketCreated;
use App\Http\Controllers\Controller;
use App\Jobs\PrintTicketJob;
use App\Models\Ticket;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly TicketService $ticketService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'status', 'station_id']);
        $tickets = $this->tickets->allForFinance($filters);

        return response()->json([
            'tickets' => $tickets->map(fn ($t) => TicketCreated::serializeTicket($t))->values(),
        ]);
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
