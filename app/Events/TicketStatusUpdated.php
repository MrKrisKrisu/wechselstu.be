<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Ticket $ticket) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('finance'),
            new Channel('station.'.$this->ticket->station_id),
            new Channel('monitor'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket' => TicketCreated::serializeTicket($this->ticket),
        ];
    }
}
