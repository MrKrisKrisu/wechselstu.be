<?php

namespace App\Http\Controllers\Api\Finance;

use App\Enums\BookingDirection;
use App\Exceptions\KassenbuchApiException;
use App\Http\Controllers\Controller;
use App\Services\HauptkasseService;
use App\Services\KassenbuchApiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HauptkasseController extends Controller
{
    public function __construct(
        private readonly HauptkasseService $hauptkasse,
        private readonly KassenbuchApiService $kassenbuch,
    ) {}

    public function pretixBookings(): JsonResponse
    {
        $bookings = $this->hauptkasse->preparePretixBookings()
            ->sortByDesc(fn ($b) => $b->date->timestamp)
            ->values();

        $serialized = $bookings->map(fn ($b) => [
            'direction' => $b->direction->name,
            'amount_cents' => $b->amountCents,
            'amount_eur' => $b->amountEur(),
            'description' => $b->description,
            'to_from' => $b->toFrom,
            'booking_text' => $b->bookingText,
            'metadata' => $b->metadata,
            'date' => $b->date->format('d.m.Y H:i'),
        ])->values();

        $incomeCents = $bookings
            ->filter(fn ($b) => $b->direction === BookingDirection::Income)
            ->sum('amountCents');

        $expenseCents = $bookings
            ->filter(fn ($b) => $b->direction === BookingDirection::Expense)
            ->sum('amountCents');

        return response()->json([
            'bookings' => $serialized,
            'summary' => [
                'count' => $bookings->count(),
                'income_eur' => number_format($incomeCents / 100, 2, ',', '.'),
                'expense_eur' => number_format($expenseCents / 100, 2, ',', '.'),
            ],
        ]);
    }

    public function kassenbuchEintraege(): JsonResponse
    {
        try {
            $dateFrom = CarbonImmutable::now()->startOfYear()->format('Y-m-d');
            $eintraege = $this->kassenbuch->getAllEntries(dateFrom: $dateFrom);
        } catch (KassenbuchApiException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $incomeCents = $eintraege
            ->filter(fn ($e) => (float) $e['amount'] > 0)
            ->sum(fn ($e) => (int) round((float) $e['amount'] * 100));

        $expenseCents = $eintraege
            ->filter(fn ($e) => (float) $e['amount'] < 0)
            ->sum(fn ($e) => (int) round(abs((float) $e['amount']) * 100));

        return response()->json([
            'eintraege' => $eintraege->values(),
            'summary' => [
                'count' => $eintraege->count(),
                'income_eur' => number_format($incomeCents / 100, 2, ',', '.'),
                'expense_eur' => number_format($expenseCents / 100, 2, ',', '.'),
                'balance_eur' => number_format(($incomeCents - $expenseCents) / 100, 2, ',', '.'),
            ],
        ]);
    }

    public function addKassenbuchEintrag(Request $request): JsonResponse
    {
        $data = $request->validate([
            'to_from' => 'required|string|max:255',
            'direction' => 'required|in:Income,Expense',
            'amount_cents' => 'required|integer|min:1',
            'booking_date' => 'required|date_format:Y-m-d',
        ]);

        $amount = $data['amount_cents'] / 100;
        if ($data['direction'] === 'Expense') {
            $amount *= -1;
        }

        try {
            $id = $this->kassenbuch->addEntry(
                toFrom: $data['to_from'],
                amount: $amount,
                bookingDate: $data['booking_date'].' 00:00:00',
            );
        } catch (KassenbuchApiException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['id' => $id]);
    }

    public function pushPretixBooking(Request $request): JsonResponse
    {
        $data = $request->validate([
            'direction' => 'required|in:Income,Expense',
            'amount_cents' => 'required|integer|min:1',
            'to_from' => 'required|string',
            'booking_text' => 'required|string',
            'metadata' => 'required|array',
            'date' => 'required|string',
        ]);

        $amount = $data['amount_cents'] / 100;
        if ($data['direction'] === 'Expense') {
            $amount *= -1;
        }

        $bookingDate = CarbonImmutable::createFromFormat('d.m.Y H:i', $data['date'])
            ->format('Y-m-d H:i:s');

        $localName = $request->user()->name;
        $toFrom = $data['to_from'];
        if ($localName !== $data['metadata']['cashier_name']) {
            $toFrom .= ' / '.$localName;
        }

        $metadata = $data['metadata'];
        $metadata['organizer'] = config('services.pretix.organizer');
        $metadata['booked_by'] = [
            'via' => 'wechselstube',
            'username' => $localName,
        ];

        try {
            $id = $this->kassenbuch->addEntry(
                toFrom: $toFrom,
                amount: $amount,
                bookingDate: $bookingDate,
                bookingText: $data['booking_text'],
                purpose: json_encode($metadata, JSON_UNESCAPED_UNICODE),
            );
        } catch (KassenbuchApiException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['id' => $id]);
    }
}
