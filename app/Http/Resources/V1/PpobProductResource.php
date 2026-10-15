<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PpobProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'provider' => $this->provider ?? null,
            'brand' => $this->brand ?? null,
            'type' => $this->type ?? null,
            'sell_price' => $this->sell_price,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
