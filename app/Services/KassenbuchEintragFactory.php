<?php

namespace App\Services;

use App\Data\KassenbuchEintrag;
use App\Data\PretixReceiptLine;
use App\Enums\BookingDirection;
use App\Enums\PretixLineType;
use Illuminate\Support\Collection;

class KassenbuchEintragFactory
{
    /** @var array<int, string> */
    private array $deviceNameMap = [];

    /**
     * @param  Collection<int, PretixReceiptLine>  $lines
     * @param  array<int, string>  $deviceNameMap
     * @return Collection<int, KassenbuchEintrag>
     */
    public function fromReceiptLines(Collection $lines, array $deviceNameMap = []): Collection
    {
        $this->deviceNameMap = $deviceNameMap;

        return $lines
            ->reject(fn (PretixReceiptLine $line) => in_array($line->lineType, [
                PretixLineType::ChangeDiff,
                PretixLineType::ChangeStart,
            ]))
            ->map(fn (PretixReceiptLine $line) => $this->fromLine($line))
            ->values();
    }

    private function fromLine(PretixReceiptLine $line): KassenbuchEintrag
    {
        return new KassenbuchEintrag(
            direction: $this->direction($line->lineType),
            amountCents: $line->amountCents,
            description: $this->description($line),
            toFrom: $this->toFrom($line),
            bookingText: $this->bookingText($line),
            metadata: $this->metadata($line),
            date: $line->closedAt,
        );
    }

    private function direction(PretixLineType $type): BookingDirection
    {
        return match ($type) {
            PretixLineType::ChangeOut => BookingDirection::Income,
            PretixLineType::ChangeIn => BookingDirection::Expense,
        };
    }

    private function description(PretixReceiptLine $line): string
    {
        return sprintf(
            '%s | Closing %d | Transaction %d | %s | %s',
            $this->deviceName($line->deviceId),
            $line->closingId,
            $line->transactionId,
            $line->cashierName,
            $line->lineType->label(),
        );
    }

    private function deviceName(int $deviceId): string
    {
        return $this->deviceNameMap[$deviceId] ?? "Device $deviceId";
    }

    private function deviceLabel(int $deviceId): string
    {
        $name = $this->deviceNameMap[$deviceId] ?? null;

        return $name !== null ? "$name (Device $deviceId)" : "Device $deviceId";
    }

    private function toFrom(PretixReceiptLine $line): string
    {
        return sprintf('%s | %s | %s', $line->lineType->label(), $this->deviceLabel($line->deviceId), $line->cashierName);
    }

    private function bookingText(PretixReceiptLine $line): string
    {
        return sprintf(
            '%s | %s | Closing %d | Transaction %d | Cashier: %s',
            $line->lineType->label(),
            $this->deviceLabel($line->deviceId),
            $line->closingId,
            $line->transactionId,
            $line->cashierName,
        );
    }

    private function metadata(PretixReceiptLine $line): array
    {
        return [
            'device_id' => $line->deviceId,
            'closing_id' => $line->closingId,
            'transaction_id' => $line->transactionId,
            'cashier_id' => $line->cashierId,
            'cashier_name' => $line->cashierName,
            'pretix_transaction_type' => $line->lineType->value,
        ];
    }
}
