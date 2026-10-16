<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PpobProductResource;
use App\Http\Resources\V1\PpobTransactionResource;
use App\Models\Customer;
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use App\Services\PpobRouterService;
use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\Request;

class PpobController extends Controller
{
    public function products(Request $request)
    {
        $query = PpobProduct::query()->where('is_active', true)->orderBy('category')->orderBy('sell_price');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate(min((int) $request->input('per_page', 50), 100));

        return ApiResponse::paginated($paginator, PpobProductResource::collection($paginator->items())->resolve(), 'Daftar produk PPOB.');
    }

    public function transactions(Request $request)
    {
        $customer = $this->resolveCustomer($request);
        if (!$customer && $request->user()->role === 'client') {
            return ApiResponse::error('Pelanggan tidak ditemukan untuk user ini.', 404, 'CUSTOMER_NOT_FOUND');
        }

        $query = PpobTransaction::with('product')->latest();
        if ($customer) {
            $query->where('customer_id', $customer->id);
        }

        $paginator = $query->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::paginated($paginator, PpobTransactionResource::collection($paginator->items())->resolve(), 'Riwayat transaksi PPOB.');
    }

    public function purchase(Request $request, PpobRouterService $routerService)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_id' => ['required', 'exists:ppob_products,id'],
            'customer_number' => ['required', 'string', 'max:100'],
        ]);

        $customer = $this->resolveCustomer($request);
        if (!$customer) {
            return ApiResponse::error('Pelanggan tidak ditemukan untuk user ini.', 404, 'CUSTOMER_NOT_FOUND');
        }

        try {
            $transaction = $routerService->purchase($customer, $data['product_id'], $data['customer_number']);
        } catch (Exception $exception) {
            return ApiResponse::error($exception->getMessage(), 422, 'PPOB_PURCHASE_FAILED');
        }

        return ApiResponse::success(new PpobTransactionResource($transaction->load('product')), 'Pembelian PPOB berhasil diproses.', 201);
    }

    private function resolveCustomer(Request $request): ?Customer
    {
        if ($request->user()->role !== 'client' && $request->filled('customer_id')) {
            return Customer::find($request->input('customer_id'));
        }

        return Customer::forUser($request->user());
    }
}
