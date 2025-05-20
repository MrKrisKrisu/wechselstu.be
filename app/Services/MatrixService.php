<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MatrixService {
    public static function sendMessage(string $message): void {
        $homeserverUrl = rtrim((string)config('services.matrix.homeserver_url'), '/');
        $roomId        = config('services.matrix.room_id');
        $accessToken   = config('services.matrix.access_token');

        if(empty($homeserverUrl) || empty($roomId) || empty($accessToken)) {
            Log::warning('Matrix config missing. Message not sent.', [
                'homeserver' => $homeserverUrl,
                'room_id'    => $roomId,
                'token_set'  => !empty($accessToken),
            ]);
            return;
        }

        try {
            $txnId         = Str::uuid(); //TODO: safe txnId and delete message after resolved?
            $encodedRoomId = urlencode($roomId);
            $url           = "{$homeserverUrl}/_matrix/client/v3/rooms/{$encodedRoomId}/send/m.room.message/{$txnId}";

            $response = Http::withToken($accessToken)
                            ->timeout(5)
                            ->put($url, [
                                'msgtype' => 'm.text',
                                'body'    => $message,
                            ]);

            if($response->failed()) {
                Log::error('Matrix API request failed', [
                    'url'      => $url,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'response' => $response->json(),
                ]);
            }
        } catch(Throwable $e) {
            Log::error('Matrix message sending threw an exception.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
