<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'token' => $this->token,
            'printer_ip' => $this->printer_ip,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
