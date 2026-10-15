<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Services\WalletService;
use Exception;

class WalletController extends Controller
{
    public function topup(Request $request, WalletService $walletService)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
        ]);

        $customer = Customer::first(); // Demo customer
        
        try {
            // For demo, we just auto-approve the topup.
            // In a real app, this would redirect to Tripay/Midtrans checkout URL.
            $walletService->credit($customer, $request->amount, 'Topup Saldo (Simulasi)');
            return back()->with('success', 'Top-up saldo sebesar Rp ' . number_format($request->amount, 0, ',', '.') . ' berhasil ditambahkan!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function payWifi(Request $request, WalletService $walletService)
    {
        $customer = Customer::first(); // Demo customer
        
        try {
            // For demo, assume tagihan is Rp 150.000
            $tagihan = 150000;
            $walletService->debit($customer, $tagihan, 'Pembayaran Tagihan WiFi Bulanan');
            return back()->with('success', 'Pembayaran WiFi berhasil dipotong dari saldo Anda.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
