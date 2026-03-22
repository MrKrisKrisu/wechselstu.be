<?php

namespace App\Models;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['station_id', 'type', 'status', 'message', 'assigned_to', 'accepted_at', 'done_at'];

    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'status' => TicketStatus::class,
            'accepted_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    /**
     * Scope: exclude tickets that are done for more than 1 hour.
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where('status', '!=', TicketStatus::Done->value)
                ->orWhere('done_at', '>', now()->subHour());
        });
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function denominations(): HasMany
    {
        return $this->hasMany(TicketDenomination::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
