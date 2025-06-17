<?php

namespace App\Models;

use App\Enum\WorkOrderStatus;
use App\Enum\WorkOrderType;
use App\Observers\WorkOrderObserver;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([WorkOrderObserver::class])]
class WorkOrder extends Model {
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory, HasUuids, LogsActivity;

    public    $incrementing = false;
    protected $keyType      = 'string';
    protected $fillable     = ['cash_register_id', 'type', 'status', 'event_id'];
    protected $casts        = [
        'id'               => 'string',
        'cash_register_id' => 'string',
        'type'             => WorkOrderType::class,
        'status'           => WorkOrderStatus::class,
        'event_id'         => 'string',
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

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()->logOnlyDirty()->logOnly(['status']);
    }
}
