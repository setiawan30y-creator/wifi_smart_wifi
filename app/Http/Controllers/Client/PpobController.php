<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\PpobProduct;
use App\Services\PpobRouterService;
use Exception;

class PpobController extends Controller
{
    public function index()
    {
        $customer = Customer::first(); // Demo customer
        $products = PpobProduct::all()->groupBy('category');
        return view('client.ppob.index', compact('customer', 'products'));
    }

    public function buy(Request $request, PpobRouterService $routerService)
    {
        $request->validate([
            'product_id' => 'required|exists:ppob_products,id',
            'customer_number' => 'required|string',
        ]);

        $customer = Customer::first(); // Demo customer
        
        try {
            $transaction = $routerService->purchase($customer, $request->product_id, $request->customer_number);
            return back()->with('success', 'Pembelian berhasil diproses! SN: ' . ($transaction->sn ?? 'Pending'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
