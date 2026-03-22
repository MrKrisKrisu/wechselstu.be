<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DashboardAccess extends Model
{
    use HasUuids;

    protected $fillable = ['label'];

    protected static function booted(): void
    {
        static::creating(function (DashboardAccess $access) {
            $access->token = Str::random(64);
        });
    }
}
