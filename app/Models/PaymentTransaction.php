<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class PaymentTransaction extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'reference',
        'amount',
        'invoice_ids',
        'status',
        'payment_url',
        'qr_url',
    ];

    protected $casts = [
        'invoice_ids' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
