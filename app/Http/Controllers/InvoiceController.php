<?php

namespace App\Http\Controllers;

use App\Services\Invoices\InvoiceImporter;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\WaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Services\WhatsAppGateway;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $now = Carbon::now();
        $selectedMonth = $request->input('month', $now->month);
        $selectedYear = $request->input('year', $now->year);
        $selectedStatus = (string) $request->input('status', '');
        $selectedBillingType = (string) $request->input('billing_type', '');
        $selectedBillingMode = (string) $request->input('billing_mode', '');

        // The filter form submits empty values for "Semua". Treat common
        // aliases as empty too, so an all-data request never filters to a
        // literal value such as "all".
        $selectedMonth = is_numeric($selectedMonth) && (int) $selectedMonth >= 1 && (int) $selectedMonth <= 12
            ? (int) $selectedMonth
            : '';
        $selectedYear = is_numeric($selectedYear) && (int) $selectedYear >= 2000 && (int) $selectedYear <= 2100
            ? (int) $selectedYear
            : '';
        foreach (['selectedStatus', 'selectedBillingType', 'selectedBillingMode'] as $filterKey) {
            if (in_array(strtolower(trim($$filterKey)), ['', 'all', 'semua', 'semua status', 'semua tagihan', 'semua jenis', 'semua mode'], true)) {
                $$filterKey = '';
            }
        }
        // Normalisasi parameter kosong agar URL seperti ?aging tidak menjadi null.
        $selectedAging = (string) $request->input('aging', '');
        if (in_array(strtolower(trim($selectedAging)), ['', 'all', 'semua'], true)) {
            $selectedAging = '';
        }
        $search = $request->get('search', '');

        if ($selectedAging !== '') {
            $selectedMonth = '';
            $selectedYear = '';
            $selectedStatus = 'unpaid';
        }

        $query = Invoice::with([
            'customer.package',
            'customer.invoices' => function($q) {
                $q->orderBy('year', 'desc')->orderBy('month', 'desc')->orderBy('id', 'desc');
            }
        ]);

        if (!empty($selectedMonth)) {
            $query->where('month', $selectedMonth);
        }
        if (!empty($selectedYear)) {
            $query->where('year', $selectedYear);
        }
        if (!empty($selectedStatus)) {
            $query->where('status', $selectedStatus);
        }
        if ($selectedBillingType === 'paid') {
            $query->whereHas('customer', function ($q) {
                $q->whereNull('billing_type')->orWhere('billing_type', Customer::BILLING_PAID);
            });
        } elseif ($selectedBillingType === 'free') {
            $query->whereHas('customer', function ($q) {
                $q->whereNotNull('billing_type')->where('billing_type', '<>', Customer::BILLING_PAID);
            });
        } elseif (!empty($selectedBillingType)) {
            $query->whereHas('customer', fn ($q) => $q->where('billing_type', $selectedBillingType));
        }
        if ($selectedBillingMode !== '') {
            $query->whereHas('customer', fn ($q) => $q->where('billing_mode', $selectedBillingMode));
        }
        if (!empty($search)) {
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $agingCustomers = collect();
        if ($selectedAging !== '') {
            $agingCustomers = Customer::withCount([
                'invoices as unpaid_invoice_count' => function ($q) {
                    $q->where('status', 'unpaid');
                },
            ])->withSum([
                'invoices as unpaid_invoice_total' => function ($q) {
                    $q->where('status', 'unpaid');
                },
            ], 'amount')->get()
                ->filter(fn (Customer $customer) => $this->matchesAgingBucket((int) $customer->unpaid_invoice_count, $selectedAging));

            $query->where('status', 'unpaid')->whereIn('customer_id', $agingCustomers->pluck('id'));
        }

        $invoices = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();
        $waTemplates = \App\Models\WaTemplate::orderBy('title', 'asc')->get();
        $advancePaymentCustomers = Customer::with('package')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->where(function ($q) {
                $q->whereNull('billing_type')->orWhere('billing_type', Customer::BILLING_PAID);
            })
            ->whereNotNull('package_id')
            ->orderBy('name')
            ->get();
        $postpaidInvoiceCustomers = Customer::with('package')
            ->whereIn('status', ['active', 'isolated', 'stopped'])
            ->where(function ($q) {
                $q->whereNull('billing_type')->orWhere('billing_type', Customer::BILLING_PAID);
            })
            ->where(function ($q) {
                $q->whereNull('billing_mode')->orWhere('billing_mode', Customer::MODE_POSTPAID);
            })
            ->whereNotNull('package_id')
            ->orderBy('name')
            ->get();

        // Get list of years for filter
        $years = Invoice::select('year')->distinct()->orderBy('year', 'desc')->pluck('year')->toArray();
        if (empty($years)) {
            $years = [$now->year];
        }

        return view('invoices.index', compact(
            'invoices',
            'selectedMonth',
            'selectedYear',
            'selectedStatus',
            'selectedBillingType',
            'selectedBillingMode',
            'selectedAging',
            'search',
            'years',
            'waTemplates',
            'advancePaymentCustomers',
            'postpaidInvoiceCustomers',
            'agingCustomers'
        ));
    }

    private function matchesAgingBucket(int $unpaidCount, ?string $bucket): bool
    {
        return match ($bucket) {
            '1' => $unpaidCount === 1,
            '2_3' => $unpaidCount >= 2 && $unpaidCount <= 3,
            '4_6' => $unpaidCount >= 4 && $unpaidCount <= 6,
            '7_12' => $unpaidCount >= 7 && $unpaidCount <= 12,
            '12_plus' => $unpaidCount >= 13,
            default => false,
        };
    }

    public function broadcastOverdue(Request $request)
    {
        $data = $request->validate([
            'aging' => ['required', 'in:1,2_3,4_6,7_12,12_plus'],
            'wa_template_id' => ['nullable', 'exists:wa_templates,id'],
            'delay_min' => ['required', 'integer', 'min:0', 'max:3600'],
            'delay_max' => ['required', 'integer', 'min:0', 'max:3600', 'gte:delay_min'],
        ]);

        $customers = Customer::with([
            'package',
            'invoices' => function ($query) {
                $query->where('status', 'unpaid')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->orderBy('id');
            },
        ])->whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->where(function ($query) {
                $query->whereNull('billing_type')->orWhere('billing_type', Customer::BILLING_PAID);
            })
            ->whereHas('invoices', fn ($query) => $query->where('status', 'unpaid'))
            ->get()
            ->filter(fn (Customer $customer) => $this->matchesAgingBucket($customer->invoices->count(), $data['aging']));

        if ($customers->isEmpty()) {
            return redirect()->back()->with('info', 'Tidak ada pelanggan yang sesuai kelompok umur tunggakan tersebut.');
        }

        $template = !empty($data['wa_template_id']) ? WaTemplate::find($data['wa_template_id']) : null;
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $delayMin = (int) $data['delay_min'];
        $delayMax = (int) $data['delay_max'];

        foreach ($customers as $customer) {
            if (!$customer->phone) {
                $skipped++;
                continue;
            }

            $message = $this->renderOverdueBroadcastMessage($customer, $template?->content);
            $result = app(WhatsAppGateway::class)->send($customer->phone, $message);

            if ($result['success']) {
                $sent++;
                if ($delayMax > 0) {
                    sleep(random_int($delayMin, $delayMax));
                }
            } else {
                $failed++;
                Log::warning('Overdue customer broadcast failed.', [
                    'customer_id' => $customer->id,
                    'error' => $result['message'] ?? 'Unknown WA gateway error',
                ]);
            }
        }

        return redirect()->back()->with(
            $failed > 0 ? 'warning' : 'success',
            "Broadcast tunggakan selesai: {$sent} terkirim, {$failed} gagal, {$skipped} dilewati karena nomor WA kosong."
        );
    }

    private function renderOverdueBroadcastMessage(Customer $customer, ?string $templateContent): string
    {
        $invoices = $customer->invoices;
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $invoiceNumbers = $invoices->pluck('invoice_number')->implode(', ');
        $periods = $invoices->map(fn ($invoice) => ($monthNames[(int) $invoice->month] ?? $invoice->month) . ' ' . $invoice->year)->implode(', ');
        $total = (int) $invoices->sum('amount');
        $oldest = $invoices->first();
        $billingDay = max(1, min((int) ($customer->billing_day ?: 1), Carbon::create((int) $oldest->year, (int) $oldest->month, 1)->daysInMonth));
        $dueDate = Carbon::create((int) $oldest->year, (int) $oldest->month, $billingDay)->translatedFormat('d F Y');
        $message = trim((string) $templateContent) !== ''
            ? $templateContent
            : "Halo {nama_pelanggan},\n\nKami informasikan masih ada {jumlah_tunggakan} periode tagihan yang belum lunas.\nPeriode: {periode}\nNomor tagihan: {nomor_tagihan}\nTotal tunggakan: {nominal}\nJatuh tempo tertua: {tanggal_jatuh_tempo}\n\nMohon segera melakukan pembayaran. Terima kasih.";

        return strtr($message, [
            '{nama}' => $customer->name,
            '{nama_pelanggan}' => $customer->name,
            '{nomor_tagihan}' => $invoiceNumbers,
            '{no_invoice}' => $invoiceNumbers,
            '{periode}' => $periods,
            '{bulan}' => $periods,
            '{nominal}' => 'Rp ' . number_format($total, 0, ',', '.'),
            '{jumlah}' => 'Rp ' . number_format($total, 0, ',', '.'),
            '{jumlah_tunggakan}' => (string) $invoices->count(),
            '{tanggal_jatuh_tempo}' => $dueDate,
            '{nama_odp}' => $customer->odp?->name ?? '-',
            '{odp}' => $customer->odp?->name ?? '-',
        ]);
    }

    public function generateForCustomer(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $customer = Customer::with('package')->findOrFail($data['customer_id']);

        if (!$customer->isBillable()) {
            return redirect()->back()->with('error', 'Pelanggan gratis/admin/sosial tidak dibuatkan tagihan.');
        }

        if (!in_array($customer->status, ['active', 'isolated', 'stopped'], true)) {
            return redirect()->back()->with('error', 'Status pelanggan tidak tersedia untuk generate per pelanggan.');
        }

        if ($customer->isPrepaid()) {
            return redirect()->back()->with('error', 'Pelanggan Prabayar tidak memakai generate invoice bulanan. Gunakan fitur Bayar di Muka.');
        }

        if (!$customer->package) {
            return redirect()->back()->with('error', 'Pelanggan belum memiliki paket layanan.');
        }

        $period = Carbon::create((int) $data['year'], (int) $data['month'], 1)->startOfMonth();
        $billingDay = max(1, min((int) ($customer->billing_day ?: 1), $period->daysInMonth));
        $dueDate = $period->copy()->day($billingDay)->startOfDay();
        $installationDate = $customer->tanggal_pasang ? Carbon::parse($customer->tanggal_pasang)->startOfDay() : null;

        if ($installationDate && $installationDate->gt($dueDate)) {
            return redirect()->back()->with('error', 'Periode yang dipilih sebelum tanggal pasang pelanggan, sehingga tagihan tidak dibuat.');
        }

        $exists = Invoice::where('customer_id', $customer->id)
            ->where('month', $period->month)
            ->where('year', $period->year)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('info', "Invoice {$customer->name} untuk {$period->translatedFormat('F Y')} sudah tersedia.");
        }

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'month' => $period->month,
            'year' => $period->year,
            'amount' => $customer->package->price,
            'status' => 'unpaid',
        ]);

        return redirect()->back()->with('success', "Invoice {$invoice->invoice_number} untuk {$customer->name} berhasil dibuat.");
    }

    /**
     * Mark one or more invoices as paid (batch payment).
     */
    public function payBatch(\App\Http\Requests\Invoice\PayBatchInvoiceRequest $request, \App\Services\InvoiceService $invoiceService)
    {
        $data = $request->validated();
        $proofFile = $request->file('payment_proof');

        $result = $invoiceService->payBatch($data['invoice_ids'], $data['payment_method'], $proofFile, $data['paid_at'] ?? null);

        if ($result['status'] === 'error') {
            return redirect()->back()->with('error', $result['message']);
        }

        if (!empty($data['send_wa']) && $result['customer'] && $result['customer']->phone) {
            $waTemplate = !empty($data['wa_template_id']) ? WaTemplate::find($data['wa_template_id']) : null;
            $waMessage = $invoiceService->buildPaymentReceiptMessage(
                $result['customer'], 
                $result['invoices'], 
                $result['totalAmount'], 
                $result['count'], 
                $data['payment_method'],
                $result['remainingUnpaidInvoices'] ?? collect(),
                $waTemplate?->content,
                $result['paidAt'] ?? null
            );

            $waResult = app(WhatsAppGateway::class)->send($result['customer']->phone, $waMessage);
            session(['invoice_payment_wa_template_id' => $waTemplate?->id]);

            if (!$waResult['success']) {
                $manualWaUrl = $invoiceService->buildWaRedirectUrl(
                    $result['customer'],
                    $result['invoices'],
                    $result['totalAmount'],
                    $result['count'],
                    $data['payment_method'],
                    $result['remainingUnpaidInvoices'] ?? collect(),
                    $waTemplate?->content,
                    $result['paidAt'] ?? null
                );

                return redirect()->back()
                    ->with('warning', $result['message'] . ' ' . $waResult['message'])
                    ->with('wa_manual_url', $manualWaUrl);
            }

            return redirect()->back()
                ->with('success', $result['message'] . ' WA Gateway: ' . $waResult['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    public function advancePayment(Request $request, \App\Services\InvoiceService $invoiceService)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'start_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month_count' => ['required', 'integer', 'min:1', 'max:24'],
            'payment_method' => ['required', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'send_wa' => ['nullable', 'boolean'],
            'wa_template_id' => ['nullable', 'exists:wa_templates,id'],
        ]);

        $customer = Customer::with('package')->findOrFail($data['customer_id']);

        if (!$customer->isBillable()) {
            return redirect()->back()->with('error', 'Pelanggan ini termasuk gratis/admin/sosial, jadi tidak dibuatkan tagihan bayar di muka.');
        }

        if (!$customer->package) {
            return redirect()->back()->with('error', 'Pelanggan belum memiliki paket, tagihan bayar di muka belum bisa dibuat.');
        }

        $createdPeriods = [];
        $existingUnpaidPeriods = [];
        $skippedPaidPeriods = [];
        $invoiceIds = [];

        DB::transaction(function () use ($customer, $data, &$createdPeriods, &$existingUnpaidPeriods, &$skippedPaidPeriods, &$invoiceIds) {
            $period = Carbon::create((int) $data['start_year'], (int) $data['start_month'], 1)->startOfMonth();

            for ($i = 0; $i < (int) $data['month_count']; $i++) {
                $monthDate = $period->copy()->addMonths($i);
                $periodLabel = $monthDate->translatedFormat('F Y');

                $invoice = Invoice::where('customer_id', $customer->id)
                    ->where('month', $monthDate->month)
                    ->where('year', $monthDate->year)
                    ->first();

                if ($invoice && $invoice->status === 'paid') {
                    $skippedPaidPeriods[] = $periodLabel;
                    continue;
                }

                if (!$invoice) {
                    $invoice = Invoice::create([
                        'customer_id' => $customer->id,
                        'month' => $monthDate->month,
                        'year' => $monthDate->year,
                        'amount' => $customer->package->price,
                        'status' => 'unpaid',
                    ]);
                    $createdPeriods[] = $periodLabel;
                } else {
                    $existingUnpaidPeriods[] = $periodLabel;
                }

                $invoiceIds[] = $invoice->id;
            }
        });

        if (empty($invoiceIds)) {
            $message = 'Tidak ada tagihan yang bisa dibayar di muka.';
            if ($skippedPaidPeriods) {
                $message .= ' Periode sudah lunas: ' . implode(', ', $skippedPaidPeriods) . '.';
            }

            return redirect()->back()->with('info', $message);
        }

        $result = $invoiceService->payBatch($invoiceIds, $data['payment_method'], $request->file('payment_proof'), $data['paid_at'] ?? null);

        if ($result['status'] === 'error') {
            return redirect()->back()->with('error', $result['message']);
        }

        $prefix = 'Bayar di muka berhasil. ';
        if ($createdPeriods) {
            $prefix .= 'Tagihan baru dibuat: ' . implode(', ', $createdPeriods) . '. ';
        }
        if ($existingUnpaidPeriods) {
            $prefix .= 'Tagihan yang sudah ada ikut dibayar: ' . implode(', ', $existingUnpaidPeriods) . '. ';
        }
        if ($skippedPaidPeriods) {
            $prefix .= 'Dilewati karena sudah lunas: ' . implode(', ', $skippedPaidPeriods) . '. ';
        }

        if (!empty($data['send_wa']) && $result['customer'] && $result['customer']->phone) {
            $waTemplate = !empty($data['wa_template_id']) ? WaTemplate::find($data['wa_template_id']) : null;
            $waMessage = $invoiceService->buildPaymentReceiptMessage(
                $result['customer'],
                $result['invoices'],
                $result['totalAmount'],
                $result['count'],
                $data['payment_method'],
                $result['remainingUnpaidInvoices'] ?? collect(),
                $waTemplate?->content,
                $result['paidAt'] ?? null
            );
            $waResult = app(WhatsAppGateway::class)->send($result['customer']->phone, $waMessage);
            session(['invoice_payment_wa_template_id' => $waTemplate?->id]);

            if (!$waResult['success']) {
                $manualWaUrl = $invoiceService->buildWaRedirectUrl(
                    $result['customer'],
                    $result['invoices'],
                    $result['totalAmount'],
                    $result['count'],
                    $data['payment_method'],
                    $result['remainingUnpaidInvoices'] ?? collect(),
                    $waTemplate?->content,
                    $result['paidAt'] ?? null
                );

                return redirect()->back()
                    ->with('warning', $prefix . $result['message'] . ' ' . $waResult['message'])
                    ->with('wa_manual_url', $manualWaUrl);
            }

            return redirect()->back()->with('success', $prefix . $result['message'] . ' WA Gateway: ' . $waResult['message']);
        }

        return redirect()->back()->with('success', $prefix . $result['message']);
    }

    public function updatePayment(Request $request, Invoice $invoice)
    {
        $hasPaymentProofColumn = Schema::hasColumn('invoices', 'payment_proof');

        $data = $request->validate([
            'status' => ['required', 'in:paid,unpaid'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'paid_at' => ['nullable', 'date'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:3072'],
            'remove_payment_proof' => ['nullable', 'boolean'],
        ]);

        $updateData = [
            'status' => $data['status'],
            'payment_method' => null,
            'paid_at' => null,
        ];

        if ($data['status'] === 'paid') {
            $paymentMethod = $data['payment_method'] ?? null;
            $paidAt = $data['paid_at'] ?? null;

            $updateData['payment_method'] = filled($paymentMethod) ? $paymentMethod : ($invoice->payment_method ?: 'Cash');
            $updateData['paid_at'] = !empty($paidAt)
                ? Carbon::parse($paidAt)
                : ($invoice->paid_at ?: now());
        }

        try {
            if ($hasPaymentProofColumn && ($request->boolean('remove_payment_proof') || $request->hasFile('payment_proof') || $data['status'] === 'unpaid')) {
                $currentProof = $invoice->getAttribute('payment_proof');

                if ($currentProof && Storage::disk('public')->exists($currentProof)) {
                    Storage::disk('public')->delete($currentProof);
                }

                $updateData['payment_proof'] = null;
            }

            if ($hasPaymentProofColumn && $data['status'] === 'paid' && $request->hasFile('payment_proof')) {
                $updateData['payment_proof'] = $request->file('payment_proof')->store('uploads/payment_proofs', 'public');
            }

            $invoice->update($updateData);
        } catch (\Throwable $exception) {
            Log::error('Failed to update invoice payment.', [
                'invoice_id' => $invoice->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Gagal menyimpan edit pembayaran: ' . $exception->getMessage());
        }

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Data pembayaran berhasil diperbarui.');
    }

    public function updatePaymentPromise(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'payment_promise_date' => ['nullable', 'date'],
            'payment_promise_note' => ['nullable', 'string', 'max:1000'],
            'payment_promise_wa_template_id' => ['nullable', 'exists:wa_templates,id'],
            'clear_promise' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('clear_promise')) {
            $invoice->forceFill([
                'payment_promise_date' => null,
                'payment_promise_note' => null,
                'payment_promise_wa_template_id' => null,
                'payment_promise_wa_sent_at' => null,
            ])->save();

            return redirect()
                ->back()
                ->with('success', 'Janji bayar berhasil dihapus.');
        }

        if (empty($data['payment_promise_date'])) {
            return redirect()
                ->back()
                ->with('error', 'Tanggal janji bayar wajib diisi.');
        }

        $invoice->forceFill([
            'payment_promise_date' => Carbon::parse($data['payment_promise_date'])->toDateString(),
            'payment_promise_note' => $data['payment_promise_note'] ?? null,
            'payment_promise_wa_template_id' => $data['payment_promise_wa_template_id'] ?? null,
            'payment_promise_wa_sent_at' => null,
        ])->save();

        return redirect()
            ->back()
            ->with('success', 'Janji bayar berhasil disimpan. WA otomatis akan dikirim pada tanggal janji jika invoice masih belum lunas.');
    }

    public function paymentProof(Invoice $invoice)
    {
        abort_unless($invoice->payment_proof, 404);
        abort_unless(Storage::disk('public')->exists($invoice->payment_proof), 404);

        $path = Storage::disk('public')->path($invoice->payment_proof);
        $mime = Storage::disk('public')->mimeType($invoice->payment_proof) ?: 'image/jpeg';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * Bulk generate invoices for all active customers for a selected month/year.
     */
    public function generate(\App\Http\Requests\Invoice\GenerateInvoiceRequest $request, \App\Services\InvoiceService $invoiceService)
    {
        $data = $request->validated();
        $waTemplate = !empty($data['wa_template_id']) ? WaTemplate::find($data['wa_template_id']) : null;
        $result = $invoiceService->generateMonthlyInvoices(
            (int) $data['month'],
            (int) $data['year'],
            $waTemplate?->title,
            (bool) ($data['send_wa'] ?? false),
            $waTemplate?->content
        );

        session(['invoice_generate_wa_template_id' => $waTemplate?->id]);

        return redirect()->back()->with($result['status'], $result['message']);
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->back()->with('success', 'Tagihan berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer', 'distinct'],
        ]);

        $deleted = Invoice::whereIn('id', $data['invoice_ids'])->delete();

        return redirect()->back()->with(
            $deleted > 0 ? 'success' : 'info',
            $deleted > 0
                ? "{$deleted} tagihan terpilih berhasil dihapus."
                : 'Tidak ada tagihan yang dapat dihapus.'
        );
    }

    /**
     * Display the invoice detail page.
     */
    public function show(Invoice $invoice)
    {
        return $this->print($invoice);
    }

    /**
     * Print the invoice receipt.
     */
    public function print(Invoice $invoice)
    {
        $invoice->load(['customer.package']);
        return view('invoices.print', compact('invoice'));
    }

    /**
     * Export all invoices to Excel XML (.xls) — no external library needed.
     */
    public function export(Request $request)
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $query = Invoice::with(['customer.package'])->orderBy('year', 'desc')->orderBy('month', 'desc')->orderBy('id', 'desc');

        // Optionally filter by month/year from query string
        if ($request->filled('month')) $query->where('month', $request->month);
        if ($request->filled('year'))  $query->where('year', $request->year);
        if ($request->filled('status')) $query->where('status', $request->status);

        $invoices = $query->get();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= '  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Worksheet ss:Name="Tagihan"><Table>' . "\n";

        // Header row
        $headers = [
            'No. Invoice', 'Nama Pelanggan', 'No. Telepon',
            'Paket', 'Bulan', 'Tahun', 'Jumlah (Rp)',
            'Status', 'Metode Bayar', 'Tanggal Bayar',
        ];
        $xml .= '<Row>' . "\n";
        foreach ($headers as $h) {
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($h, ENT_XML1) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";

        foreach ($invoices as $inv) {
            $xml .= '<Row>' . "\n";
            $cols = [
                $inv->invoice_number,
                $inv->customer ? $inv->customer->name : '',
                $inv->customer ? $inv->customer->phone : '',
                $inv->customer && $inv->customer->package ? $inv->customer->package->name : '',
                $monthNames[$inv->month] ?? $inv->month,
                $inv->year,
                $inv->amount,
                $inv->status === 'paid' ? 'Lunas' : 'Belum Lunas',
                $inv->payment_method ?? '',
                $inv->paid_at ? $inv->paid_at->format('Y-m-d H:i') : '',
            ];
            foreach ($cols as $col) {
                $type = is_numeric($col) && !preg_match('/^0\d/', (string)$col) ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars((string)$col, ENT_XML1) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        $filename = 'tagihan_' . date('Ymd_His') . '.xls';

        return response($xml, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
    /**
     * Import tagihan dari file Excel/CSV (logika di App\Services\Invoices\InvoiceImporter).
     */
    public function import(Request $request, InvoiceImporter $importer)
    {
        // File .xls hasil export sistem berformat XML, jadi ekstensi diperiksa manual (bukan "mimes").
        $request->validate([
            'import_file' => 'required|file|max:5120',
        ]);

        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, InvoiceImporter::EXTENSIONS, true)) {
            return redirect()->route('invoices.index')
                ->with('error', 'Format file tidak didukung. Gunakan file .xlsx, .xls, .csv, atau .txt.');
        }

        try {
            $result = $importer->import($file->getRealPath(), $extension);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('invoices.index')
                ->with('error', 'File tidak bisa dibaca. Simpan ulang file dari Excel lalu coba lagi.');
        }

        if ($result['rows'] === 0) {
            return redirect()->route('invoices.index')->with('error', 'File kosong atau format tidak dikenali.');
        }

        $message = "Berhasil mengimpor/memperbarui {$result['imported']} tagihan.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} baris dilewati.";
        }
        if (!empty($result['errors'])) {
            $message .= ' Catatan: ' . implode('; ', array_slice($result['errors'], 0, 3));
            if (count($result['errors']) > 3) {
                $message .= ' ... dan ' . (count($result['errors']) - 3) . ' lainnya.';
            }
        }

        return redirect()->route('invoices.index')->with('success', $message);
    }

}
