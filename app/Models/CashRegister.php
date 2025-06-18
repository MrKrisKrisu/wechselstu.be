<?php

namespace App\Models;

use Database\Factories\CashRegisterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model {
    /** @use HasFactory<CashRegisterFactory> */
    use HasFactory, HasUuids;

    public    $incrementing = false;
    protected $keyType      = 'string';
    protected $fillable     = ['name', 'token', 'register_group_id'];

    public function workOrders(): HasMany {
        return $this->hasMany(WorkOrder::class);
    }

    public function registerGroup(): BelongsTo {
        return $this->belongsTo(RegisterGroup::class, 'register_group_id', 'id');
    }
}
