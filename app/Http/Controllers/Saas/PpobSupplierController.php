<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobSupplier;

class PpobSupplierController extends Controller
{
    public function index()
    {
        $suppliers = PpobSupplier::all();
        return view('saas.ppob.suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'nullable|string|max:255',
            'api_secret' => 'nullable|string|max:255',
            'endpoint' => 'nullable|url',
        ]);

        PpobSupplier::create([
            'name' => $request->name,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'endpoint' => $request->endpoint,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Suplier PPOB berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $supplier = PpobSupplier::findOrFail($id);
        $supplier->update([
            'name' => $request->name,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'endpoint' => $request->endpoint,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Suplier PPOB berhasil diperbarui');
    }

    public function destroy($id)
    {
        $supplier = PpobSupplier::findOrFail($id);
        $supplier->delete();

        return back()->with('success', 'Suplier PPOB berhasil dihapus');
    }
}
