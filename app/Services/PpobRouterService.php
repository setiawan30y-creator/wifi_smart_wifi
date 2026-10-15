<?php

namespace App\Services;

use App\Models\PpobProduct;
use App\Models\PpobSupplier;
use App\Models\PpobSupplierProduct;
use App\Models\PpobTransaction;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Facades\Log;

class PpobRouterService
{
    /**
     * Get the cheapest active supplier for a given product.
     * 
     * @param int $productId
     * @return PpobSupplierProduct|null
     */
    public function getCheapestSupplier($productId)
    {
        return PpobSupplierProduct::where('product_id', $productId)
            ->where('is_available', true)
            ->whereHas('supplier', function ($q) {
                $q->where('is_active', true);
            })
            ->orderBy('buy_price', 'asc')
            ->first();
    }

    /**
     * Process a PPOB purchase by routing it to the cheapest supplier.
     * 
     * @param Customer $customer
     * @param int $productId
     * @param string $customerNumber (Phone number / Meter number)
     * @return PpobTransaction
     * @throws Exception
     */
    public function purchase(Customer $customer, $productId, $customerNumber)
    {
        $product = PpobProduct::findOrFail($productId);
        $tenant = $customer->tenant;
        
        // Find the cheapest active supplier for this product
        $supplierProduct = $this->getCheapestSupplier($product->id);
        
        if (!$supplierProduct) {
            throw new Exception("Produk ini sedang gangguan atau tidak tersedia dari semua suplier.");
        }

        // Calculate SaaS Base Price and Tenant Markup
        $saasBasePrice = $product->sell_price;
        $tenantMarkupRow = $tenant->ppobMarkups()->where('product_id', $product->id)->first();
        
        if ($tenantMarkupRow && isset($tenantMarkupRow->is_active) && !$tenantMarkupRow->is_active) {
            throw new Exception("Produk PPOB dinonaktifkan oleh RT/RW Net Anda.");
        }

        $markupAmount = $tenantMarkupRow ? $tenantMarkupRow->markup_amount : 0;
        $finalSellPrice = $saasBasePrice + $markupAmount;

        $saasProfit = $saasBasePrice - $supplierProduct->buy_price;
        $tenantProfit = $markupAmount;

        $walletService = app(WalletService::class);

        // 1. Deduct from Customer wallet (Final Price)
        $walletService->debit($customer, $finalSellPrice, "Pembelian PPOB: " . $product->name . " ($customerNumber)");

        // 2. Deduct from Tenant wallet (SaaS Base Price)
        // Note: For real world, we would have a WalletService for Tenant too. For now we do it directly inside a transaction.
        \Illuminate\Support\Facades\DB::transaction(function () use ($tenant, $saasBasePrice) {
            $lockedTenant = \App\Models\Tenant::where('id', $tenant->id)->lockForUpdate()->first();
            if ($lockedTenant->balance < $saasBasePrice) {
                throw new Exception("Transaksi Gagal: Saldo Sistem (RT/RW Net) tidak mencukupi untuk memproses PPOB.");
            }
            $lockedTenant->decrement('balance', $saasBasePrice);
        });

        // Log the transaction as Pending
        $transaction = PpobTransaction::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'supplier_id' => $supplierProduct->supplier_id,
            'customer_number' => $customerNumber,
            'sell_price' => $finalSellPrice, // What customer pays
            'buy_price' => $supplierProduct->buy_price, // What SaaS pays to Digiflazz
            'saas_profit' => $saasProfit,
            'tenant_profit' => $tenantProfit,
            'status' => 'pending',
            'sn' => null,
            'reference_id' => 'PPOB-' . date('YmdHis') . '-' . rand(1000, 9999),
        ]);

        // Route to the specific supplier's API integration
        try {
            $this->executeSupplierApi($transaction, $supplierProduct);
        } catch (Exception $e) {
            Log::error("PPOB API Error (Trx {$transaction->id}): " . $e->getMessage());
            
            // Refund BOTH Customer and Tenant
            $transaction->update(['status' => 'failed', 'message' => 'API Suplier Gagal']);
            $walletService->credit($customer, $finalSellPrice, "Refund PPOB Gagal: " . $product->name, $transaction->id);
            $tenant->increment('balance', $saasBasePrice);
            
            throw new Exception("Transaksi PPOB gagal di sisi suplier. Saldo telah dikembalikan.");
        }

        return $transaction;
    }

    /**
     * Execute the actual API call to the specific supplier.
     */
    protected function executeSupplierApi(PpobTransaction $transaction, PpobSupplierProduct $supplierProduct)
    {
        $supplier = $supplierProduct->supplier;

        if ($supplier->name === 'Digiflazz') {
            // Integration logic for Digiflazz goes here
            // Example: Http::post(...)
            
            // For now, simulate success:
            $transaction->update([
                'status' => 'success',
                'sn' => 'SIMULASI-SN-' . rand(100000, 999999)
            ]);
        } elseif ($supplier->name === 'Tripay') {
            // Integration logic for Tripay goes here
            $transaction->update([
                'status' => 'success',
                'sn' => 'TRIPAY-SN-' . rand(100000, 999999)
            ]);
        } else {
            // Unknown supplier integration
            throw new Exception("Unrecognized supplier integration: " . $supplier->name);
        }
    }
}
