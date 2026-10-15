<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OltResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'host' => $this->host,
            'port' => $this->port,
            'protocol' => $this->protocol,
            'username' => $this->username,
            'snmp_port' => $this->snmp_port,
            'pon_ports' => $this->pon_ports,
            'status' => $this->status,
            'last_checked_at' => optional($this->last_checked_at)->toISOString(),
            'last_error' => $this->last_error,
            'description' => $this->description,
            'onus' => OltOnuResource::collection($this->whenLoaded('onus')),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
