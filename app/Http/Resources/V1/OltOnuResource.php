<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OltOnuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'olt_id' => $this->olt_id,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
                'pppoe_username' => $this->customer?->pppoe_username,
                'package' => $this->customer?->package?->name,
            ]),
            'pon_port' => $this->pon_port,
            'onu_id' => $this->onu_id,
            'serial_number' => $this->serial_number,
            'name' => $this->name,
            'pppoe_username' => $this->pppoe_username,
            'status' => $this->status,
            'pppoe_status' => $this->pppoe_status,
            'rx_power' => $this->rx_power,
            'tx_power' => $this->tx_power,
            'distance' => $this->distance,
            'uptime' => $this->uptime,
            'last_seen_at' => optional($this->last_seen_at)->toISOString(),
            'last_polled_at' => optional($this->last_polled_at)->toISOString(),
            'last_error' => $this->last_error,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
