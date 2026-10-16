<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pembayaran tagihan lewat Tripay (QRIS).
 *
 * Pengamanan:
 *  - Pelanggan (role client) hanya bisa membayar tagihan miliknya sendiri.
 *  - Tagihan gabungan harus milik satu pelanggan dan masih belum lunas.
 *  - Callback: tanda tangan dicek dengan hash_equals, merchant_ref & nominal dicocokkan,
 *    dan pemrosesan idempoten (callback ganda tidak memproses ulang).
 */
class TripayController extends Controller
{
    private const SANDBOX_URL = 'https://tripay.co.id/api-sandbox/transaction/create';
    private const PRODUCTION_URL = 'https://tripay.co.id/api/transaction/create';

    public function requestTransaction(Invoice $invoice)
    {
        $this->ensureCanPay(collect([$invoice]));

        if ($invoice->status !== 'unpaid') {
            return back()->with('error', 'Tagihan ini sudah lunas atau tidak bisa dibayar.');
        }

        $config = $this->config((int) $invoice->tenant_id);
        if (!$config) {
            return back()->with('error', 'Payment Gateway belum dikonfigurasi. Hubungi Admin.');
        }

        $customer = $invoice->customer;
        $amount = (int) $invoice->amount;
        $merchantRef = $invoice->invoice_number;

        $payload = $this->payload($config, $merchantRef, $amount, $customer, [[
            'sku' => 'INV',
            'name' => 'Tagihan Bulan ' . $invoice->month . ' / ' . $invoice->year,
            'price' => $amount,
            'quantity' => 1,
        ]]);

        try {
            $response = Http::withToken($config['api_key'])->post($config['url'], $payload);
            $result = $response->json();

            if ($response->successful() && ($result['success'] ?? false)) {
                $invoice->update([
                    'payment_ref' => $result['data']['reference'],
                    'payment_url' => $result['data']['checkout_url'],
                ]);

                return redirect($result['data']['checkout_url']);
            }

            Log::error('Tripay Request Error', ['invoice_id' => $invoice->id, 'response' => $result]);

            return back()->with('error', 'Gagal membuat transaksi: ' . ($result['message'] ?? 'Unknown error'));
        } catch (\Throwable $e) {
            Log::error('Tripay Exception', ['invoice_id' => $invoice->id, 'message' => $e->getMessage()]);

            return back()->with('error', 'Gagal terhubung ke server pembayaran.');
        }
    }

    public function requestGroupTransaction(Request $request)
    {
        $data = $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1', 'max:100'],
            'invoice_ids.*' => ['integer'],
        ]);

        $invoices = Invoice::with('customer')
            ->whereIn('id', $data['invoice_ids'])
            ->where('status', 'unpaid')
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tagihan tidak valid atau sudah dibayar.'], 422);
        }

        if ($invoices->pluck('customer_id')->unique()->count() > 1) {
            return response()->json(['success' => false, 'message' => 'Tagihan gabungan harus milik satu pelanggan.'], 422);
        }

        $this->ensureCanPay($invoices);

        $customer = $invoices->first()->customer;
        $tenantId = (int) $invoices->first()->tenant_id;

        $config = $this->config($tenantId);
        if (!$config) {
            return response()->json(['success' => false, 'message' => 'Payment Gateway belum dikonfigurasi. Hubungi Admin.'], 422);
        }

        $totalAmount = (int) $invoices->sum('amount');

        $transaction = PaymentTransaction::create([
            'tenant_id' => $tenantId,
            'customer_id' => $customer->id,
            'reference' => 'INV-GRP-' . now()->format('YmdHis') . random_int(100, 999),
            'amount' => $totalAmount,
            'invoice_ids' => $invoices->pluck('id')->values()->all(),
            'status' => 'UNPAID',
        ]);

        $orderItems = $invoices->map(fn (Invoice $inv) => [
            'sku' => 'INV-' . $inv->id,
            'name' => 'Tagihan ' . $inv->month . '/' . $inv->year,
            'price' => (int) $inv->amount,
            'quantity' => 1,
        ])->values()->all();

        $payload = $this->payload($config, $transaction->reference, $totalAmount, $customer, $orderItems);

        try {
            $response = Http::withToken($config['api_key'])->post($config['url'], $payload);
            $result = $response->json();

            if ($response->successful() && ($result['success'] ?? false)) {
                $transaction->update([
                    'payment_url' => $result['data']['checkout_url'],
                    'qr_url' => $result['data']['qr_url'] ?? null,
                ]);

                app(WaGatewayController::class)->sendInvoiceQris($transaction->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Tagihan QRIS berhasil di-generate dan dikirim ke WA pelanggan.',
                ]);
            }

            Log::error('Tripay Request Error', ['transaction_id' => $transaction->id, 'response' => $result]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi gabungan: ' . ($result['message'] ?? 'Unknown error'),
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Tripay Exception', ['transaction_id' => $transaction->id, 'message' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Gagal terhubung ke server pembayaran.'], 500);
        }
    }

    /**
     * Callback dari Tripay (route publik, tanpa CSRF).
     */
    public function callback(Request $request)
    {
        $json = $request->getContent();
        $data = json_decode($json);

        if (!$data || !isset($data->reference)) {
            return response()->json(['success' => false, 'message' => 'Invalid data'], 400);
        }

        if (str_starts_with((string) ($data->merchant_ref ?? ''), 'INV-GRP-')) {
            return $this->handleGroupCallback($request, $json, $data);
        }

        return $this->handleInvoiceCallback($request, $json, $data);
    }

    private function handleGroupCallback(Request $request, string $json, object $data)
    {
        $transaction = PaymentTransaction::withoutGlobalScopes()
            ->where('reference', $data->merchant_ref)
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        if ($error = $this->verifyCallback($request, $json, (int) $transaction->tenant_id)) {
            return $error;
        }

        if ($request->header('x-callback-event') !== 'payment_status') {
            return response()->json(['success' => true]);
        }

        $status = strtoupper((string) ($data->status ?? ''));

        if ($status === 'PAID' && !$this->amountMatches($data, (int) $transaction->amount)) {
            Log::warning('Tripay callback: nominal tidak cocok.', ['reference' => $transaction->reference, 'data' => $data]);

            return response()->json(['success' => false, 'message' => 'Amount mismatch'], 400);
        }

        DB::transaction(function () use ($transaction, $status) {
            $locked = PaymentTransaction::withoutGlobalScopes()->whereKey($transaction->id)->lockForUpdate()->first();

            if ($locked->status === 'PAID') {
                return;
            }

            if ($status === 'PAID') {
                $locked->update(['status' => 'PAID']);

                Invoice::withoutGlobalScopes()
                    ->where('tenant_id', $locked->tenant_id)
                    ->whereIn('id', $locked->invoice_ids ?? [])
                    ->where('status', 'unpaid')
                    ->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'payment_method' => 'QRIS',
                    ]);
            } elseif (in_array($status, ['EXPIRED', 'FAILED'], true)) {
                $locked->update(['status' => $status]);
            }
        });

        return response()->json(['success' => true]);
    }

    private function handleInvoiceCallback(Request $request, string $json, object $data)
    {
        $invoice = Invoice::withoutGlobalScopes()->where('payment_ref', $data->reference)->first();

        if (!$invoice || (isset($data->merchant_ref) && $data->merchant_ref !== $invoice->invoice_number)) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        if ($error = $this->verifyCallback($request, $json, (int) $invoice->tenant_id)) {
            return $error;
        }

        if ($request->header('x-callback-event') !== 'payment_status') {
            return response()->json(['success' => true]);
        }

        $status = strtoupper((string) ($data->status ?? ''));

        if ($status === 'PAID' && !$this->amountMatches($data, (int) $invoice->amount)) {
            Log::warning('Tripay callback: nominal tidak cocok.', ['invoice_id' => $invoice->id, 'data' => $data]);

            return response()->json(['success' => false, 'message' => 'Amount mismatch'], 400);
        }

        DB::transaction(function () use ($invoice, $status) {
            $locked = Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->first();

            if ($locked->status === 'paid') {
                return;
            }

            if ($status === 'PAID') {
                $locked->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => 'QRIS',
                ]);
            } elseif (in_array($status, ['EXPIRED', 'FAILED'], true)) {
                $locked->update(['payment_url' => null, 'payment_ref' => null]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Mengembalikan response error jika tanda tangan callback tidak valid.
     */
    private function verifyCallback(Request $request, string $json, int $tenantId)
    {
        $privateKey = Setting::forTenant($tenantId)->where('key', 'tripay_private_key')->value('value');

        if (!$privateKey) {
            return response()->json(['success' => false, 'message' => 'Merchant not configured'], 400);
        }

        $expected = hash_hmac('sha256', $json, $privateKey);
        $given = (string) $request->header('x-callback-signature', '');

        if (!hash_equals($expected, $given)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        return null;
    }

    /**
     * Nominal yang dibayar (tanpa biaya yang dibebankan ke pelanggan) harus sama dengan tagihan.
     */
    private function amountMatches(object $data, int $expected): bool
    {
        if (!isset($data->total_amount)) {
            return false;
        }

        $paid = (int) $data->total_amount - (int) ($data->fee_customer ?? 0);

        return $paid === $expected;
    }

    /**
     * Pelanggan hanya boleh membayar tagihan miliknya sendiri.
     */
    private function ensureCanPay($invoices): void
    {
        $user = auth()->user();

        if ($user->role !== 'client') {
            return;
        }

        $customer = Customer::forUser($user);

        abort_unless(
            $customer && $invoices->every(fn (Invoice $inv) => (int) $inv->customer_id === (int) $customer->id),
            403,
            'Anda hanya dapat membayar tagihan milik Anda sendiri.'
        );
    }

    private function config(int $tenantId): ?array
    {
        $settings = Setting::forTenant($tenantId)
            ->whereIn('key', ['tripay_api_key', 'tripay_private_key', 'tripay_merchant_code', 'tripay_environment'])
            ->pluck('value', 'key');

        if (empty($settings['tripay_api_key']) || empty($settings['tripay_private_key']) || empty($settings['tripay_merchant_code'])) {
            return null;
        }

        return [
            'api_key' => $settings['tripay_api_key'],
            'private_key' => $settings['tripay_private_key'],
            'merchant_code' => $settings['tripay_merchant_code'],
            'url' => ($settings['tripay_environment'] ?? 'sandbox') === 'production' ? self::PRODUCTION_URL : self::SANDBOX_URL,
        ];
    }

    private function payload(array $config, string $merchantRef, int $amount, Customer $customer, array $orderItems): array
    {
        return [
            'method' => 'QRIS',
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email ?: 'noemail@domain.com',
            'customer_phone' => $customer->phone ?: '080000000000',
            'order_items' => $orderItems,
            'return_url' => route('invoices.index'),
            'expired_time' => time() + (24 * 60 * 60),
            'signature' => hash_hmac('sha256', $config['merchant_code'] . $merchantRef . $amount, $config['private_key']),
        ];
    }
}
