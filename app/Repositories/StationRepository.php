<?php

namespace App\Repositories;

use App\Models\CashEntry;
use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class StationRepository
{
    public function all(): Collection
    {
        return Station::orderBy('name')->get();
    }

    public function allForLedger(): Collection
    {
        return Station::orderBy('name')->get(['id', 'name', 'location']);
    }

    public function balances(): SupportCollection
    {
        return CashEntry::whereNotNull('counterpart_station_id')
            ->whereNull('reversed_at')
            ->selectRaw('counterpart_station_id, SUM(-amount_cents) as balance_cents')
            ->groupBy('counterpart_station_id')
            ->pluck('balance_cents', 'counterpart_station_id');
    }

    public function findById(string $id): ?Station
    {
        return Station::find($id);
    }

    public function findByToken(string $token): ?Station
    {
        return Station::where('token', $token)->first();
    }

    public function create(array $data): Station
    {
        return Station::create($data);
    }

    public function update(Station $station, array $data): Station
    {
        $station->update($data);

        return $station->fresh();
    }

    public function delete(Station $station): void
    {
        $station->delete();
    }
}
