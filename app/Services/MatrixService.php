<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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

    public function notify(Ticket $ticket): ?string
    {
        if (! $this->isConfigured()) {
            Log::warning('Matrix not configured, skipping notification', ['ticket_id' => $ticket->id]);

            return null;
        }

        $content = $this->buildContent($ticket);
        $txnId = 'ticket_'.$ticket->id.'_'.time();

        return $this->sendEvent($txnId, $content);
    }

    private function isConfigured(): bool
    {
        return ! empty($this->homeserver) && ! empty($this->accessToken) && ! empty($this->roomId);
    }

    private function buildContent(Ticket $ticket): array
    {
        $done = $ticket->status === TicketStatus::Done;
        $time = $ticket->created_at->format('H:i');

        $statusEmoji = match ($ticket->status) {
            TicketStatus::Open => '🚨',
            TicketStatus::Accepted => '⏳',
            TicketStatus::Done => '✅',
        };

        $lines = [
            "{$statusEmoji} [{$ticket->type->label()}] {$ticket->station->name}",
            "Standort: {$ticket->station->location} | Zeit: {$time}",
        ];

        if ($ticket->type === TicketType::ChangeRequest && $ticket->denominations->isNotEmpty()) {
            $parts = $ticket->denominations->map(function ($d) {
                $amount = number_format($d->denomination_cents / 100, 2, ',', '');

                return "{$d->quantity}x {$amount} €";
            });
            $lines[] = 'Stueckelung: '.implode(', ', $parts->toArray());
        }

        if (! empty($ticket->message)) {
            $lines[] = 'Nachricht: '.$ticket->message;
        }

        $statusLine = 'Status: '.$ticket->status->label();
        if ($ticket->assignedUser) {
            $statusLine .= ' von '.$ticket->assignedUser->name;
        }
        $lines[] = $statusLine;

        $plainBody = implode("\n", $lines);
        $htmlLines = array_map(fn ($l) => e($l), $lines);
        $htmlBody = implode('<br>', $htmlLines);

        if ($done) {
            $plainBody = '[ERLEDIGT] '.$plainBody;
            $htmlBody = '<del>'.$htmlBody.'</del>';
        }

        return [
            'msgtype' => 'm.text',
            'body' => $plainBody,
            'format' => 'org.matrix.custom.html',
            'formatted_body' => $htmlBody,
        ];
    }

    private function sendEvent(string $txnId, array $content): string
    {
        $roomId = urlencode($this->roomId);
        $url = "{$this->homeserver}/_matrix/client/v3/rooms/{$roomId}/send/m.room.message/{$txnId}";

        $response = Http::timeout(10)
            ->withToken($this->accessToken)
            ->put($url, $content);

        if (! $response->successful()) {
            throw new RuntimeException("Matrix API returned HTTP {$response->status()}: ".$response->body());
        }

        return $response->json('event_id');
    }

    public function update(Ticket $ticket): void
    {
        if (! $this->isConfigured() || empty($ticket->matrix_event_id)) {
            return;
        }

        $content = $this->buildContent($ticket);
        $txnId = 'ticket_update_'.$ticket->id.'_'.time();

        $editContent = [
            'msgtype' => 'm.text',
            'body' => '* '.$content['body'],
            'format' => 'org.matrix.custom.html',
            'formatted_body' => $content['formatted_body'],
            'm.new_content' => $content,
            'm.relates_to' => [
                'rel_type' => 'm.replace',
                'event_id' => $ticket->matrix_event_id,
            ],
        ];

        $this->sendEvent($txnId, $editContent);
    }
}
