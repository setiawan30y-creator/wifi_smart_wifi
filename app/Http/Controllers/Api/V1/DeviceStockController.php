<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeviceStockResource;
use App\Models\DeviceStock;
use App\Services\DeviceStockService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class DeviceStockController extends Controller
{
    public function index(Request $request)
    {
        $query = DeviceStock::query()->withCount('expenses');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")->orWhere('brand', 'like', "%{$search}%");
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $paginator = $query->orderBy('brand')->orderBy('name')->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::paginated($paginator, DeviceStockResource::collection($paginator->items())->resolve(), 'Daftar stok perangkat.');
    }

    public function store(Request $request, DeviceStockService $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'unique:device_stocks,name'],
            'brand' => ['required', 'string'],
            'type' => ['required', 'string', 'in:FO,LAN'],
            'image' => ['nullable', 'image', 'max:2048'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $deviceStock = $service->create($data, $request->file('image'))->loadCount('expenses');

        return ApiResponse::success(new DeviceStockResource($deviceStock), 'Stok perangkat berhasil dibuat.', 201);
    }

    public function show(DeviceStock $deviceStock)
    {
        return ApiResponse::success(new DeviceStockResource($deviceStock->loadCount('expenses')), 'Detail stok perangkat.');
    }

    public function update(Request $request, DeviceStock $deviceStock, DeviceStockService $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'unique:device_stocks,name,' . $deviceStock->id],
            'brand' => ['required', 'string'],
            'type' => ['required', 'string', 'in:FO,LAN'],
            'image' => ['nullable', 'image', 'max:2048'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(new DeviceStockResource($service->update($deviceStock, $data, $request->file('image'))), 'Stok perangkat berhasil diperbarui.');
    }

    public function destroy(DeviceStock $deviceStock, DeviceStockService $service)
    {
        $service->delete($deviceStock);

        return ApiResponse::success(null, 'Stok perangkat berhasil dihapus.');
    }
}
