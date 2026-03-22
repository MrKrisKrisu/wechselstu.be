<?php

namespace App\Services;

use App\Enums\TicketType;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MatrixService
{
    private string $homeserver;

    private string $accessToken;

    private string $roomId;

    public function __construct()
    {
        $this->homeserver = config('services.matrix.homeserver_url', '');
        $this->accessToken = config('services.matrix.access_token', '');
        $this->roomId = config('services.matrix.room_id', '');
    }

    public function notify(Ticket $ticket): void
    {
        if (empty($this->homeserver) || empty($this->accessToken) || empty($this->roomId)) {
            Log::warning('Matrix not configured, skipping notification', ['ticket_id' => $ticket->id]);

            return;
        }

        $message = $this->buildMessage($ticket);
        $txnId = 'ticket_'.$ticket->id.'_'.time();
        $roomId = urlencode($this->roomId);
        $url = "{$this->homeserver}/_matrix/client/v3/rooms/{$roomId}/send/m.room.message/{$txnId}";

        $response = Http::timeout(10)
            ->withToken($this->accessToken)
            ->put($url, [
                'msgtype' => 'm.text',
                'body' => $message,
                'format' => 'org.matrix.custom.html',
                'formatted_body' => nl2br(e($message)),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Matrix API returned HTTP {$response->status()}: ".$response->body());
        }
    }

    private function buildMessage(Ticket $ticket): string
    {
        $emoji = match ($ticket->type) {
            TicketType::CashFull => '💰',
            TicketType::ChangeRequest => '🪙',
            TicketType::Other => '📝',
        };

        $time = $ticket->created_at->format('H:i');
        $lines = [
            "{$emoji} *{$ticket->type->label()}* — {$ticket->station->name}",
            "📍 {$ticket->station->location} | ⏰ {$time}",
        ];

        if ($ticket->type === TicketType::ChangeRequest && $ticket->denominations->isNotEmpty()) {
            $parts = $ticket->denominations->map(function ($d) {
                $amount = number_format($d->denomination_cents / 100, 2, ',', '');

                return "{$d->quantity}x {$amount} €";
            });
            $lines[] = '🪙 '.implode(', ', $parts->toArray());
        }

        if (! empty($ticket->message)) {
            $lines[] = '💬 '.$ticket->message;
        }

        return implode("\n", $lines);
    }
}
