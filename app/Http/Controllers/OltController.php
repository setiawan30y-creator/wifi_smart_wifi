<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Olt;
use App\Models\OltOnu;
use App\Services\OltMonitoringService;
use App\Services\OltService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OltController extends Controller
{
    public function index()
    {
        $olts = Olt::latest()->get();

        return view('olts.index', compact('olts'));
    }

    public function show(Olt $olt)
    {
        $olt->load(['onus.customer.package']);

        $customers = Customer::whereNotNull('pppoe_username')
            ->where('pppoe_username', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'pppoe_username']);

        $stats = [
            'total' => $olt->onus->count(),
            'online' => $olt->onus->where('status', 'online')->count(),
            'los' => $olt->onus->where('status', 'los')->count(),
            'pppoe_active' => $olt->onus->where('pppoe_status', 'active')->count(),
            'pppoe_offline' => $olt->onus->whereIn('pppoe_status', ['offline', 'disabled'])->count(),
        ];

        return view('olts.show', compact('olt', 'customers', 'stats'));
    }

    public function store(Request $request)
    {
        Olt::create($this->validatedData($request));

        return redirect()->route('olts.index')->with('success', 'OLT berhasil ditambahkan.');
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

        return redirect()->route('olts.index')->with('success', 'OLT berhasil diperbarui.');
    }

    public function destroy(Olt $olt)
    {
        $olt->delete();

        return redirect()->route('olts.index')->with('success', 'OLT berhasil dihapus.');
    }

    public function testConnection(Olt $olt, OltService $service)
    {
        $result = $service->testConnection($olt);

        return redirect()
            ->route('olts.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function storeOnu(Request $request, Olt $olt)
    {
        $olt->onus()->create($this->validatedOnuData($request));

        return redirect()->route('olts.show', $olt)->with('success', 'Data ONU berhasil ditambahkan.');
    }

    public function updateOnu(Request $request, Olt $olt, OltOnu $onu)
    {
        abort_unless((int) $onu->olt_id === (int) $olt->id, 404);

        $onu->update($this->validatedOnuData($request));

        return redirect()->route('olts.show', $olt)->with('success', 'Data ONU berhasil diperbarui.');
    }

    public function destroyOnu(Olt $olt, OltOnu $onu)
    {
        abort_unless((int) $onu->olt_id === (int) $olt->id, 404);

        $onu->delete();

        return redirect()->route('olts.show', $olt)->with('success', 'Data ONU berhasil dihapus.');
    }

    public function syncCustomers(Olt $olt, OltMonitoringService $service)
    {
        $result = $service->syncCustomersAsOnus($olt);

        return redirect()
            ->route('olts.show', $olt)
            ->with('success', "Sinkron pelanggan selesai. Baru: {$result['created']}, diperbarui: {$result['updated']}.");
    }

    public function syncPppoe(Olt $olt, OltMonitoringService $service)
    {
        $result = $service->syncPppoeStatus($olt);

        return redirect()
            ->route('olts.show', $olt)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
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
