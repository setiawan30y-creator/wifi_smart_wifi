<?php

namespace App\Http\Controllers;

use App\Models\DeviceStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeviceStockController extends Controller
{
    /**
     * Display a listing of the device stocks.
     */
    public function index()
    {
        $deviceStocks = DeviceStock::orderBy('brand')->orderBy('name')->get();
        return view('device_stocks.index', compact('deviceStocks'));
    }

    /**
     * Store a newly created device stock in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:device_stocks,name',
            'brand' => 'required|string',
            'type' => 'required|string|in:FO,LAN',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:4096',
            'stock' => 'required|integer|min:0',
            'unit_price' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                if (!$file->isValid()) {
                    return back()->withInput()->with('error', 'Foto produk gagal diupload. Silakan pilih ulang file gambar.');
                }

                $validated['image'] = $file->store('devices', 'public');
            }

            DeviceStock::create($validated);
        } catch (\Throwable $exception) {
            Log::error('Failed to store device stock.', [
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan perangkat: ' . $exception->getMessage());
        }

        return redirect()->route('device-stocks.index')->with('success', 'Stok perangkat baru berhasil ditambahkan.');
    }

    /**
     * Update the specified device stock in storage.
     */
    public function update(Request $request, DeviceStock $deviceStock)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:device_stocks,name,' . $deviceStock->id,
            'brand' => 'required|string',
            'type' => 'required|string|in:FO,LAN',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:4096',
            'stock' => 'required|integer|min:0',
            'unit_price' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'remove_image' => 'nullable|boolean',
        ]);

        unset($validated['remove_image']);

        try {
            if ($request->boolean('remove_image') && $deviceStock->image) {
                Storage::disk('public')->delete($deviceStock->image);
                $validated['image'] = null;
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');

                if (!$file->isValid()) {
                    return back()->withInput()->with('error', 'Foto produk gagal diupload. Silakan pilih ulang file gambar.');
                }

                if ($deviceStock->image) {
                    Storage::disk('public')->delete($deviceStock->image);
                }

                $validated['image'] = $file->store('devices', 'public');
            }

            $deviceStock->update($validated);
        } catch (\Throwable $exception) {
            Log::error('Failed to update device stock.', [
                'device_stock_id' => $deviceStock->id,
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui perangkat: ' . $exception->getMessage());
        }

        return redirect()->route('device-stocks.index')->with('success', 'Stok perangkat berhasil diperbarui.');
    }

    public function image(DeviceStock $deviceStock)
    {
        abort_unless($deviceStock->image, 404);
        abort_unless(Storage::disk('public')->exists($deviceStock->image), 404);

        return Storage::disk('public')->response($deviceStock->image);
    }

    /**
     * Remove the specified device stock from storage.
     */
    public function destroy(DeviceStock $deviceStock)
    {
        // Prevent deletion if linked to expenses
        if ($deviceStock->expenses()->exists()) {
            return redirect()->route('device-stocks.index')->with('error', 'Stok perangkat tidak bisa dihapus karena memiliki riwayat pembelian kas.');
        }

        if ($deviceStock->image) {
            Storage::disk('public')->delete($deviceStock->image);
        }

        $deviceStock->delete();

        return redirect()->route('device-stocks.index')->with('success', 'Stok perangkat berhasil dihapus.');
    }
}
