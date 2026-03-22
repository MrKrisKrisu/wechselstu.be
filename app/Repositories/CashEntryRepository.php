<?php

namespace App\Repositories;

use App\Enums\CashEntryType;
use App\Models\CashEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class CashEntryRepository
{
    public function allWithRelations(): Collection
    {
        return CashEntry::with(['creator', 'ticket.station', 'counterpartStation'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function create(array $data): CashEntry
    {
        $entry = CashEntry::create($data);
        $entry->load(['creator', 'ticket.station', 'counterpartStation']);

        return $entry;
    }

    public function createReversal(CashEntry $entry, string $userId): CashEntry
    {
        $reversal = CashEntry::create([
            'type' => CashEntryType::Reversal->value,
            'amount_cents' => -$entry->amount_cents,
            'description' => 'Storno von Buchung vom '.$entry->created_at->format('d.m.Y H:i').($entry->description ? ': '.$entry->description : ''),
            'created_by' => $userId,
            'counterpart_station_id' => $entry->counterpart_station_id,
        ]);

        $entry->update([
            'reversed_at' => now(),
            'reversed_by_entry_id' => $reversal->id,
        ]);

        $reversal->load(['creator', 'counterpartStation']);

        return $reversal;
    }

    public function balanceUpTo(Carbon $lockedUntil): int
    {
        return (int) CashEntry::where('created_at', '<=', $lockedUntil)->sum('amount_cents');
    }

    public function linkedTicketIds(): SupportCollection
    {
        return CashEntry::whereNotNull('ticket_id')->pluck('ticket_id');
    }
}
