<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\WhatsAppGateway;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaGatewayController extends Controller
{
    /**
     * Send WA message via Fonnte
     */
    /**
     * Kirim satu pesan WA (dipanggil dari halaman). Logika kirim ada di App\Services\WhatsAppGateway.
     */
    public function send(Request $request, WhatsAppGateway $gateway)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $result = $gateway->send($request->phone, $request->message);

        return response()->json($result, $result['success'] ? 200 : ($result['status'] ?? 400));
    }

    public function broadcast(Request $request, WhatsAppGateway $gateway)
    {
        $data = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer'],
            'message' => ['required', 'string', 'max:60000'],
            'delay' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        if (!$gateway->hasToken()) {
            Log::warning('WA gateway token is empty for WA broadcast.', [
                'tenant_id' => session('tenant_id') ?: auth()->user()?->tenant_id,
                'user_id' => auth()->id(),
                'provider' => $gateway->config()['provider'],
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

        if (!$gateway->isFonnte()) {
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
            $response = $gateway->postToFonnte([
                'target' => implode(',', $targets),
                'message' => $message,
                'delay' => (string) ($data['delay'] ?? 2),
                'countryCode' => '0',
            ]);

            $result = $response->json();
            $isSuccess = $gateway->isSuccessful($response, $result);

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

        $gateway = app(WhatsAppGateway::class);
        if (!$gateway->hasToken()) {
            Log::error('Fonnte token not configured for QRIS WA.');
            return false;
        }

        $customer = $transaction->customer;
        $phone = (string) Phone::normalize($customer->phone);

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
            $response = $gateway->isFonnte()
                ? $gateway->postToFonnte([
                    'target' => $phone,
                    'message' => $message,
                    'url' => $transaction->qr_url,
                    'countryCode' => '62',
                ])
                : $gateway->postToCustomGateway($phone, $message);

            $result = $response->json();
            $isSuccess = $gateway->isSuccessful($response, $result);

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

    private function broadcastViaCustomGateway(WhatsAppGateway $gateway, $customers, array $data)
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
                $response = $gateway->postToCustomGateway($phone, $message);
                $result = $response->json();

                if ($gateway->isSuccessful($response, $result)) {
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
