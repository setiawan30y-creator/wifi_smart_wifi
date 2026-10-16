<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Phone;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya tempat untuk membaca konfigurasi WA Gateway (Fonnte / custom)
 * dan mengirim pesan WhatsApp.
 *
 * Sebelumnya logika ini ada di dalam WaGatewayController dan dipanggil dari
 * InvoiceService / InvoiceController lewat app(WaGatewayController::class).
 * Perilakunya dipertahankan sama persis.
 */
class WhatsAppGateway
{
    public const DEFAULT_ENDPOINT = 'https://api.fonnte.com/send';

    private ?array $config = null;

    /**
     * Kirim satu pesan teks. Mengembalikan array:
     * ['success' => bool, 'message' => string, 'status' => int, 'data' => ?array, ...]
     */
    public function send(string $phone, string $message): array
    {
        $gateway = $this->config();

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
            $response = $gateway['provider'] === 'fonnte'
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
                'message' => $result['reason'] ?? $result['message'] ?? 'Gagal mengirim pesan',
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

            // CATATAN: fallback ini mengambil nilai dari tenant lain jika tenant
            // ini belum mengisi pengaturan. Dipertahankan apa adanya dulu;
            // akan diperbaiki di tahap keamanan (lihat audit).
            $value = Setting::withoutGlobalScopes()
                ->where('key', $key)
                ->whereNotNull('value')
                ->where('value', '<>', '')
                ->orderByRaw('tenant_id IS NULL')
                ->orderBy('tenant_id')
                ->value('value');

            return filled($value) ? $value : $default;
        };

        $fallbackToken = $setting('fonnte_token', '');

        return $this->config = [
            'provider' => strtolower((string) $setting('wa_gateway_provider', 'fonnte')),
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
            $gateway['target_field'] ?: 'target' => $phone,
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
        $tenantId = session('tenant_id') ?: auth()->user()?->tenant_id;

        return $tenantId ? (int) $tenantId : null;
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
