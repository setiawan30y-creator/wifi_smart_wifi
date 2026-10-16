<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class Odp extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'parent_odp_id',
        'name',
        'fo_ports',
        'lan_ports',
        'address',
        'description',
        'maps_link',
        'latitude',
        'longitude',
        'route_waypoints',
        'photo_1',
        'photo_2',
        'photo_3',
        'photo_4'
    ];

    protected $casts = [
        'route_waypoints' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($odp) {
            if ($odp->maps_link && ($coords = \App\Support\MapsCoordinates::fromText($odp->maps_link))) {
                $odp->latitude = $coords['lat'];
                $odp->longitude = $coords['lng'];
            }
        });
    }

    public function parentOdp()
    {
        return $this->belongsTo(Odp::class, 'parent_odp_id');
    }

    public function childOdps()
    {
        return $this->hasMany(Odp::class, 'parent_odp_id');
    }

    public function devices()
    {
        return $this->hasMany(OdpDevice::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
