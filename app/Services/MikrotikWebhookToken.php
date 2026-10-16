<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Token rahasia per tenant untuk webhook status klien MikroTik (PPP up/down).
 * Token disimpan di setting tenant "mikrotik_webhook_token".
 */
class MikrotikWebhookToken
{
    public const SETTING_KEY = 'mikrotik_webhook_token';

    /** Token tenant aktif; dibuat otomatis bila belum ada. */
    public function current(): string
    {
        $token = Setting::get(self::SETTING_KEY);

        return filled($token) ? $token : $this->regenerate();
    }

    public function regenerate(): string
    {
        $token = Str::random(40);
        Setting::set(self::SETTING_KEY, $token);

        return $token;
    }

    /** Tenant pemilik token, atau null jika token tidak dikenal. */
    public function tenantIdFor(?string $token): ?int
    {
        $token = trim((string) $token);

        if (strlen($token) < 20) {
            return null;
        }

        $setting = Setting::withoutGlobalScopes()
            ->where('key', self::SETTING_KEY)
            ->where('value', $token)
            ->first(['tenant_id', 'value']);

        return $setting && hash_equals((string) $setting->value, $token) ? (int) $setting->tenant_id : null;
    }

    public function url(): string
    {
        return route('mikrotik.client-status', ['token' => $this->current()]);
    }
}
