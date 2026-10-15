<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class TripayController extends Controller
{
    /**
     * Request a new closed transaction for an Invoice
     */
    public function requestTransaction(Invoice $invoice)
    {
        // Must belong to the currently logged in client or admin
        if (auth()->user()->role === 'client' && $invoice->customer_id !== auth()->user()->id) {
            abort(403);
        }

        // Get Tripay Credentials
        $apiKey = Setting::get('tripay_api_key');
        $privateKey = Setting::get('tripay_private_key');
        $merchantCode = Setting::get('tripay_merchant_code');
        $environment = Setting::get('tripay_environment', 'sandbox');

        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            return back()->with('error', 'Payment Gateway belum dikonfigurasi. Hubungi Admin.');
        }

        // URL based on environment
        $url = $environment === 'production' 
            ? 'https://tripay.co.id/api/transaction/create'
            : 'https://tripay.co.id/api-sandbox/transaction/create';

        $merchantRef = $invoice->invoice_number;
        $amount = $invoice->amount;

        // Generate Signature
        $signature = hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);

        $customer = $invoice->customer;

        $data = [
            'method'         => 'QRIS', // Default to QRIS
            'merchant_ref'   => $merchantRef,
            'amount'         => $amount,
            'customer_name'  => $customer->name,
            'customer_email' => $customer->email ?? 'noemail@domain.com',
            'customer_phone' => $customer->phone ?? '080000000000',
            'order_items'    => [
                [
                    'sku'         => 'INV',
                    'name'        => 'Tagihan Bulan ' . $invoice->month . ' / ' . $invoice->year,
                    'price'       => $amount,
                    'quantity'    => 1,
                ]
            ],
            'return_url'   => route('invoices.index'),
            'expired_time' => (time() + (24 * 60 * 60)), // 24 hours
            'signature'    => $signature
        ];

        try {
            $response = Http::withToken($apiKey)->post($url, $data);
            $result = $response->json();

            if ($response->successful() && $result['success']) {
                // Save to database
                $invoice->payment_ref = $result['data']['reference'];
                $invoice->payment_url = $result['data']['checkout_url'];
                $invoice->save();

                return redirect($result['data']['checkout_url']);
            }

            Log::error('Tripay Request Error', ['response' => $result]);
            return back()->with('error', 'Gagal membuat transaksi: ' . ($result['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('Tripay Exception', ['message' => $e->getMessage()]);
            return back()->with('error', 'Gagal terhubung ke server pembayaran.');
        }
    }

    /**
     * Request a group transaction for multiple invoices
     */
    public function requestGroupTransaction(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);
        
        if (empty($invoiceIds)) {
            return back()->with('error', 'Tidak ada tagihan yang dipilih.');
        }

        $invoices = Invoice::whereIn('id', $invoiceIds)
            ->where('status', 'unpaid')
            ->get();

        if ($invoices->isEmpty()) {
            return back()->with('error', 'Tagihan tidak valid atau sudah dibayar.');
        }

        $customer = $invoices->first()->customer;
        $tenantId = $invoices->first()->tenant_id;
        $totalAmount = $invoices->sum('amount');

        // Create PaymentTransaction Record
        $transaction = \App\Models\PaymentTransaction::create([
            'tenant_id' => $tenantId,
            'customer_id' => $customer->id,
            'reference' => 'INV-GRP-' . time() . rand(100,999),
            'amount' => $totalAmount,
            'invoice_ids' => $invoiceIds,
            'status' => 'UNPAID',
        ]);

        // Get Tripay Credentials
        $apiKey = Setting::get('tripay_api_key');
        $privateKey = Setting::get('tripay_private_key');
        $merchantCode = Setting::get('tripay_merchant_code');
        $environment = Setting::get('tripay_environment', 'sandbox');

        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            return back()->with('error', 'Payment Gateway belum dikonfigurasi. Hubungi Admin.');
        }

        $url = $environment === 'production' 
            ? 'https://tripay.co.id/api/transaction/create'
            : 'https://tripay.co.id/api-sandbox/transaction/create';

        $signature = hash_hmac('sha256', $merchantCode . $transaction->reference . $totalAmount, $privateKey);

        $orderItems = [];
        foreach ($invoices as $inv) {
            $orderItems[] = [
                'sku'         => 'INV-' . $inv->id,
                'name'        => 'Tagihan ' . $inv->month . '/' . $inv->year,
                'price'       => $inv->amount,
                'quantity'    => 1,
            ];
        }

        $data = [
            'method'         => 'QRIS',
            'merchant_ref'   => $transaction->reference,
            'amount'         => $totalAmount,
            'customer_name'  => $customer->name,
            'customer_email' => $customer->email ?? 'noemail@domain.com',
            'customer_phone' => $customer->phone ?? '080000000000',
            'order_items'    => $orderItems,
            'return_url'     => route('invoices.index'),
            'expired_time'   => (time() + (24 * 60 * 60)),
            'signature'      => $signature
        ];

        try {
            $response = Http::withToken($apiKey)->post($url, $data);
            $result = $response->json();

            if ($response->successful() && $result['success']) {
                $transaction->payment_url = $result['data']['checkout_url'];
                $transaction->qr_url = $result['data']['qr_url'] ?? null;
                $transaction->save();

                // Fire WA Gateway logic here if needed
                app(\App\Http\Controllers\WaGatewayController::class)->sendInvoiceQris($transaction->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Tagihan QRIS berhasil di-generate dan dikirim ke WA pelanggan.'
                ]);
            }

            Log::error('Tripay Request Error', ['response' => $result]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi gabungan: ' . ($result['message'] ?? 'Unknown error')
            ], 400);

        } catch (\Exception $e) {
            Log::error('Tripay Exception', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke server pembayaran.'
            ], 500);
        }
    }

    /**
     * Webhook Callback from Tripay
     */
    public function callback(Request $request)
    {
        $json = $request->getContent();
        $callbackSignature = $request->header('x-callback-signature');
        $data = json_decode($json);
        
        if (!$data || !isset($data->reference)) {
            return response()->json(['success' => false, 'message' => 'Invalid data'], 400);
        }

        // Handle Group Transaction
        if (str_starts_with($data->reference, 'INV-GRP-')) {
            $transaction = \App\Models\PaymentTransaction::withoutGlobalScopes()->where('reference', $data->reference)->first();
            if (!$transaction) return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);

            $privateKey = Setting::withoutGlobalScopes()
                            ->where('tenant_id', $transaction->tenant_id)
                            ->where('key', 'tripay_private_key')
                            ->value('value');

            if (!$privateKey) return response()->json(['success' => false, 'message' => 'Merchant not configured'], 400);

            $signature = hash_hmac('sha256', $json, $privateKey);
            if ($signature !== $callbackSignature) return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);

            if ($request->header('x-callback-event') === 'payment_status') {
                if ($data->status === 'PAID') {
                    $transaction->status = 'PAID';
                    $transaction->save();

                    // Mark all associated invoices as paid
                    Invoice::withoutGlobalScopes()->whereIn('id', $transaction->invoice_ids)->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'payment_method' => 'QRIS',
                    ]);
                } elseif ($data->status === 'EXPIRED' || $data->status === 'FAILED') {
                    $transaction->status = $data->status;
                    $transaction->save();
                }
            }
            return response()->json(['success' => true]);
        }

        // Handle Single Legacy Invoice
        $invoice = Invoice::withoutGlobalScopes()->where('payment_ref', $data->reference)->first();

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $privateKey = Setting::withoutGlobalScopes()
                        ->where('tenant_id', $invoice->tenant_id)
                        ->where('key', 'tripay_private_key')
                        ->value('value');

        if (!$privateKey) {
            return response()->json(['success' => false, 'message' => 'Merchant not configured'], 400);
        }

        $signature = hash_hmac('sha256', $json, $privateKey);

        if ($signature !== $callbackSignature) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        if ($request->header('x-callback-event') === 'payment_status') {
            if ($data->status === 'PAID') {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
                $invoice->payment_method = 'QRIS';
                $invoice->save();
            } elseif ($data->status === 'EXPIRED' || $data->status === 'FAILED') {
                $invoice->payment_url = null;
                $invoice->payment_ref = null;
                $invoice->save();
            }
        }

        return response()->json(['success' => true]);
    }
}
