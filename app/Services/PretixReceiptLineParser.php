<?php

namespace App\Services;

use App\Data\PretixReceiptLine;
use App\Enums\PretixLineType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use ValueError;

class PretixReceiptLineParser
{
    private const DATE_FORMATS = ['Y-m-d H:i', 'd.m.Y H:i'];

    // Column indices are identical in both German and English Pretix exports.
    private const COL_DEVICE_ID = 0;

    private const COL_CLOSING_ID = 3;

    private const COL_TRANSACTION_ID = 4;

    private const COL_CLOSED_AT = 7;

    private const COL_CASHIER_ID = 12;

    private const COL_CASHIER_NAME = 13;

    private const COL_LINE_TYPE = 18;

    private const COL_GROSS_PRICE = 32;

    /**
     * @return Collection<int, PretixReceiptLine>
     */
    public function parse(string $csv): Collection
    {
        return collect(explode("\n", trim($csv)))
            ->skip(1)
            ->map(fn (string $line) => str_getcsv(trim($line), ',', '"', '\\'))
            ->filter(fn (array $columns) => count($columns) > self::COL_GROSS_PRICE)
            ->map(fn (array $columns) => $this->parseLine($columns))
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function parseLine(array $columns): ?PretixReceiptLine
    {
        try {
            $lineType = PretixLineType::from($columns[self::COL_LINE_TYPE]);
        } catch (ValueError) {
            return null;
        }

        $closedAt = $this->parseDate($columns[self::COL_CLOSED_AT]);

        if (! $closedAt) {
            return null;
        }

        return new PretixReceiptLine(
            deviceId: (int) $columns[self::COL_DEVICE_ID],
            closingId: (int) $columns[self::COL_CLOSING_ID],
            transactionId: (int) $columns[self::COL_TRANSACTION_ID],
            cashierId: (int) $columns[self::COL_CASHIER_ID],
            cashierName: $columns[self::COL_CASHIER_NAME],
            lineType: $lineType,
            amountCents: $this->parseAmountCents($columns[self::COL_GROSS_PRICE]),
            closedAt: $closedAt,
        );
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        foreach (self::DATE_FORMATS as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, $value);
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }

    private function parseAmountCents(string $value): int
    {
        return (int) round(abs((float) str_replace(',', '.', $value)) * 100);
    }
}
