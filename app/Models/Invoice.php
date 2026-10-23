<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class Invoice extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'invoice_number',
        'month',
        'year',
        'amount',
        'status',
        'paid_at',
        'payment_method',
        'payment_proof',
        'payment_ref',
        'payment_url',
        'overdue_reminder_count',
        'last_overdue_reminder_sent_at',
        'payment_promise_date',
        'payment_promise_note',
        'payment_promise_wa_template_id',
        'payment_promise_wa_sent_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'last_overdue_reminder_sent_at' => 'datetime',
        'payment_promise_date' => 'date',
        'payment_promise_wa_sent_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function reminders()
    {
        return $this->hasMany(InvoiceReminder::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $monthStr = str_pad($invoice->month, 2, '0', STR_PAD_LEFT);
                $prefix = 'INV-' . $invoice->year . $monthStr . '-';
                
                $lastInvoice = static::withoutGlobalScopes()
                    ->where('year', $invoice->year)
                    ->where('month', $invoice->month)
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($lastInvoice && preg_match('/-(\d+)$/', $lastInvoice->invoice_number, $matches)) {
                    $lastNum = intval($matches[1]);
                    $newNum = $lastNum + 1;
                } else {
                    $newNum = 1;
                }
                
                $invoice->invoice_number = $prefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
