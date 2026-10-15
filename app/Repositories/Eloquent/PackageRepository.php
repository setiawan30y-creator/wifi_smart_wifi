<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\PackageRepositoryInterface;
use App\Models\Package;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PackageRepository implements PackageRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Package::query()
            ->withCount('customers')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('speed', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy($filters['sort_by'] ?? 'name', $filters['sort_dir'] ?? 'asc')
            ->paginate($perPage);
    }

    public function all(array $filters = []): Collection
    {
        return Package::query()
            ->withCount('customers')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('speed', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?Package
    {
        return Package::withCount('customers')->find($id);
    }

    public function create(array $data): Package
    {
        return Package::create($data);
    }

    public function update(Package $package, array $data): Package
    {
        $package->update($data);

        return $package->refresh()->loadCount('customers');
    }

    public function delete(Package $package): bool
    {
        return (bool) $package->delete();
    }
}
