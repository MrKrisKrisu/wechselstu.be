<?php

namespace App\Data;

use App\Enums\PretixLineType;
use Carbon\CarbonImmutable;

readonly class PretixReceiptLine
{
    public function __construct(
        public int $deviceId,
        public int $closingId,
        public int $transactionId,
        public int $cashierId,
        public string $cashierName,
        public PretixLineType $lineType,
        public int $amountCents,
        public CarbonImmutable $closedAt,
    ) {}
}
