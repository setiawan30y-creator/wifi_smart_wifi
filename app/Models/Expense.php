<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class Expense extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'expenses';

    protected $fillable = [
        'tenant_id',
        'title',
        'category', // Pembelian Perangkat | Operasional | Gaji/Honor | Perbaikan | Lain-lain
        'amount',
        'date',
        'device_stock_id',
        'quantity',
        'description',
        'receipt_photo',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function deviceStock()
    {
        return $this->belongsTo(DeviceStock::class, 'device_stock_id');
    }
}
