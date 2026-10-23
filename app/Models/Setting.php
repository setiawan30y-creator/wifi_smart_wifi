<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class Setting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','key', 'value'];

    protected static function booted(): void
    {
        static::saved(function () { \App\Support\Permissions::flush(); \App\Support\Theme::flush(); });
        static::deleted(function () { \App\Support\Permissions::flush(); \App\Support\Theme::flush(); });
    }

    /**
     * Get a setting value by key with fallback.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, $value): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) || is_object($value) ? json_encode($value) : $value]
        );
    }

    /**
     * Pengaturan tingkat platform/SaaS (tenant_id NULL), dikelola oleh pemilik SaaS.
     */
    public static function platform(): array
    {
        return \Illuminate\Support\Facades\DB::table('settings')
            ->whereNull('tenant_id')
            ->pluck('value', 'key')
            ->toArray();
    }

    public static function setPlatform(string $key, $value): void
    {
        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['tenant_id' => null, 'key' => $key],
            ['value' => is_array($value) || is_object($value) ? json_encode($value) : $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
