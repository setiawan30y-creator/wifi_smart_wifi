<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    private const CATEGORIES = [
        'Pembelian Perangkat',
        'Operasional',
        'Gaji/Honor',
        'Perbaikan',
        'Lain-lain',
        'ISP',
        'Listrik',
        'Perlengkapan',
    ];

    public function index(Request $request)
    {
        $query = Expense::with('deviceStock')->latest('date')->latest('id');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->date('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->date('end_date'));
        }

        $paginator = $query->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::paginated($paginator, ExpenseResource::collection($paginator->items())->resolve(), 'Daftar pengeluaran.');
    }

    public function store(Request $request, ExpenseService $service)
    {
        $expense = $service->create($this->validatedData($request));

        return ApiResponse::success(new ExpenseResource($expense), 'Pengeluaran berhasil dibuat.', 201);
    }

    public function show(Expense $expense)
    {
        return ApiResponse::success(new ExpenseResource($expense->load('deviceStock')), 'Detail pengeluaran.');
    }

    public function update(Request $request, Expense $expense, ExpenseService $service)
    {
        $expense = $service->update($expense, $this->validatedData($request));

        return ApiResponse::success(new ExpenseResource($expense), 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense, ExpenseService $service)
    {
        $service->delete($expense);

        return ApiResponse::success(null, 'Pengeluaran berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'amount' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'date'],
            'device_stock_id' => ['nullable'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ];

        if ($request->input('category') === 'Pembelian Perangkat') {
            $rules['device_stock_id'] = ['required'];
            $rules['quantity'] = ['required', 'integer', 'min:1'];

            if ($request->input('device_stock_id') === 'NEW_DEVICE') {
                $rules['new_device_brand'] = ['required', 'string', 'max:255'];
                $rules['new_device_name'] = ['required', 'string', 'max:255', 'unique:device_stocks,name'];
                $rules['new_device_type'] = ['required', 'string', 'in:FO,LAN'];
            } else {
                $rules['device_stock_id'] = ['required', 'exists:device_stocks,id'];
            }
        }

        return $request->validate($rules);
    }
}
