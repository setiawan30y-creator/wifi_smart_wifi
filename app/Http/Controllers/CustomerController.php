<?php

namespace App\Http\Controllers;

use App\Support\SafeRedirect;
use App\Services\Customers\CustomerExporter;
use App\Services\Customers\CustomerImporter;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Odp;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::with(['package', 'odp', 'latestInvoice', 'statusHistories.user'])->withCount('invoices');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter_name')) {
            $query->where('name', 'like', '%' . $request->filter_name . '%');
        }
        if ($request->filled('filter_phone')) {
            $query->where('phone', 'like', '%' . $request->filter_phone . '%');
        }
        if ($request->filled('filter_address')) {
            $query->where('address', 'like', '%' . $request->filter_address . '%');
        }
        if ($request->filled('filter_odp')) {
            $query->where('odp_id', $request->filter_odp);
        }
        if ($request->filled('filter_package')) {
            $query->where('package_id', $request->filter_package);
        }
        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }
        if ($request->filled('filter_billing_type')) {
            $query->where('billing_type', $request->filter_billing_type);
        }
        if ($request->filled('filter_billing_mode')) {
            $query->where('billing_mode', $request->filter_billing_mode);
        }
        if ($request->filled('filter_billing_day')) {
            $query->where('billing_day', (int) $request->filter_billing_day);
        }

        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $customers = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();
        $packages = Package::all();
        $odps = \App\Models\Odp::withCount('customers')->orderBy('name', 'asc')->get();
        $waTemplates = \App\Models\WaTemplate::orderBy('title', 'asc')->get();

        return view('customers.index', compact('customers', 'packages', 'odps', 'waTemplates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\Customer\StoreCustomerRequest $request, \App\Services\CustomerService $customerService)
    {
        $data = $request->validated();
        $files = $request->allFiles();
        
        $customerService->storeCustomer($data, $files);

        $redirectTo = SafeRedirect::to($request->input('redirect_to'), route('customers.index'));

        return redirect($redirectTo)->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\Customer\UpdateCustomerRequest $request, Customer $customer, \App\Services\CustomerService $customerService)
    {
        $data = $request->validated();
        $files = $request->allFiles();

        $customerService->updateCustomer($customer, $data, $files);

        $redirectTo = SafeRedirect::to($request->input('redirect_to'), route('customers.index'));

        return redirect($redirectTo)->with('success', 'Pelanggan berhasil diperbarui.');
    }

    /**
     * Toggle customer active/inactive status.
     */
    public function toggleStatus(Customer $customer)
    {
        $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
        app(\App\Services\CustomerService::class)->changeStatus(
            $customer,
            $newStatus,
            $newStatus === 'active' ? 'aktif_kembali' : 'nonaktif',
            request('reason')
        );

        return redirect()->route('customers.index')->with('success', 'Status pelanggan berhasil diubah menjadi ' . ($newStatus === 'active' ? 'Aktif' : 'Nonaktif') . '.');
    }

    public function isolate(Request $request, Customer $customer)
    {
        app(\App\Services\CustomerService::class)->changeStatus($customer, 'isolated', 'isolir', $request->input('reason'));

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil diisolir.');
    }

    public function stopService(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'stopped_at' => ['nullable', 'date'],
        ]);

        app(\App\Services\CustomerService::class)->changeStatus(
            $customer,
            'stopped',
            'berhenti',
            $data['reason'] ?? null,
            !empty($data['stopped_at']) ? Carbon::parse($data['stopped_at']) : null
        );

        return redirect()->route('customers.index')->with('success', 'Layanan pelanggan berhasil diberhentikan.');
    }

    public function reactivate(Request $request, Customer $customer)
    {
        app(\App\Services\CustomerService::class)->changeStatus($customer, 'active', 'aktif_kembali', $request->input('reason'));

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil diaktifkan kembali.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil dihapus.');
    }

    public function destroyAll(Request $request)
    {
        $request->validate([
            'confirmation' => ['required', 'in:HAPUS'],
        ], [
            'confirmation.in' => 'Konfirmasi hapus semua pelanggan harus diisi dengan kata HAPUS.',
        ]);

        $deletedCount = 0;

        DB::transaction(function () use (&$deletedCount) {
            Customer::query()
                ->lazyById(200)
                ->each(function (Customer $customer) use (&$deletedCount) {
                    $customer->delete();
                    $deletedCount++;
                });
        });

        return redirect()
            ->route('customers.index')
            ->with('success', "{$deletedCount} data pelanggan berhasil dihapus.");
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer', 'exists:customers,id'],
            'confirmation' => ['required', 'in:HAPUS'],
        ], [
            'customer_ids.required' => 'Pilih minimal satu pelanggan yang ingin dihapus.',
            'confirmation.in' => 'Konfirmasi hapus pelanggan pilihan harus diisi dengan kata HAPUS.',
        ]);

        $deletedCount = 0;

        DB::transaction(function () use ($data, &$deletedCount) {
            Customer::whereIn('id', $data['customer_ids'])
                ->lazyById(100)
                ->each(function (Customer $customer) use (&$deletedCount) {
                    $customer->delete();
                    $deletedCount++;
                });
        });

        return redirect()
            ->route('customers.index')
            ->with('success', "{$deletedCount} pelanggan pilihan berhasil dihapus.");
    }

    public function bulkUpdateBillingMode(Request $request)
    {
        $data = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer', 'distinct', 'exists:customers,id'],
            'billing_mode' => ['required', 'in:postpaid,prepaid'],
        ], [
            'customer_ids.required' => 'Pilih minimal satu pelanggan.',
            'billing_mode.in' => 'Mode pembayaran tidak valid.',
        ]);

        $updatedCount = Customer::whereIn('id', $data['customer_ids'])
            ->update(['billing_mode' => $data['billing_mode']]);

        $label = Customer::billingModeLabels()[$data['billing_mode']];

        return redirect()
            ->route('customers.index')
            ->with('success', "{$updatedCount} pelanggan berhasil diubah menjadi {$label}.");
    }

    /**
     * Print the customer profile details.
     */
    public function print(Customer $customer)
    {
        $customer->load(['package', 'odp', 'latestInvoice']);
        return view('customers.print', compact('customer'));
    }
    /**
     * Export pelanggan ke .xls (logika di App\Services\Customers\CustomerExporter).
     */
    public function export(CustomerExporter $exporter)
    {
        return response($exporter->xml(), 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="pelanggan_' . date('Ymd_His') . '.xls"',
        ]);
    }
    /**
     * Import pelanggan dari file Excel/CSV (logika di App\Services\Customers\CustomerImporter).
     */
    public function import(Request $request, CustomerImporter $importer)
    {
        $request->validate([
            'import_file' => 'required|file|max:5120',
        ]);

        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, CustomerImporter::EXTENSIONS, true)) {
            return redirect()->route('customers.index')
                ->with('error', 'Format file tidak didukung. Gunakan file .xlsx, .xls, .csv, atau .txt.');
        }

        $result = $importer->import($file->getRealPath(), $extension);

        if ($result['rows'] === 0) {
            return redirect()->route('customers.index')->with('error', 'File kosong atau format tidak dikenali. Untuk Excel biasa gunakan .xlsx, atau pakai file .xls hasil export dari sistem ini.');
        }

        $message = "Berhasil mengimpor {$result['imported']} pelanggan.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} baris dilewati.";
        }
        if (!empty($result['errors'])) {
            $message .= ' Catatan: ' . implode('; ', array_slice($result['errors'], 0, 3));
            if (count($result['errors']) > 3) {
                $message .= ' ... dan ' . (count($result['errors']) - 3) . ' pesan lainnya.';
            }
        }

        return redirect()->route('customers.index')
            ->with($result['imported'] > 0 ? 'success' : 'error', $message);
    }

}
