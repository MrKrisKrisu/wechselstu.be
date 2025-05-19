<?php

namespace App\Models;

use Database\Factories\ChangeRequestItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRequestItem extends Model {
    /** @use HasFactory<ChangeRequestItemFactory> */
    use HasFactory, HasUuids;

    public    $incrementing = false;
    protected $keyType      = 'string';
    protected $fillable     = ['work_order_id', 'denomination', 'quantity',];

    public function workOrder(): BelongsTo {
        return $this->belongsTo(WorkOrder::class);
    }
}
