<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PpobSupplierProduct extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function supplier()
    {
        return $this->belongsTo(PpobSupplier::class, 'supplier_id');
    }

    public function product()
    {
        return $this->belongsTo(PpobProduct::class, 'product_id');
    }
}
