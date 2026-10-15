<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OdpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'parent_odp_id' => $this->parent_odp_id,
            'name' => $this->name,
            'fo_ports' => $this->fo_ports,
            'lan_ports' => $this->lan_ports,
            'address' => $this->address,
            'description' => $this->description,
            'maps_link' => $this->maps_link,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'route_waypoints' => $this->route_waypoints,
            'photos' => [
                'photo_1_url' => $this->photo_1 ? asset('storage/' . $this->photo_1) : null,
                'photo_2_url' => $this->photo_2 ? asset('storage/' . $this->photo_2) : null,
                'photo_3_url' => $this->photo_3 ? asset('storage/' . $this->photo_3) : null,
                'photo_4_url' => $this->photo_4 ? asset('storage/' . $this->photo_4) : null,
            ],
            'customers_count' => $this->whenCounted('customers'),
            'parent_odp' => $this->whenLoaded('parentOdp', fn () => $this->parentOdp ? [
                'id' => $this->parentOdp->id,
                'name' => $this->parentOdp->name,
            ] : null),
            'devices' => $this->whenLoaded('devices', fn () => $this->devices->map(fn ($device) => [
                'id' => $device->id,
                'device_type' => $device->device_type,
                'device_spec' => $device->device_spec,
                'fo_ports' => $device->fo_ports,
                'lan_ports' => $device->lan_ports,
            ])->values()),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
