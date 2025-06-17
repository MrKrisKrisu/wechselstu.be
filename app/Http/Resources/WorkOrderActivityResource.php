<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderActivityResource extends JsonResource {

    public function toArray(Request $request): array {
        $statusBefore = null;
        $statusAfter  = null;

        if(isset($this->properties['attributes']['status'])) {
            $statusAfter = $this->properties['attributes']['status'];
        }
        if(isset($this->properties['old']['status'])) {
            $statusBefore = $this->properties['old']['status'];
        }

        if($statusBefore === $statusAfter) {
            // If the status hasn't changed, we don't need to return this activity
            return [];
        }

        $causedUser = $this->causer ? [
            'id'   => $this->causer?->id,
            'name' => $this->causer?->name,
        ] : null;

        return [
            'description' => $this->description,
            'status'      => [
                'before' => $statusBefore,
                'after'  => $statusAfter,
            ],
            'caused_by'   => $causedUser,
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
