<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegisterGroup extends Model {

    use HasFactory, HasUuids;

    public    $incrementing = false;
    protected $keyType      = 'string';


    protected $fillable = ['name', 'password'];

    protected $casts = [
        'name'     => 'string',
        'password' => 'string',
    ];

    public function cashRegisters() {
        return $this->hasMany(CashRegister::class, 'register_group_id', 'id');
    }
}
