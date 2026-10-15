<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class PpobSupplier extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function supplierProducts()
    {
        return $this->hasMany(PpobSupplierProduct::class, 'supplier_id');
    }
}
