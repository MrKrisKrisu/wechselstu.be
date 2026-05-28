<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PretixApiService
{
    private string $baseUrl;

    private string $organizer;

    private string $token;

    /** @var int[] */
    private array $deviceIds;

    /** @var int[] */
    private array $cashierIds;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.pretix.base_url', ''), '/');
        $this->organizer = config('services.pretix.organizer', '');
        $this->token = config('services.pretix.token', '');
        $this->deviceIds = config('services.pretix.device_ids', []);
        $this->cashierIds = config('services.pretix.cashier_ids', []);
    }

    public function fetchPosReceiptLinesCsv(): string
    {
        $payload = [
            '_format' => 'default',
            'all_events' => 'on',
            'date_range' => 'year_to_date',
        ];

        if (! empty($this->deviceIds)) {
            $payload['devices'] = $this->deviceIds;
        }

        if (! empty($this->cashierIds)) {
            $payload['cashiers'] = $this->cashierIds;
        }

        $downloadUrl = $this->triggerExport('pos_receiptlines', $payload);

        return $this->pollDownload($downloadUrl);
    }

    private function triggerExport(string $identifier, array $params): string
    {
        $url = "{$this->baseUrl}/api/v1/organizers/{$this->organizer}/exporters/{$identifier}/run/";

        $response = $this->client()->post($url, $params);

        if ($response->status() !== 202) {
            throw new RuntimeException("Pretix export trigger failed [{$response->status()}]: ".$response->body());
        }

        $downloadUrl = $response->json('download');

        if (empty($downloadUrl)) {
            throw new RuntimeException('Pretix response missing download URL: '.$response->body());
        }

        return $downloadUrl;
    }

    private function pollDownload(string $url, int $maxAttempts = 20, int $intervalSeconds = 3): string
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = $this->client()->get($url);

            if ($response->status() === 200) {
                return $response->body();
            }

            if ($response->status() === 409) {
                sleep($intervalSeconds);

                continue;
            }

            throw new RuntimeException("Pretix download failed [{$response->status()}]: ".$response->body());
        }

        throw new RuntimeException("Pretix export did not complete after {$maxAttempts} polling attempts.");
    }

    private function client(): PendingRequest
    {
        return Http::timeout(30)
            ->withToken($this->token, 'Token')
            ->withUserAgent('wechselstu.be/1.0 (https://github.com/mrkriskrisu/wechselstu.be)');
    }
}
