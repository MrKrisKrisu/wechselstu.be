<?php

namespace App\Repositories\Interfaces;

use App\Models\DashboardAccess;
use Illuminate\Database\Eloquent\Collection;

interface DashboardAccessRepositoryInterface
{
    public function all(): Collection;

    public function findByToken(string $token): ?DashboardAccess;

    public function create(array $data): DashboardAccess;

    public function delete(DashboardAccess $access): void;
}
