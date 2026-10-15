<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'database',
        'status',
        'balance',
        'subscription_plan',
        'subscription_ends_at',
        'is_trial',
    ];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
        'is_trial' => 'boolean',
        'balance' => 'decimal:2',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function ppobMarkups()
    {
        return $this->hasMany(TenantPpobMarkup::class);
    }

    public function saasInvoices()
    {
        return $this->hasMany(SaasInvoice::class);
    }
}
