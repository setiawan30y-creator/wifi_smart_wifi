<?php

namespace App\Contracts\Repositories;

use App\Models\Package;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PackageRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function all(array $filters = []): Collection;

    public function find(int $id): ?Package;

    public function create(array $data): Package;

    public function update(Package $package, array $data): Package;

    public function delete(Package $package): bool;
}
