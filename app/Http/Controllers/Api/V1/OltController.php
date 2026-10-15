<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OltOnuResource;
use App\Http\Resources\V1\OltResource;
use App\Models\Olt;
use App\Models\OltOnu;
use App\Services\OltMonitoringService;
use App\Services\OltService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OltController extends Controller
{
    public function index(Request $request)
    {
        $query = Olt::query()->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('host', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        $paginator = $query->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::paginated($paginator, OltResource::collection($paginator->items())->resolve(), 'Daftar OLT.');
    }

    public function store(Request $request)
    {
        $olt = Olt::create($this->validatedData($request));

        return ApiResponse::success(new OltResource($olt), 'OLT berhasil dibuat.', 201);
    }

    public function show(Olt $olt)
    {
        return ApiResponse::success(new OltResource($olt->load('onus.customer.package')), 'Detail OLT.');
    }

    public function update(Request $request, Olt $olt)
    {
        $data = $this->validatedData($request, $olt);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if (blank($data['snmp_community'] ?? null)) {
            unset($data['snmp_community']);
        }

        $olt->update($data);

        return ApiResponse::success(new OltResource($olt->refresh()), 'OLT berhasil diperbarui.');
    }

    public function destroy(Olt $olt)
    {
        $olt->delete();

        return ApiResponse::success(null, 'OLT berhasil dihapus.');
    }

    public function testConnection(Olt $olt, OltService $service)
    {
        $result = $service->testConnection($olt);

        return $result['success']
            ? ApiResponse::success(new OltResource($olt->refresh()), $result['message'])
            : ApiResponse::error($result['message'], 422, 'OLT_CONNECTION_FAILED');
    }

    public function onus(Olt $olt)
    {
        $onus = $olt->onus()->with('customer.package')->latest()->get();

        return ApiResponse::success(OltOnuResource::collection($onus), 'Daftar ONU OLT.');
    }

    public function storeOnu(Request $request, Olt $olt)
    {
        $onu = $olt->onus()->create($this->validatedOnuData($request));

        return ApiResponse::success(new OltOnuResource($onu->load('customer.package')), 'ONU berhasil dibuat.', 201);
    }

    public function updateOnu(Request $request, Olt $olt, OltOnu $onu)
    {
        abort_unless((int) $onu->olt_id === (int) $olt->id, 404);

        $onu->update($this->validatedOnuData($request));

        return ApiResponse::success(new OltOnuResource($onu->refresh()->load('customer.package')), 'ONU berhasil diperbarui.');
    }

    public function destroyOnu(Olt $olt, OltOnu $onu)
    {
        abort_unless((int) $onu->olt_id === (int) $olt->id, 404);

        $onu->delete();

        return ApiResponse::success(null, 'ONU berhasil dihapus.');
    }

    public function syncCustomers(Olt $olt, OltMonitoringService $service)
    {
        $result = $service->syncCustomersAsOnus($olt);

        return ApiResponse::success($result, 'Sinkron pelanggan selesai.');
    }

    public function syncPppoe(Olt $olt, OltMonitoringService $service)
    {
        $result = $service->syncPppoeStatus($olt);

        return $result['success']
            ? ApiResponse::success($result, $result['message'])
            : ApiResponse::error($result['message'], 422, 'OLT_PPPOE_SYNC_FAILED', $result);
    }

    private function validatedData(Request $request, ?Olt $olt = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['required', Rule::in(['telnet', 'ssh', 'snmp'])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => [$olt ? 'nullable' : 'required_unless:protocol,snmp', 'nullable', 'string', 'max:255'],
            'snmp_community' => ['nullable', 'string', 'max:255'],
            'snmp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'pon_ports' => ['nullable', 'integer', 'min:0', 'max:4096'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function validatedOnuData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'pon_port' => ['nullable', 'string', 'max:255'],
            'onu_id' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'pppoe_username' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['online', 'offline', 'los', 'unknown'])],
            'pppoe_status' => ['required', Rule::in(['active', 'offline', 'disabled', 'unknown'])],
            'rx_power' => ['nullable', 'numeric', 'min:-60', 'max:20'],
            'tx_power' => ['nullable', 'numeric', 'min:-60', 'max:20'],
            'distance' => ['nullable', 'integer', 'min:0', 'max:200000'],
            'uptime' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
