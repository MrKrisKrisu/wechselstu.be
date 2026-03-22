<?php

namespace App\Models;

use App\Enums\CashEntryType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'entry_number',
        'type',
        'amount_cents',
        'description',
        'created_by',
        'ticket_id',
        'counterpart_station_id',
        'reversed_by_entry_id',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashEntryType::class,
            'reversed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function counterpartStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'counterpart_station_id');
    }

    public function reversedByEntry(): BelongsTo
    {
        return $this->belongsTo(CashEntry::class, 'reversed_by_entry_id');
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }
}
