<?php

namespace App\Http\Controllers\Admin\Ppob;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobProduct;
use App\Models\TenantPpobMarkup;

class TenantPpobMarkupController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Get all active global products and join with tenant markups
        $products = PpobProduct::where('is_active', true)
            ->get()
            ->map(function ($product) use ($tenantId) {
                $markup = TenantPpobMarkup::where('tenant_id', $tenantId)
                            ->where('product_id', $product->id)
                            ->first();
                
                $product->tenant_markup = $markup ? $markup->markup_amount : 0;
                $product->is_active_for_tenant = $markup ? $markup->is_active : true;
                $product->final_price = $product->sell_price + $product->tenant_markup;
                
                return $product;
            })->groupBy('category');

        return view('admin.ppob.markup.index', compact('products'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $markups = $request->input('markups', []);

        foreach ($markups as $productId => $data) {
            TenantPpobMarkup::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'product_id' => $productId
                ],
                [
                    'markup_amount' => $data['amount'] ?? 0,
                    'is_active' => isset($data['is_active']) ? 1 : 0
                ]
            );
        }

        return redirect()->back()->with('success', 'Berhasil memperbarui harga jual (Markup) produk PPOB.');
    }
}
