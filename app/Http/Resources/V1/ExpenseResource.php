<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'title' => $this->title,
            'category' => $this->category,
            'amount' => $this->amount,
            'date' => optional($this->date)->toDateString(),
            'device_stock_id' => $this->device_stock_id,
            'quantity' => $this->quantity,
            'description' => $this->description,
            'device_stock' => $this->whenLoaded('deviceStock', fn () => $this->deviceStock ? [
                'id' => $this->deviceStock->id,
                'name' => $this->deviceStock->name,
                'brand' => $this->deviceStock->brand,
                'type' => $this->deviceStock->type,
            ] : null),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
