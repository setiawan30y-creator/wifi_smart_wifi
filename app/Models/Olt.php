<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Olt extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'brand',
        'model',
        'host',
        'port',
        'protocol',
        'username',
        'password',
        'snmp_community',
        'snmp_port',
        'pon_ports',
        'status',
        'last_checked_at',
        'last_error',
        'description',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = filled($value) ? Crypt::encryptString($value) : null;
    }

    public function getPasswordAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function setSnmpCommunityAttribute($value): void
    {
        $this->attributes['snmp_community'] = filled($value) ? Crypt::encryptString($value) : null;
    }

    public function getSnmpCommunityAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function onus()
    {
        return $this->hasMany(OltOnu::class);
    }
}
