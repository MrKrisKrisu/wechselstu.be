<?php

namespace App\Data;

use App\Enums\BookingDirection;
use Carbon\CarbonImmutable;

readonly class KassenbuchEintrag
{
    public function __construct(
        public BookingDirection $direction,
        public int $amountCents,
        public string $description,
        public string $toFrom,
        public string $bookingText,
        public array $metadata,
        public CarbonImmutable $date,
    ) {}

    public function amountEur(): string
    {
        return number_format($this->amountCents / 100, 2, ',', '.');
    }
}
