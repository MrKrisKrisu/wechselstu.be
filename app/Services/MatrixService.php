<?php declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MatrixService {
    private ?string $homeserverUrl;
    private ?string $roomId;
    private ?string $accessToken;

    public function __construct() {
        $this->homeserverUrl = rtrim((string)config('services.matrix.homeserver_url'), '/');
        $this->roomId        = config('services.matrix.room_id');
        $this->accessToken   = config('services.matrix.access_token');

        if(!$this->isConfigured()) {
            Log::warning('Matrix config missing.', [
                'homeserver' => $this->homeserverUrl,
                'room_id'    => $this->roomId,
                'token_set'  => !empty($this->accessToken),
            ]);
        }
    }

    private function isConfigured(): bool {
        return !empty($this->homeserverUrl) && !empty($this->roomId) && !empty($this->accessToken);
    }

    public function whoAmI(): string|bool {
        if(!$this->isConfigured()) return false;

        $url      = "{$this->homeserverUrl}/_matrix/client/v3/account/whoami";
        $response = $this->sendRequest($url, [], 'GET');

        return $response->json('user_id');
    }

    private function sendRequest(string $url, array $payload, string $method = 'PUT'): ?Response {
        try {
            $response = Http::withToken($this->accessToken)
                            ->timeout(5)
                            ->withHeaders(['Content-Type' => 'application/json'])
                            ->send($method, $url, [
                                'json' => $payload,
                            ]);

            if($response->failed()) {
                Log::error('Matrix API request failed', [
                    'url'      => $url,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'response' => $response->json(),
                ]);
                return null;
            }

            return $response;
        } catch(Throwable $e) {
            Log::error('Matrix request threw an exception.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function sendNewMessage(string $message): string|false {
        if(!$this->isConfigured()) return false;

        try {
            $url     = $this->buildUrl('m.room.message');
            $payload = [
                'msgtype' => 'm.notice',
                'body'    => $message,
            ];

            $response = $this->sendRequest($url, $payload);
            if(!$response) return false;

            return $response->json('event_id');
        } catch(Exception $e) {
            Log::error('Matrix message request threw an exception.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    private function buildUrl(string $type): string {
        $txnId         = Str::uuid();
        $encodedRoomId = urlencode($this->roomId);
        return "{$this->homeserverUrl}/_matrix/client/v3/rooms/{$encodedRoomId}/send/{$type}/{$txnId}";
    }

    public function setEmojiReactionToMessage(string $eventId, string $emoji): mixed {
        if(!$this->isConfigured()) return false;

        try {
            $url     = $this->buildUrl('m.reaction');
            $payload = [
                'm.relates_to' => [
                    'rel_type' => 'm.annotation',
                    'event_id' => $eventId,
                    'key'      => $emoji,
                ],
            ];

            $response = $this->sendRequest($url, $payload);
            return $response->json('event_id');
        } catch(Throwable $e) {
            Log::error('Matrix reaction request threw an exception.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function updateMessage(string $eventId, string $newMessage): bool {
        if(!$this->isConfigured()) return false;

        $url     = $this->buildUrl('m.room.message');
        $payload = [
            'msgtype'       => 'm.text',
            'body'          => "* {$newMessage}",
            'm.new_content' => [
                'msgtype'        => 'm.notice',
                'formatted_body' => $newMessage,
                'body'           => strip_tags($newMessage),
                'format'         => 'org.matrix.custom.html',
            ],
            'm.relates_to'  => [
                'rel_type' => 'm.replace',
                'event_id' => $eventId,
            ],
        ];

        return (bool)$this->sendRequest($url, $payload);
    }

    public function getHomeserverUrl(): string {
        return $this->homeserverUrl;
    }

    public function sendSyncRequest(string $url): ?Response {
        try {
            $response = Http::withToken($this->accessToken)
                            ->timeout(35)
                            ->get($url);

            if($response->failed()) {
                Log::warning('Matrix sync failed', [
                    'url'    => $url,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response;
        } catch(Throwable $e) {
            Log::error('Matrix sync threw an exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
