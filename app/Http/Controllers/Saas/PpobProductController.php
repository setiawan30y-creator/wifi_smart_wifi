<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobProduct;
use App\Models\PpobSupplier;
use App\Models\PpobSupplierProduct;
use Illuminate\Support\Facades\DB;

class PpobProductController extends Controller
{
    public function index()
    {
        $products = PpobProduct::with('supplierProducts.supplier')->get();
        $suppliers = PpobSupplier::where('is_active', true)->get();
        return view('saas.ppob.products.index', compact('products', 'suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'sell_price' => 'required|numeric|min:0',
        ]);

        PpobProduct::create([
            'category' => $request->category,
            'name' => $request->name,
            'sell_price' => $request->sell_price,
            'is_active' => true,
        ]);

        return back()->with('success', 'Produk PPOB Master berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'sell_price' => 'required|numeric|min:0',
        ]);

        $product = PpobProduct::findOrFail($id);
        $product->update([
            'category' => $request->category,
            'name' => $request->name,
            'sell_price' => $request->sell_price,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Produk PPOB Master berhasil diperbarui');
    }

    public function destroy($id)
    {
        $product = PpobProduct::findOrFail($id);
        $product->delete();

        return back()->with('success', 'Produk PPOB Master berhasil dihapus');
    }

    // Mapping Handler
    public function mapping(Request $request, $productId)
    {
        $request->validate([
            'supplier_id' => 'required|exists:ppob_suppliers,id',
            'supplier_sku' => 'required|string|max:255',
            'buy_price' => 'required|numeric|min:0',
        ]);

        PpobSupplierProduct::updateOrCreate(
            ['product_id' => $productId, 'supplier_id' => $request->supplier_id],
            [
                'supplier_sku' => $request->supplier_sku,
                'buy_price' => $request->buy_price,
                'status' => $request->has('status') ? 'active' : 'inactive',
            ]
        );

        return back()->with('success', 'Mapping Suplier berhasil disimpan');
    }

    public function destroyMapping($id)
    {
        $mapping = PpobSupplierProduct::findOrFail($id);
        $mapping->delete();
        return back()->with('success', 'Mapping Suplier berhasil dihapus');
    }
}
