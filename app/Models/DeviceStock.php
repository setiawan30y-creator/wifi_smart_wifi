<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class DeviceStock extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'device_stocks';

    protected $fillable = [
        'tenant_id',
        'name',
        'brand',
        'type', // FO | LAN
        'image',
        'stock',
        'unit_price',
        'description'
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
