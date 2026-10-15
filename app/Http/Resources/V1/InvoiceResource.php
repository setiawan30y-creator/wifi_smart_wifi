<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'invoice_number' => $this->invoice_number,
            'month' => $this->month,
            'year' => $this->year,
            'amount' => $this->amount,
            'status' => $this->status,
            'paid_at' => optional($this->paid_at)->toISOString(),
            'payment_method' => $this->payment_method,
            'payment_proof_url' => $this->payment_proof ? asset('storage/' . $this->payment_proof) : null,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'package' => $this->customer->relationLoaded('package') && $this->customer->package ? [
                    'id' => $this->customer->package->id,
                    'name' => $this->customer->package->name,
                    'price' => $this->customer->package->price,
                ] : null,
            ]),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
