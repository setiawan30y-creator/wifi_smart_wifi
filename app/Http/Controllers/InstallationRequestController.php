<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationRequest;
use App\Models\Odp;
use App\Models\Package;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InstallationRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = InstallationRequest::with(['package', 'odp', 'assignee', 'customer']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $requests = $query->latest()->paginate(12)->withQueryString();
        $statusCounts = InstallationRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $packages = Package::orderBy('name')->get();
        $odps = Odp::orderBy('name')->get();
        $technicians = User::whereIn('role', ['tehnisi', 'admin', 'superadmin'])->orderBy('name')->get();
        $statuses = InstallationRequest::statuses();

        return view('installation_requests.index', compact('requests', 'statusCounts', 'packages', 'odps', 'technicians', 'statuses'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['created_by'] = auth()->id();
        $data['status'] = $data['status'] ?? InstallationRequest::STATUS_NEW;
        $data = $this->extractCoordinates($data);

        InstallationRequest::create($data);

        return redirect()->route('installation-requests.index')->with('success', 'Calon pelanggan berhasil masuk antrian pemasangan.');
    }

    public function update(Request $request, InstallationRequest $installationRequest)
    {
        $data = $this->validateRequest($request, true);
        $data = $this->extractCoordinates($data);

        $installationRequest->update($data);

        return redirect()->route('installation-requests.index')->with('success', 'Data calon pelanggan berhasil diperbarui.');
    }

    public function updateStatus(Request $request, InstallationRequest $installationRequest)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(InstallationRequest::statuses()))],
            'scheduled_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'survey_notes' => 'nullable|string',
            'cancel_reason' => 'nullable|string',
        ]);

        if ($data['status'] === InstallationRequest::STATUS_CANCELLED && empty($data['cancel_reason'])) {
            return back()->with('error', 'Alasan batal wajib diisi.');
        }

        $installationRequest->update($data);

        return redirect()->route('installation-requests.index')->with('success', 'Status pemasangan berhasil diperbarui.');
    }

    public function convert(Request $request, InstallationRequest $installationRequest, CustomerService $customerService)
    {
        if ($installationRequest->customer_id) {
            return redirect()->route('installation-requests.index')->with('info', 'Calon pelanggan ini sudah pernah dijadikan pelanggan.');
        }

        $data = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'odp_id' => 'nullable|exists:odps,id',
            'billing_day' => 'required|integer|min:1|max:28',
            'tanggal_pasang' => 'required|date',
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
            'status_rumah' => 'required|in:milik_sendiri,ngontrak',
            'pemilik_rumah' => 'nullable|string|max:255',
            'jalur' => 'nullable|in:FO,LAN',
            'perangkat' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($installationRequest, $customerService, $data) {
            $customer = $customerService->storeCustomer(array_merge($data, [
                'name' => $installationRequest->name,
                'email' => $installationRequest->email,
                'phone' => $installationRequest->phone,
                'address' => $installationRequest->address,
                'maps_link' => $installationRequest->maps_link,
                'latitude' => $installationRequest->latitude,
                'longitude' => $installationRequest->longitude,
            ]));

            $installationRequest->update([
                'customer_id' => $customer->id,
                'status' => InstallationRequest::STATUS_INSTALLED,
                'converted_at' => now(),
            ]);
        });

        return redirect()->route('customers.index')->with('success', 'Calon pelanggan berhasil dijadikan pelanggan aktif.');
    }

    public function destroy(InstallationRequest $installationRequest)
    {
        if ($installationRequest->customer_id) {
            return back()->with('error', 'Data yang sudah menjadi pelanggan tidak bisa dihapus dari riwayat pemasangan.');
        }

        $installationRequest->delete();

        return redirect()->route('installation-requests.index')->with('success', 'Data calon pelanggan berhasil dihapus.');
    }

    private function validateRequest(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'maps_link' => 'nullable|string|max:500',
            'package_id' => 'nullable|exists:packages,id',
            'odp_id' => 'nullable|exists:odps,id',
            'assigned_to' => 'nullable|exists:users,id',
            'preferred_installation_date' => 'nullable|date',
            'scheduled_at' => 'nullable|date',
            'status' => ['nullable', Rule::in(array_keys(InstallationRequest::statuses()))],
            'source' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'survey_notes' => 'nullable|string',
            'cancel_reason' => 'nullable|string',
        ]);
    }

    private function extractCoordinates(array $data): array
    {
        if (empty($data['maps_link'])) {
            return $data;
        }

        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $data['maps_link'], $matches)
            || preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $data['maps_link'], $matches)
            || preg_match('/^(-?\d+\.\d+)(?:\s*,\s*|\s+)(-?\d+\.\d+)$/', trim($data['maps_link']), $matches)) {
            $data['latitude'] = $matches[1];
            $data['longitude'] = $matches[2];
        }

        return $data;
    }
}
