<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientCustomer;
use App\Http\Controllers\Controller;
use App\Models\PpobProduct;
use App\Services\PpobRouterService;
use Exception;
use Illuminate\Http\Request;

class PpobController extends Controller
{
    use ResolvesClientCustomer;

    public function index()
    {
        $customer = $this->clientCustomer();
        $products = PpobProduct::all()->groupBy('category');

        return view('client.ppob.index', compact('customer', 'products'));
    }

    public function buy(Request $request, PpobRouterService $routerService)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:ppob_products,id',
            'customer_number' => 'required|string|max:100',
        ]);

        $customer = $this->clientCustomer();

        try {
            $transaction = $routerService->purchase($customer, $data['product_id'], $data['customer_number']);

            return back()->with('success', 'Pembelian berhasil diproses! SN: ' . ($transaction->sn ?? 'Pending'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
