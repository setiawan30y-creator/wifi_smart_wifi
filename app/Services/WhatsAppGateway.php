<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Phone;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya tempat untuk membaca konfigurasi WA Gateway (ALMARA Gateway WA / Fonnte / custom)
 * dan mengirim pesan WhatsApp.
 *
 * Provider "almara": cukup isi URL aplikasi ALMARA Gateway WA + token perangkat
 * (menu Perangkat → Token API). Header, field & format otomatis (gaya Fonnte: /api/send).
 *
 * Sebelumnya logika ini ada di dalam WaGatewayController dan dipanggil dari
 * InvoiceService / InvoiceController lewat app(WaGatewayController::class).
 * Perilakunya dipertahankan sama persis.
 */
class WhatsAppGateway
{
    public const DEFAULT_ENDPOINT = 'https://api.fonnte.com/send';

    /**
     * Jasa WA gateway siap pakai (selain Fonnte & ALMARA). Pengguna cukup mengisi
     * URL server (bila perlu), token, dan nama instance/session; format kirim otomatis.
     *
     * key => [
     *   label, url bawaan|null, akhiran path yang ditambahkan ke URL server,
     *   header token, prefix token, format payload, field nomor, akhiran nomor,
     *   field pesan, payload tetap, success key, success value, petunjuk token
     * ]
     */
    public const PRESETS = [
        'wablas' => [
            'label' => 'Wablas',
            'default_url' => null,
            'path' => '/api/send-message',
            'auth_header' => 'Authorization', 'auth_prefix' => '',
            'payload_format' => 'form',
            'target_field' => 'phone', 'target_suffix' => '',
            'message_field' => 'message',
            'extra_payload' => [],
            'success_key' => 'status', 'success_value' => 'true',
            'url_hint' => 'URL server Wablas Anda, mis. https://tegal.wablas.com',
            'token_hint' => 'Token perangkat. Jika di dashboard Wablas ada Secret Key, isi dengan format TOKEN.SECRETKEY',
            'instance_label' => null,
        ],
        'starsender' => [
            'label' => 'StarSender',
            'default_url' => 'https://api.starsender.online',
            'path' => '/api/send',
            'auth_header' => 'Authorization', 'auth_prefix' => '',
            'payload_format' => 'json',
            'target_field' => 'to', 'target_suffix' => '',
            'message_field' => 'body',
            'extra_payload' => ['messageType' => 'text'],
            'success_key' => 'success', 'success_value' => 'true',
            'url_hint' => 'Boleh dikosongkan (otomatis https://api.starsender.online)',
            'token_hint' => 'API Key perangkat dari dashboard StarSender',
            'instance_label' => null,
        ],
        'evolution' => [
            'label' => 'Evolution API (server sendiri)',
            'default_url' => null,
            'path' => '/message/sendText/{instance}',
            'auth_header' => 'apikey', 'auth_prefix' => '',
            'payload_format' => 'json',
            'target_field' => 'number', 'target_suffix' => '',
            'message_field' => 'text',
            'extra_payload' => [],
            'success_key' => '', 'success_value' => '',
            'url_hint' => 'URL server Evolution API, mis. https://evo.domainanda.com',
            'token_hint' => 'API key instance (atau global API key)',
            'instance_label' => 'Nama Instance',
        ],
        'waha' => [
            'label' => 'WAHA (server sendiri)',
            'default_url' => null,
            'path' => '/api/sendText',
            'auth_header' => 'X-Api-Key', 'auth_prefix' => '',
            'payload_format' => 'json',
            'target_field' => 'chatId', 'target_suffix' => '@c.us',
            'message_field' => 'text',
            'extra_payload' => ['session' => '{instance}'],
            'success_key' => '', 'success_value' => '',
            'url_hint' => 'URL server WAHA, mis. https://waha.domainanda.com',
            'token_hint' => 'Nilai WAHA_API_KEY server Anda',
            'instance_label' => 'Nama Session',
        ],
    ];

    /** Semua pilihan provider untuk halaman pengaturan. */
    public static function providers(): array
    {
        return ['almara' => 'ALMARA Gateway WA', 'fonnte' => 'Fonnte']
            + array_map(fn ($preset) => $preset['label'], self::PRESETS)
            + ['custom' => 'Custom / Gateway Lain'];
    }

    /**
     * URL kirim untuk provider siap pakai: URL server (tanpa path) + path provider.
     */
    public static function presetEndpoint(string $provider, string $url, string $instance = ''): string
    {
        $preset = self::PRESETS[$provider] ?? null;
        if (!$preset) {
            return '';
        }

        $base = rtrim(trim($url), '/') ?: (string) $preset['default_url'];
        if ($base === '') {
            return '';
        }

        $path = $preset['path'];
        $staticPath = explode('/{', $path)[0];

        // Pengguna kadang menempel URL lengkap; buang path yang sudah ada agar tidak dobel.
        $position = stripos($base, $staticPath);
        if ($position !== false && $staticPath !== '') {
            $base = substr($base, 0, $position);
        }

        return rtrim($base, '/') . str_replace('{instance}', rawurlencode($instance !== '' ? $instance : 'default'), $path);
    }

    private ?array $config = null;

    private ?int $tenantId = null;

    /**
     * Gateway untuk tenant tertentu — dipakai di konteks tanpa login
     * (scheduler, webhook MikroTik, lupa password).
     */
    public static function forTenant(?int $tenantId): static
    {
        $gateway = new static();
        $gateway->tenantId = $tenantId ?: null;

        return $gateway;
    }

    /** Endpoint kirim ALMARA dari URL apa pun yang diisi (domain saja, /api, atau /api/send). */
    public static function almaraEndpoint(string $url): string
    {
        $url = rtrim(trim($url), '/');

        if ($url === '' || str_contains($url, 'fonnte.com')) {
            return '';
        }

        foreach (['/api/v1/messages/send', '/api/send-message', '/api/send', '/api'] as $suffix) {
            if (str_ends_with(strtolower($url), $suffix)) {
                $url = substr($url, 0, -strlen($suffix));
                break;
            }
        }

        return $url . '/api/send';
    }

    /**
     * Kirim satu pesan teks. Mengembalikan array:
     * ['success' => bool, 'message' => string, 'status' => int, 'data' => ?array, ...]
     */
    public function send(string $phone, string $message): array
    {
        $gateway = $this->config();

        if ($gateway['endpoint'] === '' && ($gateway['provider'] === 'almara' || isset(self::PRESETS[$gateway['provider']]))) {
            $label = self::providers()[$gateway['provider']] ?? 'WA Gateway';

            return [
                'success' => false,
                'message' => $gateway['provider'] === 'almara'
                    ? 'URL ALMARA Gateway WA belum diisi di Pengaturan Perusahaan.'
                    : "URL server {$label} belum diisi di Pengaturan Perusahaan.",
                'status' => 400,
            ];
        }

        if (empty($gateway['token'])) {
            Log::warning('WA gateway token is empty for WA send.', [
                'tenant_id' => $this->currentTenantId(),
                'user_id' => auth()->id(),
                'provider' => $gateway['provider'],
            ]);

            return [
                'success' => false,
                'message' => 'Token WA Gateway belum dikonfigurasi di Pengaturan Perusahaan.',
                'status' => 400,
            ];
        }

        $phone = Phone::normalize($phone);

        if (!$phone) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp pelanggan belum valid.',
                'status' => 422,
            ];
        }

        try {
            $response = $this->usesFonnteFormat()
                ? $this->postToFonnte([
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62',
                ])
                : $this->postToCustomGateway($phone, $message);

            $result = $response->json();

            if ($this->isSuccessful($response, $result)) {
                return [
                    'success' => true,
                    'message' => 'Pesan terkirim ke ' . $phone,
                    'data' => $result,
                    'status' => 200,
                ];
            }

            Log::warning('WA gateway send failed.', [
                'target' => $phone,
                'provider' => $gateway['provider'],
                'http_status' => $response->status(),
                'response' => $result ?: $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $this->errorMessage($result, $response),
                'data' => $result,
                'status' => 400,
            ];
        } catch (\Exception $e) {
            Log::error('WA gateway connection error.', [
                'target' => $phone,
                'provider' => $gateway['provider'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $this->friendlyConnectionError($e->getMessage()),
                'technical_message' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Konfigurasi gateway untuk tenant yang sedang login.
     */
    public function config(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $tenantId = $this->currentTenantId();
        $setting = function (string $key, $default = null) use ($tenantId) {
            if ($tenantId) {
                $value = Setting::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('key', $key)
                    ->value('value');

                if (filled($value)) {
                    return $value;
                }
            }

            // Jika tenant belum mengisi, pakai pengaturan gateway SaaS (tenant_id NULL).
            // Tidak pernah memakai token/pengaturan milik tenant lain.
            $value = Setting::withoutGlobalScopes()
                ->whereNull('tenant_id')
                ->where('key', $key)
                ->value('value');

            return filled($value) ? $value : $default;
        };

        $fallbackToken = $setting('fonnte_token', '');
        $provider = strtolower((string) $setting('wa_gateway_provider', 'fonnte'));

        if ($provider === 'almara') {
            // ALMARA Gateway WA memakai format gaya Fonnte — hanya URL & token yang perlu diisi.
            return $this->config = [
                'provider' => 'almara',
                'endpoint' => self::almaraEndpoint((string) $setting('wa_gateway_endpoint', '')),
                'token' => trim((string) $setting('wa_gateway_token', $fallbackToken)),
                'auth_header' => 'Authorization',
                'auth_prefix' => '',
                'payload_format' => 'multipart',
                'target_field' => 'target',
                'message_field' => 'message',
                'extra_payload' => ['countryCode' => '62'],
                'success_key' => 'status',
                'success_value' => 'true',
            ];
        }

        if (isset(self::PRESETS[$provider])) {
            $preset = self::PRESETS[$provider];
            $instance = trim((string) $setting('wa_gateway_instance', '')) ?: 'default';
            $endpointSetting = (string) $setting('wa_gateway_endpoint', '');

            return $this->config = [
                'provider' => $provider,
                'endpoint' => self::presetEndpoint($provider, str_contains($endpointSetting, 'fonnte.com') ? '' : $endpointSetting, $instance),
                'token' => trim((string) $setting('wa_gateway_token', $fallbackToken)),
                'auth_header' => $preset['auth_header'],
                'auth_prefix' => $preset['auth_prefix'],
                'payload_format' => $preset['payload_format'],
                'target_field' => $preset['target_field'],
                'target_suffix' => $preset['target_suffix'],
                'message_field' => $preset['message_field'],
                'extra_payload' => array_map(fn ($value) => is_string($value) ? str_replace('{instance}', $instance, $value) : $value, $preset['extra_payload']),
                'success_key' => $preset['success_key'],
                'success_value' => $preset['success_value'],
            ];
        }

        return $this->config = [
            'provider' => $provider,
            'endpoint' => (string) $setting('wa_gateway_endpoint', self::DEFAULT_ENDPOINT),
            'token' => trim((string) $setting('wa_gateway_token', $fallbackToken)),
            'auth_header' => (string) $setting('wa_gateway_auth_header', 'Authorization'),
            'auth_prefix' => (string) $setting('wa_gateway_auth_prefix', ''),
            'payload_format' => (string) $setting('wa_gateway_payload_format', 'multipart'),
            'target_field' => (string) $setting('wa_gateway_target_field', 'target'),
            'message_field' => (string) $setting('wa_gateway_message_field', 'message'),
            'extra_payload' => $this->decodeExtraPayload((string) $setting('wa_gateway_extra_payload', '{"countryCode":"62"}')),
            'success_key' => (string) $setting('wa_gateway_success_key', 'status'),
            'success_value' => (string) $setting('wa_gateway_success_value', 'true'),
        ];
    }

    public function isFonnte(): bool
    {
        return $this->config()['provider'] === 'fonnte';
    }

    public function isAlmara(): bool
    {
        return $this->config()['provider'] === 'almara';
    }

    /** Fonnte & ALMARA menerima payload yang sama (target, message, url untuk gambar). */
    public function usesFonnteFormat(): bool
    {
        return in_array($this->config()['provider'], ['fonnte', 'almara'], true);
    }

    public function hasToken(): bool
    {
        return !empty($this->config()['token']);
    }

    /**
     * Kirim payload mentah ke Fonnte (multipart). Dipakai juga untuk broadcast & QRIS.
     */
    public function postToFonnte(array $payload): Response
    {
        $gateway = $this->config();
        $payload = array_merge($gateway['extra_payload'], $payload);

        return Http::withHeaders($this->authHeaders())
            ->connectTimeout(15)
            ->timeout(30)
            ->send('POST', $gateway['endpoint'] ?: self::DEFAULT_ENDPOINT, [
                'multipart' => $this->toMultipart($payload),
            ]);
    }

    public function postToCustomGateway(string $phone, string $message): Response
    {
        $gateway = $this->config();
        $payload = array_merge($gateway['extra_payload'], [
            $gateway['target_field'] ?: 'target' => $phone . ($gateway['target_suffix'] ?? ''),
            $gateway['message_field'] ?: 'message' => $message,
        ]);

        $request = Http::withHeaders($this->authHeaders())
            ->connectTimeout(15)
            ->timeout(30);
        $endpoint = $gateway['endpoint'];

        return match ($gateway['payload_format']) {
            'json' => $request->asJson()->post($endpoint, $payload),
            'form' => $request->asForm()->post($endpoint, $payload),
            default => $request->send('POST', $endpoint, [
                'multipart' => $this->toMultipart($payload),
            ]),
        };
    }

    public function isSuccessful(Response $response, ?array $result): bool
    {
        if (!$response->successful()) {
            return false;
        }

        $gateway = $this->config();
        $successKey = trim($gateway['success_key'] ?? '');
        if ($successKey === '') {
            return true;
        }

        $actual = data_get($result ?? [], $successKey);
        $expected = strtolower(trim((string) ($gateway['success_value'] ?? 'true')));

        return strtolower(trim((string) $actual)) === $expected
            || ($expected === 'true' && in_array($actual, [true, 1, '1'], true))
            || ($expected === '1' && in_array($actual, [true, 1, '1'], true));
    }

    public function friendlyConnectionError(string $error): string
    {
        $lower = strtolower($error);

        if (str_contains($lower, 'resolving timed out') || str_contains($lower, 'could not resolve host')) {
            return 'WA belum terkirim karena server belum bisa mengakses DNS/Internet menuju WA Gateway. Pembayaran tetap berhasil tersimpan.';
        }

        if (str_contains($lower, 'connection timed out') || str_contains($lower, 'operation timed out')) {
            return 'WA belum terkirim karena koneksi server ke WA Gateway timeout. Pembayaran tetap berhasil tersimpan.';
        }

        return 'WA belum terkirim karena koneksi ke WA Gateway sedang bermasalah. Pembayaran tetap berhasil tersimpan.';
    }

    private function currentTenantId(): ?int
    {
        if ($this->tenantId) {
            return $this->tenantId;
        }

        return app(\App\Support\Tenancy\TenantContext::class)->id()
            ?? (auth()->user()?->tenant_id ? (int) auth()->user()->tenant_id : null);
    }

    private function authHeaders(): array
    {
        $gateway = $this->config();
        $headers = [];
        $header = trim($gateway['auth_header'] ?? '');
        $token = trim($gateway['token'] ?? '');

        if ($header !== '' && $token !== '') {
            $prefix = trim($gateway['auth_prefix'] ?? '');
            $headers[$header] = $prefix !== '' ? $prefix . ' ' . $token : $token;
        }

        return $headers;
    }

    /** Pesan error dari berbagai bentuk respons gateway. */
    private function errorMessage(?array $result, Response $response): string
    {
        foreach (['reason', 'message', 'error.message', 'error', 'detail', 'response.message'] as $key) {
            $value = data_get($result ?? [], $key);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        if (!$response->successful()) {
            return 'Gateway menolak permintaan (HTTP ' . $response->status() . '). Periksa URL, token, dan nama instance/session.';
        }

        return 'Gagal mengirim pesan';
    }

    private function decodeExtraPayload(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function toMultipart(array $payload): array
    {
        return collect($payload)->map(fn ($value, $key) => [
            'name' => $key,
            'contents' => (string) $value,
        ])->values()->all();
    }
}
