<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_type',
        'name',
        'label',
    ];
}
