<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\OdpRepositoryInterface;
use App\Models\Odp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OdpRepository implements OdpRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Odp::query()
            ->with(['parentOdp', 'devices'])
            ->withCount('customers')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy($filters['sort_by'] ?? 'name', $filters['sort_dir'] ?? 'asc')
            ->paginate($perPage);
    }

    public function find(int $id): ?Odp
    {
        return Odp::with(['parentOdp', 'childOdps', 'devices', 'customers.package'])
            ->withCount('customers')
            ->find($id);
    }

    public function create(array $data): Odp
    {
        return Odp::create($data);
    }

    public function update(Odp $odp, array $data): Odp
    {
        $odp->update($data);

        return $odp->refresh();
    }

    public function delete(Odp $odp): bool
    {
        return (bool) $odp->delete();
    }
}
