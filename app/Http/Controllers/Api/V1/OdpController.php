<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\OdpRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Odp\StoreOdpRequest;
use App\Http\Requests\Api\V1\Odp\UpdateOdpRequest;
use App\Http\Resources\V1\OdpResource;
use App\Models\Odp;
use App\Services\OdpService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class OdpController extends Controller
{
    public function __construct(
        private readonly OdpRepositoryInterface $odps
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->odps->paginate(
            filters: $request->only(['search', 'sort_by', 'sort_dir']),
            perPage: min((int) $request->input('per_page', 15), 100)
        );

        return ApiResponse::paginated(
            $paginator,
            OdpResource::collection($paginator->items())->resolve(),
            'Daftar ODP.'
        );
    }

    public function store(StoreOdpRequest $request, OdpService $odpService)
    {
        $odp = $odpService->create($request->validated(), $request->allFiles());

        return ApiResponse::success(new OdpResource($odp), 'ODP berhasil dibuat.', 201);
    }

    public function show(Odp $odp)
    {
        $odp->load(['parentOdp', 'childOdps', 'devices', 'customers.package'])->loadCount('customers');

        return ApiResponse::success(new OdpResource($odp), 'Detail ODP.');
    }

    public function update(UpdateOdpRequest $request, Odp $odp, OdpService $odpService)
    {
        $odp = $odpService->update($odp, $request->validated(), $request->allFiles());

        return ApiResponse::success(new OdpResource($odp), 'ODP berhasil diperbarui.');
    }

    public function destroy(Odp $odp, OdpService $odpService)
    {
        $odpService->delete($odp);

        return ApiResponse::success(null, 'ODP berhasil dihapus.');
    }
}
