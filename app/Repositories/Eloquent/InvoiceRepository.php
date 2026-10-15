<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['customer.package'])
            ->when(!empty($filters['month']), fn ($query) => $query->where('month', $filters['month']))
            ->when(!empty($filters['year']), fn ($query) => $query->where('year', $filters['year']))
            ->when(!empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy($filters['sort_by'] ?? 'id', $filters['sort_dir'] ?? 'desc')
            ->paginate($perPage);
    }

    public function find(int $id): ?Invoice
    {
        return Invoice::with(['customer.package'])->find($id);
    }

    public function delete(Invoice $invoice): bool
    {
        return (bool) $invoice->delete();
    }
}
