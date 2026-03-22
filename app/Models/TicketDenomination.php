<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketDenomination extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['ticket_id', 'denomination_cents', 'quantity'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->denomination_cents / 100, 2, ',', '').' €';
    }
}
