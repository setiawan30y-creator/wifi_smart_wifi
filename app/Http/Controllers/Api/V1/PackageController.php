<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\PackageRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Package\StorePackageRequest;
use App\Http\Requests\Api\V1\Package\UpdatePackageRequest;
use App\Http\Resources\V1\PackageResource;
use App\Models\Package;
use App\Services\PackageService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(
        private readonly PackageRepositoryInterface $packages
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->packages->paginate(
            filters: $request->only(['search', 'sort_by', 'sort_dir']),
            perPage: min((int) $request->input('per_page', 15), 100)
        );

        return ApiResponse::paginated(
            $paginator,
            PackageResource::collection($paginator->items())->resolve(),
            'Daftar paket layanan.'
        );
    }

    public function store(StorePackageRequest $request, PackageService $packageService)
    {
        $data = $request->validated();
        $syncMikrotik = (bool) ($data['sync_mikrotik'] ?? true);
        unset($data['sync_mikrotik']);

        $package = $packageService->create($data, $syncMikrotik)->loadCount('customers');

        return ApiResponse::success(new PackageResource($package), 'Paket berhasil dibuat.', 201);
    }

    public function show(Package $package)
    {
        $package->loadCount('customers');

        return ApiResponse::success(new PackageResource($package), 'Detail paket layanan.');
    }

    public function update(UpdatePackageRequest $request, Package $package, PackageService $packageService)
    {
        $data = $request->validated();
        $syncMikrotik = (bool) ($data['sync_mikrotik'] ?? true);
        unset($data['sync_mikrotik']);

        $package = $packageService->update($package, $data, $syncMikrotik);

        return ApiResponse::success(new PackageResource($package), 'Paket berhasil diperbarui.');
    }

    public function destroy(Package $package, PackageService $packageService)
    {
        $packageService->delete($package);

        return ApiResponse::success(null, 'Paket berhasil dihapus.');
    }
}
