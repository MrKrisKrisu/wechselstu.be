<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\TicketType;
use App\Events\TicketCreated;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function store(Request $request, string $token): JsonResponse
    {
        $station = Station::where('token', $token)->firstOrFail();

        // Ticket type comes from domain middleware, falls back to request body
        $typeValue = $request->attributes->get('ticket_type')
            ?? $request->input('type');

        if (! $typeValue || ! TicketType::tryFrom($typeValue)) {
            return response()->json(['message' => 'Invalid or missing ticket type.'], 422);
        }

        $type = TicketType::from($typeValue);

        $rules = [
            'message' => $type === TicketType::Other
                ? ['required', 'string', 'max:1000']
                : ['nullable', 'string', 'max:1000'],
        ];

        if ($type === TicketType::ChangeRequest) {
            $rules['denominations'] = ['nullable', 'array'];
            $rules['denominations.*'] = ['integer', 'min:0', 'max:99'];
        }

        $validated = $request->validate($rules);

        $ticket = $this->ticketService->createTicket($station, $type, $validated);

        return response()->json([
            'ticket' => TicketCreated::serializeTicket($ticket),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $ticket = Ticket::with(['station', 'denominations', 'assignedUser'])->findOrFail($id);

        return response()->json([
            'ticket' => TicketCreated::serializeTicket($ticket),
        ]);
    }
}
