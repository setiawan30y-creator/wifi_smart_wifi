<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WalletTransactionResource;
use App\Models\Customer;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function balance(Request $request)
    {
        $customer = $this->resolveCustomer($request);
        if (!$customer) {
            return ApiResponse::error('Pelanggan tidak ditemukan untuk user ini.', 404, 'CUSTOMER_NOT_FOUND');
        }

        return ApiResponse::success([
            'customer_id' => $customer->id,
            'balance' => $customer->balance,
        ], 'Saldo wallet.');
    }

    public function transactions(Request $request)
    {
        $customer = $this->resolveCustomer($request);
        if (!$customer) {
            return ApiResponse::error('Pelanggan tidak ditemukan untuk user ini.', 404, 'CUSTOMER_NOT_FOUND');
        }

        $paginator = WalletTransaction::where('customer_id', $customer->id)
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::paginated($paginator, WalletTransactionResource::collection($paginator->items())->resolve(), 'Riwayat wallet.');
    }

    public function topup(Request $request, WalletService $walletService)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:10000'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'string', 'max:255'],
        ]);

        $customer = $this->resolveCustomer($request);
        if (!$customer) {
            return ApiResponse::error('Pelanggan tidak ditemukan untuk user ini.', 404, 'CUSTOMER_NOT_FOUND');
        }

        try {
            $transaction = $walletService->credit($customer, $data['amount'], $data['description'] ?? 'Topup Saldo', $data['reference_id'] ?? null);
        } catch (Exception $exception) {
            return ApiResponse::error($exception->getMessage(), 422, 'WALLET_ERROR');
        }

        return ApiResponse::success(new WalletTransactionResource($transaction), 'Topup wallet berhasil.', 201);
    }

    private function resolveCustomer(Request $request): ?Customer
    {
        if ($request->user()->role !== 'client' && $request->filled('customer_id')) {
            return Customer::find($request->input('customer_id'));
        }

        return Customer::where('email', $request->user()->email)
            ->when($request->user()->phone, fn ($query) => $query->orWhere('phone', $request->user()->phone))
            ->first();
    }
}
