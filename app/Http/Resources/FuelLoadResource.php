<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FuelLoadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipment_id' => $this->equipment_id,
            'loaded_at' => $this->loaded_at?->toIso8601String(),
            'liters' => (float) $this->liters,
            'amount_clp' => $this->amount_clp,
            'hourmeter' => (float) $this->hourmeter,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}