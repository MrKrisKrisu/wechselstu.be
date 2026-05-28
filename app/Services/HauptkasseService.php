<?php

namespace App\Services;

use App\Data\KassenbuchEintrag;
use App\Models\Station;
use Illuminate\Support\Collection;

readonly class HauptkasseService
{
    public function __construct(
        private PretixApiService $pretix,
        private PretixReceiptLineParser $parser,
        private KassenbuchEintragFactory $factory,
    ) {}

    /**
     * @return Collection<int, KassenbuchEintrag>
     */
    public function preparePretixBookings(): Collection
    {
        $csv = $this->pretix->fetchPosReceiptLinesCsv();
        $lines = $this->parser->parse($csv);

        $deviceNameMap = Station::whereNotNull('pretix_device_id')
            ->pluck('name', 'pretix_device_id')
            ->all();

        return $this->factory->fromReceiptLines($lines, $deviceNameMap);
    }
}
