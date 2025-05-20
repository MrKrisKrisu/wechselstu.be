<?php

namespace App\Models;

use App\Enum\WorkOrderStatus;
use App\Enum\WorkOrderType;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model {
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory, HasUuids;

    public    $incrementing = false;
    protected $keyType      = 'string';
    protected $fillable     = ['cash_register_id', 'type', 'status', 'notes'];
    protected $casts        = [
        'id'               => 'string',
        'cash_register_id' => 'string',
        'type'             => WorkOrderType::class,
        'status'           => WorkOrderStatus::class,
        'notes'            => 'string',
    ];

    public function cashRegister(): BelongsTo {
        return $this->belongsTo(CashRegister::class);
    }

    /**
     * If type is 'change_request', these are the requested coins (see Model ChangeRequestItem)
     */
    public function changeRequestItems(): HasMany {
        return $this->hasMany(ChangeRequestItem::class);
    }
}
