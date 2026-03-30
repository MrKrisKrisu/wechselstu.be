<?php

namespace App\Repositories;

use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;

class StationRepository
{
    public function all(): Collection
    {
        return Station::orderBy('name')->get();
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
