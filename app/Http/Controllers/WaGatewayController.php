<?php

namespace App\Http\Controllers;

use App\Support\Phone;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaGatewayController extends Controller
{
    /**
     * Send WA message via Fonnte
     */
    public function send(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $result = $this->sendDirect($request->phone, $request->message);

        return response()->json($result, $result['success'] ? 200 : ($result['status'] ?? 400));
    }

    public function sendDirect(string $phone, string $message): array
    {
        $gateway = $this->gatewayConfig();
        $token = $gateway['token'];

        if (empty($token)) {
            Log::warning('WA gateway token is empty for WA send.', [
                'tenant_id' => session('tenant_id') ?: auth()->user()?->tenant_id,
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
            $payload = [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ];

            $response = $gateway['provider'] === 'fonnte'
                ? $this->postToFonnte($gateway, $payload)
                : $this->postToCustomGateway($gateway, $phone, $message);

            $result = $response->json();
            $isSuccess = $this->gatewayResponseSuccessful($response, $result, $gateway);

            if ($isSuccess) {
                return [
                    'success' => true,
                    'message' => 'Pesan terkirim ke ' . $phone,
                    'data' => $result,
                    'status' => 200,
                ];
            } else {
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
            }
        } catch (\Exception $e) {
            Log::error('WA gateway connection error.', [
                'target' => $phone,
                'provider' => $gateway['provider'],
                'error' => $e->getMessage(),
            ]);

            $message = $this->friendlyConnectionError($e->getMessage());

            return [
                'success' => false,
                'message' => $message,
                'technical_message' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer'],
            'message' => ['required', 'string', 'max:60000'],
            'delay' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $gateway = $this->gatewayConfig();
        $token = $gateway['token'];

        if (empty($token)) {
            Log::warning('WA gateway token is empty for WA broadcast.', [
                'tenant_id' => session('tenant_id') ?: auth()->user()?->tenant_id,
                'user_id' => auth()->id(),
                'provider' => $gateway['provider'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token WA Gateway belum dikonfigurasi di Pengaturan Perusahaan.'
            ], 400);
        }

        $customers = Customer::with(['odp', 'package', 'latestInvoice'])
            ->whereIn('id', $data['customer_ids'])
            ->get()
            ->filter(fn (Customer $customer) => filled($customer->phone));

        if ($customers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada pelanggan dengan nomor WA yang valid.'
            ], 422);
        }

        if ($gateway['provider'] !== 'fonnte') {
            return $this->broadcastViaCustomGateway($gateway, $customers, $data);
        }

        $targets = [];

        foreach ($customers as $customer) {
            $latestInvoice = $customer->latestInvoice;
            $phone = Phone::normalize($customer->phone);

            if (!$phone) {
                continue;
            }

            $targets[] = implode('|', [
                $phone,
                $this->fonnteVariable($customer->name),
                $this->fonnteVariable($customer->odp->name ?? 'Tidak Terhubung'),
                $this->fonnteVariable($latestInvoice->invoice_number ?? 'INV-xxxx'),
                $this->fonnteVariable($latestInvoice ? $this->monthName((int) $latestInvoice->month) : now()->translatedFormat('F')),
                $this->fonnteVariable($latestInvoice ? 'Rp ' . number_format((int) $latestInvoice->amount, 0, ',', '.') : ($customer->package ? 'Rp ' . number_format((int) $customer->package->price, 0, ',', '.') : '0')),
            ]);
        }

        if (empty($targets)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor pelanggan belum valid untuk dikirim.'
            ], 422);
        }

        $message = $this->prepareBroadcastTemplate($data['message']);

        try {
            $response = $this->postToFonnte($gateway, [
                'target' => implode(',', $targets),
                'message' => $message,
                'delay' => (string) ($data['delay'] ?? 2),
                'countryCode' => '0',
            ]);

            $result = $response->json();
            $isSuccess = $this->gatewayResponseSuccessful($response, $result, $gateway);

            if (!$isSuccess) {
                Log::warning('Fonnte WA broadcast failed.', [
                    'target_count' => count($targets),
                    'http_status' => $response->status(),
                    'response' => $result ?: $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['reason'] ?? $result['message'] ?? 'Broadcast gagal dikirim ke Fonnte.',
                    'data' => $result,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Broadcast berhasil diproses untuk ' . count($targets) . ' pelanggan.',
                'sent_count' => count($targets),
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Fonnte WA broadcast connection error.', [
                'target_count' => count($targets),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error koneksi ke Fonnte: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send QRIS image and Invoice details via Fonnte
     */
    public function sendInvoiceQris($transactionId)
    {
        $transaction = \App\Models\PaymentTransaction::with('customer')->find($transactionId);
        
        if (!$transaction || !$transaction->customer || !$transaction->qr_url) {
            return false;
        }

        $gateway = $this->gatewayConfig();
        $token = $gateway['token'];
        if (empty($token)) {
            \Illuminate\Support\Facades\Log::error('Fonnte token not configured for QRIS WA.');
            return false;
        }

        $customer = $transaction->customer;
        $phone = preg_replace('/[^0-9]/', '', $customer->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $message = "*[TAGIHAN INTERNET]*\n\n";
        $message .= "Yth. Bpk/Ibu *" . $customer->name . "*,\n\n";
        $message .= "Berikut adalah total tagihan yang perlu dilunasi:\n";
        
        // List the invoice details
        $invoices = \App\Models\Invoice::whereIn('id', $transaction->invoice_ids)->get();
        foreach ($invoices as $inv) {
            $message .= "- Bulan " . $inv->month . "/" . $inv->year . ": Rp " . number_format($inv->amount, 0, ',', '.') . "\n";
        }
        
        $message .= "\n*Total: Rp " . number_format($transaction->amount, 0, ',', '.') . "*\n\n";
        $message .= "Mohon scan *Barcode QRIS* pada gambar di atas untuk melakukan pembayaran. Pembayaran Anda akan diverifikasi secara otomatis oleh sistem.\n\nTerima kasih!";

        try {
            $response = $gateway['provider'] === 'fonnte'
                ? $this->postToFonnte($gateway, [
                'target' => $phone,
                'message' => $message,
                'url' => $transaction->qr_url,
                'countryCode' => '62',
            ])
                : $this->postToCustomGateway($gateway, $phone, $message);

            $result = $response->json();
            $isSuccess = $this->gatewayResponseSuccessful($response, $result, $gateway);

            if (!$isSuccess) {
                Log::warning('Fonnte QRIS WA send failed.', [
                    'target' => $phone,
                    'http_status' => $response->status(),
                    'response' => $result ?: $response->body(),
                ]);
            }

            return $isSuccess;
        } catch (\Exception $e) {
            Log::error('Failed to send QRIS via Fonnte: ' . $e->getMessage());
            return false;
        }
    }

    private function fonnteToken(): ?string
    {
        return $this->gatewayConfig()['token'];
    }

    private function gatewayConfig(): array
    {
        $tenantId = session('tenant_id') ?: auth()->user()?->tenant_id;
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

        return [
            'provider' => strtolower((string) $setting('wa_gateway_provider', 'fonnte')),
            'endpoint' => (string) $setting('wa_gateway_endpoint', 'https://api.fonnte.com/send'),
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

    private function decodeExtraPayload(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function authHeaders(array $gateway): array
    {
        $headers = [];
        $header = trim($gateway['auth_header'] ?? '');
        $token = trim($gateway['token'] ?? '');

        if ($header !== '' && $token !== '') {
            $prefix = trim($gateway['auth_prefix'] ?? '');
            $headers[$header] = $prefix !== '' ? $prefix . ' ' . $token : $token;
        }

        return $headers;
    }

    private function gatewayResponseSuccessful($response, ?array $result, array $gateway): bool
    {
        if (!$response->successful()) {
            return false;
        }

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

    private function postToFonnte(array $gateway, array $payload)
    {
        $payload = array_merge($gateway['extra_payload'], $payload);

        return Http::withHeaders($this->authHeaders($gateway))
            ->connectTimeout(15)
            ->timeout(30)
            ->send('POST', $gateway['endpoint'] ?: 'https://api.fonnte.com/send', [
                'multipart' => collect($payload)->map(fn ($value, $key) => [
                    'name' => $key,
                    'contents' => (string) $value,
                ])->values()->all(),
            ]);
    }

    private function postToCustomGateway(array $gateway, string $phone, string $message)
    {
        $payload = array_merge($gateway['extra_payload'], [
            $gateway['target_field'] ?: 'target' => $phone,
            $gateway['message_field'] ?: 'message' => $message,
        ]);

        $request = Http::withHeaders($this->authHeaders($gateway))
            ->connectTimeout(15)
            ->timeout(30);
        $endpoint = $gateway['endpoint'];

        return match ($gateway['payload_format']) {
            'json' => $request->asJson()->post($endpoint, $payload),
            'form' => $request->asForm()->post($endpoint, $payload),
            default => $request->send('POST', $endpoint, [
                'multipart' => collect($payload)->map(fn ($value, $key) => [
                    'name' => $key,
                    'contents' => (string) $value,
                ])->values()->all(),
            ]),
        };
    }

    private function friendlyConnectionError(string $error): string
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

    private function broadcastViaCustomGateway(array $gateway, $customers, array $data)
    {
        $sent = 0;
        $failed = 0;
        $firstError = null;
        $delay = max(1, (int) ($data['delay'] ?? 2));

        foreach ($customers as $customer) {
            $phone = Phone::normalize($customer->phone);
            if (!$phone) {
                $failed++;
                continue;
            }

            $message = $this->renderCustomerMessage($data['message'], $customer);

            try {
                $response = $this->postToCustomGateway($gateway, $phone, $message);
                $result = $response->json();

                if ($this->gatewayResponseSuccessful($response, $result, $gateway)) {
                    $sent++;
                } else {
                    $failed++;
                    $firstError ??= $result['reason'] ?? $result['message'] ?? $response->body();
                }
            } catch (\Throwable $e) {
                $failed++;
                $firstError ??= $e->getMessage();
            }

            if ($delay > 0 && $customers->count() > 1) {
                sleep($delay);
            }
        }

        return response()->json([
            'success' => $sent > 0,
            'message' => $sent > 0
                ? "Broadcast diproses. Berhasil: {$sent}, gagal: {$failed}."
                : 'Broadcast gagal. ' . ($firstError ?: 'Cek konfigurasi WA Gateway.'),
            'sent_count' => $sent,
            'failed_count' => $failed,
            'error' => $firstError,
        ], $sent > 0 ? 200 : 400);
    }

    private function renderCustomerMessage(string $message, Customer $customer): string
    {
        $latestInvoice = $customer->latestInvoice;

        return strtr($message, [
            '{nama_pelanggan}' => $customer->name,
            '{nama}' => $customer->name,
            '{nama_odp}' => $customer->odp->name ?? 'Tidak Terhubung',
            '{odp}' => $customer->odp->name ?? 'Tidak Terhubung',
            '{nomor_tagihan}' => $latestInvoice->invoice_number ?? 'INV-xxxx',
            '{no_invoice}' => $latestInvoice->invoice_number ?? 'INV-xxxx',
            '{bulan}' => $latestInvoice ? $this->monthName((int) $latestInvoice->month) : now()->translatedFormat('F'),
            '{tanggal_jatuh_tempo}' => $latestInvoice ? \Illuminate\Support\Carbon::create((int) $latestInvoice->year, (int) $latestInvoice->month, max(1, min((int) ($customer->billing_day ?: 1), \Illuminate\Support\Carbon::create((int) $latestInvoice->year, (int) $latestInvoice->month, 1)->daysInMonth)))->translatedFormat('d F Y') : '-',
            '{nominal}' => $latestInvoice ? 'Rp ' . number_format((int) $latestInvoice->amount, 0, ',', '.') : ($customer->package ? 'Rp ' . number_format((int) $customer->package->price, 0, ',', '.') : '0'),
            '{jumlah}' => $latestInvoice ? 'Rp ' . number_format((int) $latestInvoice->amount, 0, ',', '.') : ($customer->package ? 'Rp ' . number_format((int) $customer->package->price, 0, ',', '.') : '0'),
        ]);
    }


    private function prepareBroadcastTemplate(string $message): string
    {
        return strtr($message, [
            '{nama_pelanggan}' => '{name}',
            '{nama}' => '{name}',
            '{nama_odp}' => '{var1}',
            '{odp}' => '{var1}',
            '{nomor_tagihan}' => '{var2}',
            '{no_invoice}' => '{var2}',
            '{bulan}' => '{var3}',
            '{nominal}' => '{var4}',
            '{jumlah}' => '{var4}',
        ]);
    }

    private function fonnteVariable(string $value): string
    {
        return str_replace(['|', ','], [' ', ' '], $value);
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$month] ?? (string) $month;
    }
}
