<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Ticket $ticket) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('finance'),
            new Channel('monitor'),
        ];

        if ($this->ticket->station_id !== null) {
            $channels[] = new Channel('station.'.$this->ticket->station_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'ticket.created';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket' => $this->serializeTicket($this->ticket),
        ];
    }

    public static function serializeTicket(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'type' => $ticket->type->value,
            'type_label' => $ticket->type->label(),
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label(),
            'message' => $ticket->message,
            'station' => $ticket->station ? [
                'id' => $ticket->station->id,
                'name' => $ticket->station->name,
                'location' => $ticket->station->location,
                'token' => $ticket->station->token,
                'printer_ip' => $ticket->station->printer_ip,
            ] : null,
            'denominations' => $ticket->denominations->map(fn ($d) => [
                'id' => $d->id,
                'denomination_cents' => $d->denomination_cents,
                'quantity' => $d->quantity,
            ])->values()->toArray(),
            'assigned_to' => $ticket->assigned_to,
            'assigned_user' => $ticket->assignedUser ? [
                'id' => $ticket->assignedUser->id,
                'name' => $ticket->assignedUser->name,
                'profile_url' => $ticket->assignedUser->member_token
                    ? 'https://'.config('domains.member').'/member/'.$ticket->assignedUser->member_token
                    : null,
                'avatar_url' => $ticket->assignedUser->avatar_path
                    ? '/api/members/'.$ticket->assignedUser->member_token.'/avatar'
                    : null,
            ] : null,
            'accepted_at' => $ticket->accepted_at?->toIso8601String(),
            'done_at' => $ticket->done_at?->toIso8601String(),
            'created_at' => $ticket->created_at->toIso8601String(),
        ];
    }
}
