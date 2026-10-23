<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class InvoiceReminder extends Model
{
    use BelongsToTenant;

    public const BEFORE = 'before';
    public const AFTER = 'after';

    protected $fillable = ['tenant_id', 'invoice_id', 'type', 'offset_days', 'status', 'attempted_at', 'error'];

    protected $casts = [
        'attempted_at' => 'datetime',
        'offset_days' => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /** Label singkat, mis. "H-3", "Hari H", "H+7". */
    public function label(): string
    {
        if ($this->type === self::BEFORE) {
            return $this->offset_days === 0 ? 'Hari H' : 'H-' . $this->offset_days;
        }

        return 'H+' . $this->offset_days;
    }
}
