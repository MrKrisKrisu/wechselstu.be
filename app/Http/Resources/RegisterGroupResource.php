<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterGroupResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id'   => $this->id,
            'name' => $this->name,
        ];
    }
}
