<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Station extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'location'];

    protected static function booted(): void
    {
        static::creating(function (Station $station) {
            $station->token = Str::upper(Str::random(4));
        });
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
