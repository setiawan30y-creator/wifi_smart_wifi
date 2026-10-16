<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientCustomer;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Throwable;

class WalletController extends Controller
{
    use ResolvesClientCustomer;

    /**
     * Top-up mandiri belum tersedia: saldo hanya bertambah lewat admin atau pembayaran
     * yang sudah terverifikasi. (Dulu tombol ini menambah saldo tanpa pembayaran.)
     */
    public function topup()
    {
        $this->clientCustomer();

        return back()->with('error', 'Top-up saldo belum bisa dilakukan sendiri. Silakan hubungi admin untuk mengisi saldo.');
    }

    /**
     * Bayar tagihan internet tertua yang belum lunas memakai saldo dompet.
     */
    public function payWifi(WalletService $walletService)
    {
        $customer = $this->clientCustomer();

        $invoice = Invoice::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->orderBy('year')
            ->orderBy('month')
            ->first();

        if (!$invoice) {
            return back()->with('success', 'Tidak ada tagihan yang perlu dibayar.');
        }

        try {
            DB::transaction(function () use ($walletService, $customer, $invoice) {
                $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->first();
                if ($locked->status !== 'unpaid') {
                    return;
                }

                $walletService->debit(
                    $customer,
                    (float) $locked->amount,
                    'Pembayaran tagihan ' . $locked->invoice_number,
                    $locked->invoice_number
                );

                $locked->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => 'wallet',
                ]);
            });
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Tagihan ' . $invoice->invoice_number . ' berhasil dibayar dari saldo.');
    }
}
