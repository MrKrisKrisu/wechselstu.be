<?php

namespace App\Repositories\Interfaces;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

interface TicketRepositoryInterface
{
    public function findById(string $id): ?Ticket;

    /** All tickets respecting the visible scope (done < 1h) */
    public function allVisible(array $filters = []): Collection;

    /** All tickets without time-filter (for finance list view) */
    public function allForFinance(array $filters = []): Collection;

    public function forStation(string $stationId): Collection;

    public function create(array $data): Ticket;

    public function accept(Ticket $ticket, User $user): Ticket;

    public function complete(Ticket $ticket): Ticket;

    public function unlinkedDoneSuggestions(SupportCollection $linkedTicketIds, ?Carbon $lockedUntil): Collection;
}
