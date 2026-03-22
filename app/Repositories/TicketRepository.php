<?php

namespace App\Repositories;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class TicketRepository implements TicketRepositoryInterface
{
    public function findById(string $id): ?Ticket
    {
        return Ticket::with(['station', 'denominations', 'assignedUser'])->find($id);
    }

    public function allVisible(array $filters = []): Collection
    {
        return Ticket::with(['station', 'denominations', 'assignedUser'])
            ->visible()
            ->when(isset($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['station_id']), fn ($q) => $q->where('station_id', $filters['station_id']))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function allForFinance(array $filters = []): Collection
    {
        return Ticket::with(['station', 'denominations', 'assignedUser'])
            ->when(isset($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['station_id']), fn ($q) => $q->where('station_id', $filters['station_id']))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function forStation(string $stationId): Collection
    {
        return Ticket::with(['denominations'])
            ->where('station_id', $stationId)
            ->visible()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): Ticket
    {
        return Ticket::create($data);
    }

    public function accept(Ticket $ticket, User $user): Ticket
    {
        $ticket->update([
            'status' => TicketStatus::Accepted,
            'assigned_to' => $user->id,
            'accepted_at' => now(),
        ]);

        return $ticket->fresh(['station', 'denominations', 'assignedUser']);
    }

    public function complete(Ticket $ticket): Ticket
    {
        $ticket->update([
            'status' => TicketStatus::Done,
            'done_at' => now(),
        ]);

        return $ticket->fresh(['station', 'denominations', 'assignedUser']);
    }

    public function unlinkedDoneSuggestions(SupportCollection $linkedTicketIds, ?Carbon $lockedUntil): Collection
    {
        return Ticket::with(['station', 'denominations'])
            ->whereIn('type', [TicketType::ChangeRequest->value, TicketType::CashFull->value])
            ->where('status', TicketStatus::Done->value)
            ->whereNotIn('id', $linkedTicketIds)
            ->when($lockedUntil, fn ($q) => $q->where('done_at', '>', $lockedUntil))
            ->get();
    }
}
