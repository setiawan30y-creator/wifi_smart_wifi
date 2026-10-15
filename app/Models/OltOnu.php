<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OltOnu extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'olt_id',
        'customer_id',
        'pon_port',
        'onu_id',
        'serial_number',
        'name',
        'pppoe_username',
        'status',
        'pppoe_status',
        'rx_power',
        'tx_power',
        'distance',
        'uptime',
        'last_seen_at',
        'last_polled_at',
        'last_error',
        'notes',
    ];

    protected $casts = [
        'rx_power' => 'decimal:2',
        'tx_power' => 'decimal:2',
        'last_seen_at' => 'datetime',
        'last_polled_at' => 'datetime',
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
