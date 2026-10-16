<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Router extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'username',
        'password',
        'description',
        'status',
    ];

    /**
     * Router MikroTik yang dipakai tenant aktif (router online pertama).
     * Satu-satunya tempat untuk memilih router; dulu ditulis berulang di banyak file.
     */
    public static function active(): ?self
    {
        return static::query()->where('status', 'online')->orderBy('id')->first();
    }

    public static function normalizeHost(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $candidate = preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $value)
            ? $value
            : 'tcp://' . $value;

        $host = parse_url($candidate, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return trim($host, " \t\n\r\0\x0B[]");
        }

        return rtrim(preg_replace('/\/.*$/', '', $value), '/');
    }

    public function setIpAddressAttribute($value)
    {
        $this->attributes['ip_address'] = self::normalizeHost($value);
    }

    /**
     * Mutator to encrypt password before saving
     */
    public function setPasswordAttribute($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['password'] = Crypt::encryptString($value);
    }

    /**
     * Accessor to decrypt password when retrieved
     */
    public function getPasswordAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }
}
