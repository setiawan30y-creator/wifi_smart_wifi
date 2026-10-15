<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'brand' => $this->brand,
            'type' => $this->type,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'stock' => $this->stock,
            'unit_price' => $this->unit_price,
            'description' => $this->description,
            'expenses_count' => $this->whenCounted('expenses'),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
