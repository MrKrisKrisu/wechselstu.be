<?php

namespace App\Services;

use App\Enums\TicketType;
use App\Events\TicketCreated;
use App\Events\TicketStatusUpdated;
use App\Models\Station;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Interfaces\TicketRepositoryInterface;

class TicketService
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
    ) {}

    public function createTicket(Station $station, TicketType $type, array $data): Ticket
    {
        $ticket = $this->tickets->create([
            'station_id' => $station->id,
            'type' => $type,
            'status' => 'open',
            'message' => $data['message'] ?? null,
        ]);

        if ($type === TicketType::ChangeRequest && ! empty($data['denominations'])) {
            foreach ($data['denominations'] as $cents => $quantity) {
                if ($quantity > 0) {
                    $ticket->denominations()->create([
                        'denomination_cents' => (int) $cents,
                        'quantity' => (int) $quantity,
                    ]);
                }
            }
        }

        $ticket->load(['station', 'denominations', 'assignedUser']);

        event(new TicketCreated($ticket));

        return $ticket;
    }

    public function acceptTicket(Ticket $ticket, User $user): Ticket
    {
        $updated = $this->tickets->accept($ticket, $user);
        event(new TicketStatusUpdated($updated));

        return $updated;
    }

    public function completeTicket(Ticket $ticket): Ticket
    {
        $updated = $this->tickets->complete($ticket);
        event(new TicketStatusUpdated($updated));

        return $updated;
    }
}
