<?php

namespace App\Http\Controllers;

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

        $redirectTo = $this->safeRedirectTarget($request->input('redirect_to'), route('customers.index'));

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

        $redirectTo = $this->safeRedirectTarget($request->input('redirect_to'), route('customers.index'));

        return redirect($redirectTo)->with('success', 'Pelanggan berhasil diperbarui.');
    }

    private function safeRedirectTarget(?string $target, string $fallback): string
    {
        if (!$target) {
            return $fallback;
        }

        if (str_starts_with($target, '/') || str_starts_with($target, url('/'))) {
            return $target;
        }

        return $fallback;
    }

    /**
     * Toggle customer active/inactive status.
     */
    public function toggleStatus(Customer $customer)
    {
        $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
        $this->changeStatus(
            $customer,
            $newStatus,
            $newStatus === 'active' ? 'aktif_kembali' : 'nonaktif',
            request('reason')
        );

        return redirect()->route('customers.index')->with('success', 'Status pelanggan berhasil diubah menjadi ' . ($newStatus === 'active' ? 'Aktif' : 'Nonaktif') . '.');
    }

    public function isolate(Request $request, Customer $customer)
    {
        $this->changeStatus($customer, 'isolated', 'isolir', $request->input('reason'));

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil diisolir.');
    }

    public function stopService(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'stopped_at' => ['nullable', 'date'],
        ]);

        $this->changeStatus(
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
        $this->changeStatus($customer, 'active', 'aktif_kembali', $request->input('reason'));

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil diaktifkan kembali.');
    }

    private function changeStatus(Customer $customer, string $newStatus, string $action, ?string $reason = null, ?Carbon $changedAt = null): void
    {
        DB::transaction(function () use ($customer, $newStatus, $action, $reason, $changedAt) {
            $oldStatus = $customer->status;

            if ($oldStatus === $newStatus) {
                return;
            }

            $customer->update(['status' => $newStatus]);

            $customer->statusHistories()->create([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'action' => $action,
                'reason' => $reason,
                'changed_by' => auth()->id(),
                'changed_at' => $changedAt ?? now(),
            ]);
        });
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
     * Export all customers to Excel XML (.xls) — no external library needed.
     */
    public function export()
    {
        $customers = Customer::with(['package', 'odp', 'statusHistories'])->orderBy('name')->get();

        // Build SpreadsheetML (Excel 2003 XML) — opens natively in Excel & LibreOffice
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= '  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= '  xmlns:x="urn:schemas-microsoft-com:office:excel">' . "\n";
        $xml .= '<Worksheet ss:Name="Pelanggan"><Table>' . "\n";

        // Header row
        $headers = [
            'ID', 'Nama', 'Email', 'No. Telepon', 'Alamat',
            'Paket', 'ODP', 'Status', 'Tgl Tagih',
            'Jenis Tagihan', 'Catatan Gratis', 'Tgl Pasang', 'PPPoE Username', 'Status Rumah',
            'Pemilik Rumah', 'Jalur', 'Perangkat',
            'Tanggal Isolir', 'Tanggal Berhenti', 'Tanggal Aktif Kembali',
            'Tanggal Update Status Terakhir', 'Catatan Status Terakhir',
        ];
        $xml .= '<Row>' . "\n";
        foreach ($headers as $h) {
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($h, ENT_XML1) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";

        // Data rows
        foreach ($customers as $c) {
            $statusLabels = [
                'active' => 'Aktif',
                'inactive' => 'Nonaktif',
                'isolated' => 'Isolir',
                'stopped' => 'Berhenti',
            ];
            $isolatedAt = $c->statusHistories->firstWhere('action', 'isolir')?->changed_at;
            $stoppedAt = $c->statusHistories->firstWhere('action', 'berhenti')?->changed_at;
            $reactivatedAt = $c->statusHistories->firstWhere('action', 'aktif_kembali')?->changed_at;
            $latestStatusHistory = $c->statusHistories->first();

            $xml .= '<Row>' . "\n";
            $cols = [
                $c->id,
                $c->name,
                $c->email ?? '',
                $c->phone,
                $c->address,
                $c->package ? $c->package->name : '',
                $c->odp ? $c->odp->name : '',
                $statusLabels[$c->status] ?? $c->status,
                $c->billing_day,
                $c->billingTypeLabel(),
                $c->billing_note ?? '',
                $c->tanggal_pasang ?? '',
                $c->pppoe_username ?? '',
                $c->status_rumah ?? '',
                $c->pemilik_rumah ?? '',
                $c->jalur ?? '',
                $c->perangkat ?? '',
                $isolatedAt ? $isolatedAt->format('Y-m-d H:i:s') : '',
                $stoppedAt ? $stoppedAt->format('Y-m-d H:i:s') : '',
                $reactivatedAt ? $reactivatedAt->format('Y-m-d H:i:s') : '',
                $latestStatusHistory ? $latestStatusHistory->changed_at->format('Y-m-d H:i:s') : '',
                $latestStatusHistory ? ($latestStatusHistory->reason ?? '') : '',
            ];
            foreach ($cols as $col) {
                $type = is_numeric($col) && !preg_match('/^0\d/', $col) ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars((string) $col, ENT_XML1) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        $filename = 'pelanggan_' . date('Ymd_His') . '.xls';

        return response($xml, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Import customers from an Excel/CSV file.
     * Supports .csv files (Excel-compatible) and .xls files exported by this system.
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|max:5120',
        ]);

        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['csv', 'txt', 'xls', 'xlsx'];

        if (!in_array($extension, $allowedExtensions, true)) {
            return redirect()
                ->route('customers.index')
                ->with('error', 'Format file tidak didukung. Gunakan file .xlsx, .xls, .csv, atau .txt.');
        }

        $rows = [];

        if (in_array($extension, ['csv', 'txt'])) {
            $rows = $this->readCsvRows($file->getRealPath());
        } elseif ($extension === 'xls') {
            $rows = $this->readSpreadsheetMlRows($file->getRealPath());
        } elseif ($extension === 'xlsx') {
            $rows = $this->readXlsxRows($file->getRealPath());
        }

        if (empty($rows)) {
            return redirect()->route('customers.index')->with('error', 'File kosong atau format tidak dikenali. Untuk Excel biasa gunakan .xlsx, atau pakai file .xls hasil export dari sistem ini.');
        }

        // Skip header row
        $header = array_shift($rows);
        $headerMap = $this->customerImportHeaderMap($header);
        $useCompactFallback = !$this->hasRecognizedCustomerImportHeader($headerMap);

        if ($useCompactFallback) {
            array_unshift($rows, $header);
        }

        $fallback = $useCompactFallback
            ? [
                'name' => 0,
                'email' => null,
                'phone' => 1,
                'address' => 2,
                'package' => 3,
                'odp' => 4,
                'status' => 5,
                'billing_day' => 6,
                'billing_type' => null,
                'billing_mode' => null,
                'billing_note' => null,
                'tanggal_pasang' => 7,
                'pppoe_username' => 8,
                'status_rumah' => 9,
                'pemilik_rumah' => 10,
                'jalur' => 11,
                'perangkat' => 12,
            ]
            : [
                'name' => 1,
                'email' => 2,
                'phone' => 3,
                'address' => 4,
                'package' => 5,
                'odp' => 6,
                'status' => 7,
                'billing_day' => 8,
                'billing_type' => 9,
                'billing_mode' => null,
                'billing_note' => 10,
                'tanggal_pasang' => 11,
                'pppoe_username' => 12,
                'status_rumah' => 13,
                'pemilik_rumah' => 14,
                'jalur' => 15,
                'perangkat' => 16,
            ];

        $packages = Package::pluck('id', 'name')->toArray();
        $odps     = Odp::pluck('id', 'name')->toArray();

        $importedCount = 0;
        $skippedCount  = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
            // Expect: ID, Nama, Email, No.Telepon, Alamat, Paket, ODP, Status, TglTagih, TglPasang, PPPoE, StatusRumah, PemilikRumah, Jalur, Perangkat
            if ($this->isEmptyImportRow($row)) {
                $skippedCount++;
                continue;
            }

            $name    = $this->rowValue($row, $headerMap, ['nama', 'name', 'namapelanggan', 'pelanggan'], $fallback['name']);
            $phone   = $this->normalizePhoneNumber($this->rowValue($row, $headerMap, ['notelepon', 'telepon', 'phone', 'hp', 'nohp', 'kontak'], $fallback['phone']));
            $address = $this->rowValue($row, $headerMap, ['alamat', 'address', 'alamatrumah'], $fallback['address']);

            if (empty($name) || empty($phone) || empty($address)) {
                $errors[] = "Baris " . ($i + 2) . ": Nama, No. Telepon, atau Alamat kosong, baris dilewati.";
                $skippedCount++;
                continue;
            }

            $packageName = $this->rowValue($row, $headerMap, ['paket', 'package', 'paketlayanan'], $fallback['package']);
            $odpName     = $this->rowValue($row, $headerMap, ['odp', 'odpterhubung'], $fallback['odp']);
            $statusInput = strtolower($this->rowValue($row, $headerMap, ['status'], $fallback['status']));
            $statusMap = [
                'aktif' => 'active',
                'active' => 'active',
                'nonaktif' => 'inactive',
                'inactive' => 'inactive',
                'isolir' => 'isolated',
                'isolated' => 'isolated',
                'berhenti' => 'stopped',
                'stopped' => 'stopped',
            ];
            $status = $statusMap[$statusInput] ?? 'active';
            $billingDay  = (int) ($this->rowValue($row, $headerMap, ['tgltagih', 'tanggaltagih', 'billingday'], $fallback['billing_day']) ?: 1);
            $billingDay  = max(1, min(28, $billingDay));
            $billingType = $this->normalizeBillingType($this->rowValue($row, $headerMap, ['jenistagihan', 'billingtype', 'tipetagihan', 'gratis'], $fallback['billing_type']));
            $billingMode = $this->normalizeBillingMode($this->rowValue($row, $headerMap, ['billingmode', 'sistempembayaran', 'modepembayaran'], $fallback['billing_mode']));
            $billingNote = $this->rowValue($row, $headerMap, ['catatangratis', 'billingnote', 'keterangangratis'], $fallback['billing_note']) ?: null;

            $packageId = isset($packages[$packageName]) ? $packages[$packageName] : null;
            $odpId     = isset($odps[$odpName])     ? $odps[$odpName]     : null;

            if (!$packageId) {
                // Try to find by partial match
                foreach ($packages as $pName => $pId) {
                    if (stripos($pName, $packageName) !== false || stripos($packageName, $pName) !== false) {
                        $packageId = $pId;
                        break;
                    }
                }
            }

            if (!$packageId) {
                $defaultPackage = Package::orderBy('id')->first();
                if ($defaultPackage) {
                    $packageId = $defaultPackage->id;
                    $errors[] = "Baris " . ($i + 2) . ": Paket '$packageName' tidak ditemukan, otomatis memakai '{$defaultPackage->name}'.";
                } else {
                    $errors[] = "Baris " . ($i + 2) . ": Paket '$packageName' tidak ditemukan dan belum ada paket di database, baris dilewati.";
                    $skippedCount++;
                    continue;
                }
            }

            try {
                Customer::updateOrCreate(
                    ['phone' => $phone],
                    [
                    'package_id'    => $packageId,
                    'odp_id'        => $odpId,
                    'name'          => $name,
                    'email'         => $this->normalizeEmail($this->rowValue($row, $headerMap, ['email'], $fallback['email'])),
                    'phone'         => $phone,
                    'address'       => $address,
                    'status'        => $status,
                    'billing_day'   => $billingDay,
                    'billing_type'  => $billingType,
                    'billing_mode'  => $billingMode,
                    'billing_note'  => $billingNote,
                    'tanggal_pasang'=> $this->normalizeImportDate($this->rowValue($row, $headerMap, ['tglpasang', 'tanggalpasang'], $fallback['tanggal_pasang'])),
                    'pppoe_username'=> $this->rowValue($row, $headerMap, ['pppoeusername', 'usernamepppoe', 'pppoe'], $fallback['pppoe_username']) ?: null,
                    'status_rumah'  => $this->normalizeHomeStatus($this->rowValue($row, $headerMap, ['statusrumah'], $fallback['status_rumah'])),
                    'pemilik_rumah' => $this->rowValue($row, $headerMap, ['pemilikrumah'], $fallback['pemilik_rumah']) ?: null,
                    'jalur'         => $this->normalizeConnectionType($this->rowValue($row, $headerMap, ['jalur'], $fallback['jalur'])),
                    'perangkat'     => $this->rowValue($row, $headerMap, ['perangkat'], $fallback['perangkat']) ?: null,
                    ]
                );
            } catch (\Throwable $e) {
                $errors[] = "Baris " . ($i + 2) . ": gagal disimpan (" . $e->getMessage() . ").";
                $skippedCount++;
                continue;
            }

            $importedCount++;
        }

        $message = "Berhasil mengimpor $importedCount pelanggan.";
        if ($skippedCount > 0) {
            $message .= " $skippedCount baris dilewati.";
        }
        if (!empty($errors)) {
            $message .= ' Catatan: ' . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= ' ... dan ' . (count($errors) - 3) . ' pesan lainnya.';
            }
        }

        return redirect()
            ->route('customers.index')
            ->with($importedCount > 0 ? 'success' : 'error', $message);
    }

    private function customerImportHeaderMap(array $header): array
    {
        $map = [];

        foreach ($header as $index => $label) {
            $key = $this->normalizeImportHeader((string) $label);
            if ($key !== '') {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    private function normalizeImportHeader(string $label): string
    {
        $label = strtolower(trim($label));
        $label = str_replace(['.', '_', '-', '/', '\\', ' '], '', $label);

        return preg_replace('/[^a-z0-9]/', '', $label) ?: '';
    }

    private function hasRecognizedCustomerImportHeader(array $headerMap): bool
    {
        $knownHeaders = [
            'id',
            'nama',
            'name',
            'namapelanggan',
            'pelanggan',
            'notelepon',
            'telepon',
            'phone',
            'hp',
            'nohp',
            'alamat',
            'address',
            'paket',
            'package',
            'odp',
            'status',
        ];

        foreach ($knownHeaders as $header) {
            if (array_key_exists($header, $headerMap)) {
                return true;
            }
        }

        return false;
    }

    private function rowValue(array $row, array $headerMap, array $keys, ?int $fallbackIndex): string
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeImportHeader($key);
            if (array_key_exists($normalizedKey, $headerMap)) {
                return trim((string) ($row[$headerMap[$normalizedKey]] ?? ''));
            }
        }

        if ($fallbackIndex === null) {
            return '';
        }

        return trim((string) ($row[$fallbackIndex] ?? ''));
    }

    private function isEmptyImportRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\.0$/', '', $phone);
        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($phone, '62')) {
            return '0' . substr($phone, 2);
        }

        return $phone;
    }

    private function normalizeHomeStatus(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['ngontrak', 'kontrak', 'sewa'], true) ? 'ngontrak' : 'milik_sendiri';
    }

    private function normalizeConnectionType(string $value): ?string
    {
        $value = strtoupper(trim($value));

        return in_array($value, ['FO', 'LAN'], true) ? $value : null;
    }

    private function normalizeBillingType(string $value): string
    {
        $value = $this->normalizeImportHeader($value);

        $map = [
            '' => 'paid',
            'berbayar' => 'paid',
            'paid' => 'paid',
            'normal' => 'paid',
            'regular' => 'paid',
            'gratis' => 'other_free',
            'free' => 'other_free',
            'lainnya' => 'other_free',
            'otherfree' => 'other_free',
            'admin' => 'admin',
            'gratisadmin' => 'admin',
            'sosial' => 'social',
            'social' => 'social',
            'gratissosial' => 'social',
            'gratissocial' => 'social',
            'keluarga' => 'family',
            'family' => 'family',
            'gratiskeluarga' => 'family',
            'anakyatim' => 'orphan',
            'yatim' => 'orphan',
            'orphan' => 'orphan',
            'gratisanakyatim' => 'orphan',
            'rt' => 'rt',
            'jatahrt' => 'rt',
            'gratisrt' => 'rt',
        ];

        return $map[$value] ?? 'paid';
    }

    private function normalizeBillingMode(?string $value): string
    {
        $value = $this->normalizeImportHeader((string) $value);

        return in_array($value, ['prabayar', 'prepaid', 'pra bayar'], true)
            ? Customer::MODE_PREPAID
            : Customer::MODE_POSTPAID;
    }

    private function normalizeEmail(string $email): ?string
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizeImportDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (int) $value;
            if ($serial > 0) {
                return \Carbon\Carbon::create(1899, 12, 30)->addDays($serial)->format('Y-m-d');
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd M Y', 'd F Y'];
        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function readCsvRows(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        $firstLine = fgets($handle) ?: '';
        rewind($handle);

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readSpreadsheetMlRows(string $path): array
    {
        $rows = [];
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        try {
            $xml = @simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
            if (!$xml) {
                return [];
            }

            $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
            $xmlRows = $xml->xpath('//ss:Row') ?: [];

            foreach ($xmlRows as $xmlRow) {
                $cells = [];
                $currentIndex = 1;

                foreach ($xmlRow->children('urn:schemas-microsoft-com:office:spreadsheet')->Cell as $cell) {
                    $attributes = $cell->attributes('urn:schemas-microsoft-com:office:spreadsheet');
                    if (isset($attributes['Index'])) {
                        $currentIndex = (int) $attributes['Index'];
                    }

                    while (count($cells) < $currentIndex - 1) {
                        $cells[] = '';
                    }

                    $data = $cell->children('urn:schemas-microsoft-com:office:spreadsheet')->Data;
                    $cells[] = (string) $data;
                    $currentIndex++;
                }

                $rows[] = $cells;
            }
        } catch (\Throwable) {
            return [];
        }

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return [];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = $this->readXlsxSharedStrings($zip);
        $sheetPath = $this->firstXlsxSheetPath($zip);
        $sheetXml = $sheetPath ? $zip->getFromName($sheetPath) : false;

        if ($sheetXml === false) {
            $zip->close();
            return [];
        }

        $xml = @simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
        if (!$xml) {
            $zip->close();
            return [];
        }

        $xml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xmlRows = $xml->xpath('//main:sheetData/main:row') ?: [];
        $rows = [];

        foreach ($xmlRows as $xmlRow) {
            $cells = [];
            foreach ($xmlRow->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->c as $cell) {
                $attributes = $cell->attributes();
                $cellRef = (string) ($attributes['r'] ?? '');
                $type = (string) ($attributes['t'] ?? '');
                $columnIndex = $cellRef ? $this->xlsxColumnIndex($cellRef) : count($cells);

                while (count($cells) < $columnIndex) {
                    $cells[] = '';
                }

                $value = '';
                if ($type === 's') {
                    $index = (int) ($cell->v ?? 0);
                    $value = $sharedStrings[$index] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                $cells[$columnIndex] = $value;
            }

            $rows[] = $cells;
        }

        $zip->close();

        return $rows;
    }

    private function readXlsxSharedStrings(\ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');
        if ($content === false) {
            return [];
        }

        $xml = @simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
        if (!$xml) {
            return [];
        }

        $strings = [];
        foreach ($xml->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->si as $si) {
            $parts = [];
            foreach ($si->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                $parts[] = (string) $text;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function firstXlsxSheetPath(\ZipArchive $zip): ?string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbook === false || $rels === false) {
            return $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : null;
        }

        $workbookXml = @simplexml_load_string($workbook, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
        $relsXml = @simplexml_load_string($rels, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);

        if (!$workbookXml || !$relsXml) {
            return null;
        }

        $workbookXml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbookXml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheets = $workbookXml->xpath('//main:sheets/main:sheet') ?: [];

        if (empty($sheets)) {
            return null;
        }

        $sheetAttributes = $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId = (string) ($sheetAttributes['id'] ?? '');

        foreach ($relsXml->Relationship as $relationship) {
            $attributes = $relationship->attributes();
            if ((string) $attributes['Id'] === $relationshipId) {
                $target = ltrim((string) $attributes['Target'], '/');
                return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            }
        }

        return null;
    }

    private function xlsxColumnIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/i', $cellRef, $matches);
        $letters = strtoupper($matches[1] ?? 'A');
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }
}
