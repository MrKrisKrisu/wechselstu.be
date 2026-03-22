<?php

namespace App\Repositories;

use App\Models\DashboardAccess;
use App\Repositories\Interfaces\DashboardAccessRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DashboardAccessRepository implements DashboardAccessRepositoryInterface
{
    public function all(): Collection
    {
        return DashboardAccess::orderBy('created_at', 'desc')->get();
    }

    public function findByToken(string $token): ?DashboardAccess
    {
        return DashboardAccess::where('token', $token)->first();
    }

    public function create(array $data): DashboardAccess
    {
        return DashboardAccess::create($data);
    }

    public function delete(DashboardAccess $access): void
    {
        $access->delete();
    }
}
