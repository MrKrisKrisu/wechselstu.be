<?php

namespace App\Services;

use App\Exceptions\KassenbuchApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class KassenbuchApiService
{
    private const PAGE_SIZE = 500;

    public string $entityName;

    private string $baseUrl;

    private string $username;

    private string $password;

    private int $account;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.kassenbuch.base_url', ''), '/');
        $this->username = config('services.kassenbuch.username', '');
        $this->password = config('services.kassenbuch.password', '');
        $this->account = config('services.kassenbuch.account', 0);
        $this->entityName = config('services.kassenbuch.entity_name', 'Wechselstube');
    }

    public function getAllEntries(?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        $all = collect();
        $offset = 0;

        do {
            $payload = [
                'account' => $this->account,
                'limit' => self::PAGE_SIZE,
                'offset' => $offset,
            ];

            if ($dateFrom !== null) {
                $payload['date_from'] = $dateFrom;
            }

            if ($dateTo !== null) {
                $payload['date_to'] = $dateTo;
            }

            $response = $this->client()->post('/transactions/get', $payload);
            $this->assertSuccess($response);

            $page = collect($response->json('data', []));
            $all = $all->merge($page);
            $offset += self::PAGE_SIZE;
        } while ($page->count() === self::PAGE_SIZE);

        return $all;
    }

    private function client(): PendingRequest
    {
        return Http::timeout(30)
            ->withBasicAuth($this->username, $this->password)
            ->withUserAgent('wechselstu.be/1.0 (https://github.com/mrkriskrisu/wechselstu.be)')
            ->baseUrl($this->baseUrl);
    }

    private function assertSuccess(Response $response): void
    {
        if (! $response->successful()) {
            throw new KassenbuchApiException(
                $response->json('message', "Kassenbuch API error [{$response->status()}]"),
                (int) $response->json('error_code', 0),
            );
        }
    }

    public function addEntry(
        string $toFrom,
        float $amount,
        string $bookingDate,
        ?string $bookingText = null,
        ?string $purpose = null,
    ): string {
        $payload = [
            'account' => $this->account,
            'to_from' => $toFrom,
            'amount' => $amount,
            'booking_date' => $bookingDate,
        ];

        if ($bookingText !== null) {
            $payload['booking_text'] = $bookingText;
        }

        if ($purpose !== null) {
            $payload['purpose'] = $purpose;
        }

        $response = $this->client()->post('/transactions/add', $payload);
        $this->assertSuccess($response);

        return (string) $response->json('id_by_customer');
    }
}
