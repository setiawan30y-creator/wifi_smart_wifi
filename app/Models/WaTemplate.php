<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class WaTemplate extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'wa_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'title',
        'content',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];
}
