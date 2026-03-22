<?php

namespace App\Repositories;

use App\Models\CashClosing;
use Illuminate\Database\Eloquent\Collection;

class CashClosingRepository
{
    public function allWithCreator(): Collection
    {
        return CashClosing::with('creator')
            ->orderBy('locked_until', 'asc')
            ->get();
    }

    public function latest(): ?CashClosing
    {
        return CashClosing::orderBy('locked_until', 'desc')->first();
    }

    public function create(array $data): CashClosing
    {
        $closing = CashClosing::create($data);
        $closing->load('creator');

        return $closing;
    }
}
