<?php

namespace App\Http\Controllers\Api\Finance;

use App\Events\TicketCreated;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Repositories\CashEntryRepository;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly TicketService $ticketService,
        private readonly CashEntryRepository $cashEntries,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'status', 'station_id']);
        $tickets = $this->tickets->allForFinance($filters);

        $linkedTicketIds = $this->cashEntries->linkedTicketIds()->flip();

        return response()->json([
            'tickets' => $tickets->map(function ($t) use ($linkedTicketIds) {
                return array_merge(
                    TicketCreated::serializeTicket($t),
                    ['has_cash_entry' => isset($linkedTicketIds[$t->id])],
                );
            })->values(),
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
}
