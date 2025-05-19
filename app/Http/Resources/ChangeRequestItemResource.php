<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangeRequestItemResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id'           => $this->id,
            'work_order'   => new WorkOrderResource($this->whenLoaded('workOrder')),
            'denomination' => $this->denomination,
            'quantity'     => $this->quantity,
        ];
    }
}
