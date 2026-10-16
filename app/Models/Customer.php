<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\DeviceStock;

class Customer extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'package_id',
        'odp_id',
        'name',
        'email',
        'phone',
        'address',
        'maps_link',
        'latitude',
        'longitude',
        'route_waypoints',
        'status',
        'billing_day',
        'billing_type',
        'billing_mode',
        'billing_note',
        'tanggal_pasang',
        'pppoe_username',
        'pppoe_password',
        'status_rumah',
        'pemilik_rumah',
        'foto_ktp',
        'foto_rumah',
        'foto_perangkat',
        'foto_odp',
        'jalur',
        'perangkat',
        'balance'
    ];

    protected $casts = [
        'route_waypoints' => 'array',
        'balance' => 'decimal:2',
    ];

    public const BILLING_PAID = 'paid';
    public const MODE_POSTPAID = 'postpaid';
    public const MODE_PREPAID = 'prepaid';

    /**
     * Data pelanggan milik akun login (role client), dicocokkan lewat email atau nomor HP
     * di dalam tenant aktif. Mengembalikan null bila akun belum terhubung ke pelanggan.
     */
    public static function forUser(\App\Models\User $user): ?self
    {
        $email = trim((string) $user->email);
        $phone = \App\Support\Phone::normalize($user->phone);

        if ($email === '' && !$phone) {
            return null;
        }

        return static::query()
            ->where(function ($query) use ($email, $user, $phone) {
                if ($email !== '') {
                    $query->orWhere('email', $email);
                }
                if ($phone) {
                    $query->orWhere('phone', $phone)
                        ->orWhere('phone', (string) $user->phone)
                        ->orWhere('phone', '0' . substr($phone, 2));
                }
            })
            ->orderBy('id')
            ->first();
    }

    public static function billingModeLabels(): array
    {
        return [
            self::MODE_POSTPAID => 'Pascabayar',
            self::MODE_PREPAID => 'Prabayar',
        ];
    }

    public function isPrepaid(): bool
    {
        return ($this->billing_mode ?? self::MODE_POSTPAID) === self::MODE_PREPAID;
    }

    public function billingModeLabel(): string
    {
        return self::billingModeLabels()[$this->billing_mode ?? self::MODE_POSTPAID] ?? 'Pascabayar';
    }

    public static function billingTypeLabels(): array
    {
        return [
            'paid' => 'Berbayar',
            'admin' => 'Gratis Admin',
            'social' => 'Gratis Sosial',
            'family' => 'Gratis Keluarga',
            'orphan' => 'Gratis Anak Yatim',
            'rt' => 'Gratis Jatah RT',
            'other_free' => 'Gratis Lainnya',
        ];
    }

    public function isBillable(): bool
    {
        return ($this->billing_type ?? self::BILLING_PAID) === self::BILLING_PAID;
    }

    public function billingTypeLabel(): string
    {
        return self::billingTypeLabels()[$this->billing_type ?? self::BILLING_PAID] ?? ucfirst((string) $this->billing_type);
    }

    protected static function booted()
    {
        static::saving(function ($customer) {
            if ($customer->maps_link) {
                if (preg_match('/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/', $customer->maps_link, $matches)) {
                    $customer->latitude = (float) $matches[1];
                    $customer->longitude = (float) $matches[2];
                }
            }
        });

        static::created(function ($customer) {
            if ($customer->perangkat) {
                $stock = DeviceStock::where('name', $customer->perangkat)->first();
                if ($stock) {
                    $stock->decrement('stock');
                }
            }
        });

        static::updated(function ($customer) {
            $oldPerangkat = $customer->getOriginal('perangkat');
            $newPerangkat = $customer->perangkat;
            if ($oldPerangkat !== $newPerangkat) {
                if ($oldPerangkat) {
                    $oldStock = DeviceStock::where('name', $oldPerangkat)->first();
                    if ($oldStock) {
                        $oldStock->increment('stock');
                    }
                }
                if ($newPerangkat) {
                    $newStock = DeviceStock::where('name', $newPerangkat)->first();
                    if ($newStock) {
                        $newStock->decrement('stock');
                    }
                }
            }
        });

        static::deleted(function ($customer) {
            if ($customer->perangkat) {
                $stock = DeviceStock::where('name', $customer->perangkat)->first();
                if ($stock) {
                    $stock->increment('stock');
                }
            }
        });
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function odp()
    {
        return $this->belongsTo(Odp::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function latestInvoice()
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function oltOnus()
    {
        return $this->hasMany(OltOnu::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function ppobTransactions()
    {
        return $this->hasMany(PpobTransaction::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(CustomerStatusHistory::class)->latest('changed_at');
    }

    public function latestStatusHistory()
    {
        return $this->hasOne(CustomerStatusHistory::class)->latestOfMany('changed_at');
    }
}
