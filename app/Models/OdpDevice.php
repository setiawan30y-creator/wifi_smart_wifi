<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class OdpDevice extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'odp_id',
        'device_type',
        'device_spec',
        'fo_ports',
        'lan_ports',
    ];

    public function odp()
    {
        return $this->belongsTo(Odp::class);
    }
}
