<?php

namespace App\Services;

class PretixCashBalanceService
{
    private const COL_DEVICE_ID = 0;

    private const COL_TRAINING = 5;

    private const COL_STATUS = 17;

    private const COL_LINE_TYPE = 18;

    private const COL_PAYMENT = 9;

    private const COL_AMOUNT = 32;

    private const CHANGE_TYPES = ['CHANGE_IN', 'CHANGE_OUT', 'CHANGE_DIFF'];

    private const CASH_SALE_TYPES = ['PRODUCT_SALE', 'PRODUCT_RETURN', 'GIFTCARD_REDEMPTION'];

    public function __construct(private readonly PretixApiService $pretix) {}

    /**
     * Returns balance in cents per Pretix device ID.
     *
     * @return array<int, int>
     */
    public function balanceByDevice(): array
    {
        $csv = $this->pretix->fetchPosReceiptLinesCsv();
        $balances = [];

        foreach (explode("\n", trim($csv)) as $i => $raw) {
            if ($i === 0) {
                continue;
            }

            $cols = str_getcsv(trim($raw), ',', '"', '\\');
            if (count($cols) <= self::COL_AMOUNT) {
                continue;
            }

            if ($cols[self::COL_TRAINING] === 'TRAINING') {
                continue;
            }

            if ($cols[self::COL_STATUS] !== 'AKTIV') {
                continue;
            }

            $type = $cols[self::COL_LINE_TYPE];
            $payment = $cols[self::COL_PAYMENT];
            $deviceId = (int) $cols[self::COL_DEVICE_ID];

            $isChange = in_array($type, self::CHANGE_TYPES);
            $isCashSale = in_array($type, self::CASH_SALE_TYPES) && $payment === 'cash';

            if (! $isChange && ! $isCashSale) {
                continue;
            }

            $cents = (int) round((float) str_replace(',', '.', $cols[self::COL_AMOUNT]) * 100);
            $balances[$deviceId] = ($balances[$deviceId] ?? 0) + $cents;
        }

        return $balances;
    }
}
