<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashClosing extends Model
{
    use HasUuids;

    protected $fillable = [
        'label',
        'closing_date',
        'locked_until',
        'balance_cents',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'locked_until' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
