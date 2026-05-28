<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    protected function profileRules(?string $userId = null): array
    {
        return [
            'name' => $this->nameRules($userId),
        ];
    }

    protected function nameRules(?string $userId = null): array
    {
        return [
            'required',
            'string',
            'max:255',
            'regex:/^\S+$/',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
