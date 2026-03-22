<?php

namespace App\Repositories\Interfaces;

use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;

interface StationRepositoryInterface
{
    public function all(): Collection;

    public function findById(string $id): ?Station;

    public function findByToken(string $token): ?Station;

    public function create(array $data): Station;

    public function update(Station $station, array $data): Station;

    public function delete(Station $station): void;
}
