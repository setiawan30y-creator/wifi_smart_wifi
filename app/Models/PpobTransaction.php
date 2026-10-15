<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class PpobTransaction extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = ['id'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(PpobProduct::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(PpobSupplier::class, 'supplier_id');
    }
}
