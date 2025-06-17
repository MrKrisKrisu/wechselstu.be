<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id'                   => $this->id,
            'status'               => $this->status,
            'type'                 => $this->type?->value,
            'notes'                => $this->notes,
            'cash_register'        => new CashRegisterResource($this->whenLoaded('cashRegister')),
            'change_request_items' => ChangeRequestItemResource::collection($this->whenLoaded('changeRequestItems')),
            'activities'           => WorkOrderActivityResource::collection($this->whenLoaded('activities')),
            'created_at'           => $this->created_at->toIso8601String(),
        ];
    }
}
