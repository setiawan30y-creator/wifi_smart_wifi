<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantPpobMarkup extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = ['id'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product()
    {
        return $this->belongsTo(PpobProduct::class, 'product_id');
    }
}
