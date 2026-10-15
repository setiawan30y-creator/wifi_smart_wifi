<?php

namespace App\Contracts\Repositories;

use App\Models\Odp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OdpRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Odp;

    public function create(array $data): Odp;

    public function update(Odp $odp, array $data): Odp;

    public function delete(Odp $odp): bool;
}
