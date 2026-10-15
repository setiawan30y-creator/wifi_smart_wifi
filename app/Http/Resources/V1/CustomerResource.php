<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestStatusHistory = $this->whenLoaded('statusHistories', fn () => $this->statusHistories->first());

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'package_id' => $this->package_id,
            'odp_id' => $this->odp_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'maps_link' => $this->maps_link,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'billing_day' => $this->billing_day,
            'tanggal_pasang' => $this->tanggal_pasang,
            'pppoe_username' => $this->pppoe_username,
            'status_rumah' => $this->status_rumah,
            'pemilik_rumah' => $this->pemilik_rumah,
            'jalur' => $this->jalur,
            'perangkat' => $this->perangkat,
            'balance' => $this->balance,
            'package' => $this->whenLoaded('package', fn () => [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'price' => $this->package->price,
                'speed' => $this->package->speed,
            ]),
            'odp' => $this->whenLoaded('odp', fn () => [
                'id' => $this->odp->id,
                'name' => $this->odp->name,
                'address' => $this->odp->address,
            ]),
            'latest_status_history' => $latestStatusHistory ? [
                'old_status' => $latestStatusHistory->old_status,
                'new_status' => $latestStatusHistory->new_status,
                'action' => $latestStatusHistory->action,
                'reason' => $latestStatusHistory->reason,
                'changed_at' => optional($latestStatusHistory->changed_at)->toISOString(),
                'changed_by' => $latestStatusHistory->user?->name,
            ] : null,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
