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
        static::saved(fn () => \App\Support\Permissions::flush());
        static::deleted(fn () => \App\Support\Permissions::flush());
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
}
