<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PpobTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'product_id' => $this->product_id,
            'customer_number' => $this->customer_number,
            'sell_price' => $this->sell_price,
            'buy_price' => $this->buy_price,
            'saas_profit' => $this->saas_profit ?? null,
            'tenant_profit' => $this->tenant_profit ?? null,
            'status' => $this->status,
            'sn' => $this->sn,
            'reference_id' => $this->reference_id,
            'message' => $this->message ?? null,
            'product' => $this->whenLoaded('product', fn () => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'category' => $this->product->category,
            ] : null),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
