<?php

namespace App\Services;

use App\Models\DeviceStock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeviceStockService
{
    public function create(array $data, ?UploadedFile $image = null): DeviceStock
    {
        if ($image) {
            $data['image'] = $image->store('devices', 'public');
        }

        return DeviceStock::create($data);
    }

    public function update(DeviceStock $deviceStock, array $data, ?UploadedFile $image = null): DeviceStock
    {
        if ($image) {
            if ($deviceStock->image) {
                Storage::disk('public')->delete($deviceStock->image);
            }
            $data['image'] = $image->store('devices', 'public');
        }

        $deviceStock->update($data);

        return $deviceStock->refresh()->loadCount('expenses');
    }

    public function delete(DeviceStock $deviceStock): void
    {
        if ($deviceStock->expenses()->exists()) {
            throw ValidationException::withMessages([
                'device_stock' => 'Stok perangkat tidak bisa dihapus karena memiliki riwayat pembelian kas.',
            ]);
        }

        if ($deviceStock->image) {
            Storage::disk('public')->delete($deviceStock->image);
        }

        $deviceStock->delete();
    }
}
