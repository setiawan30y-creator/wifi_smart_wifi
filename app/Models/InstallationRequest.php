<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallationRequest extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_NEW = 'new';
    public const STATUS_SURVEY = 'survey';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_INSTALLED = 'installed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'package_id',
        'odp_id',
        'customer_id',
        'created_by',
        'assigned_to',
        'name',
        'phone',
        'email',
        'address',
        'maps_link',
        'latitude',
        'longitude',
        'preferred_installation_date',
        'scheduled_at',
        'status',
        'source',
        'notes',
        'survey_notes',
        'cancel_reason',
        'converted_at',
    ];

    protected $casts = [
        'preferred_installation_date' => 'date',
        'scheduled_at' => 'datetime',
        'converted_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => 'Baru',
            self::STATUS_SURVEY => 'Survey',
            self::STATUS_SCHEDULED => 'Jadwal Pasang',
            self::STATUS_INSTALLED => 'Terpasang',
            self::STATUS_CANCELLED => 'Batal',
        ];
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function odp()
    {
        return $this->belongsTo(Odp::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
