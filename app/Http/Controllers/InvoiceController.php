<?php

namespace App\Http\Controllers;

use App\Support\SpreadsheetReader;
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
     * Import invoices from .xls (SpreadsheetML) or .csv file.
     *
     * Expected columns:
     * No.Invoice | Nama Pelanggan | No.Telepon | Paket | Bulan | Tahun | Jumlah | Status | Metode Bayar | Tanggal Bayar
     */
    public function import(Request $request)
    {
        // Catatan: file .xls hasil export sistem berformat XML, sehingga aturan "mimes:xls"
        // selalu menolaknya. Ekstensi diperiksa manual (sama seperti import pelanggan).
        $request->validate([
            'import_file' => 'required|file|max:5120',
        ]);

        if (!in_array(strtolower($request->file('import_file')->getClientOriginalExtension()), ['csv', 'txt', 'xls', 'xlsx'], true)) {
            return redirect()->route('invoices.index')
                ->with('error', 'Format file tidak didukung. Gunakan file .xlsx, .xls, .csv, atau .txt.');
        }

        $monthMap = [
            'januari'=>1,'februari'=>2,'maret'=>3,'april'=>4,
            'mei'=>5,'juni'=>6,'juli'=>7,'agustus'=>8,
            'september'=>9,'oktober'=>10,'november'=>11,'desember'=>12,
        ];

        $file      = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $rows      = [];

        if ($extension === 'xlsx') {
            try {
                // Untuk XLSX: nilai sel di-trim dan baris tanpa sel dilewati (perilaku lama import invoice).
                $rows = array_values(array_filter(
                    array_map(fn ($row) => array_map('trim', $row), SpreadsheetReader::xlsx($file->getRealPath())),
                    fn ($row) => $row !== []
                ));
            } catch (\Throwable $e) {
                return redirect()->route('invoices.index')
                    ->with('error', 'Format file XLSX tidak valid atau tidak bisa dibaca. Simpan ulang file dari Excel lalu coba lagi.');
            }
        } else {
            $rows = SpreadsheetReader::read($file->getRealPath(), $extension);
        }

        if (empty($rows)) {
            return redirect()->route('invoices.index')->with('error', 'File kosong atau format tidak dikenali.');
        }

        $header = array_shift($rows);
        $headerMap = $this->invoiceImportHeaderMap($header ?? []);
        $hasMappedHeader = $this->hasInvoiceImportHeader($headerMap);

        if (!$hasMappedHeader && !empty($header)) {
            array_unshift($rows, $header);
        }

        $fallbackColumns = [
            'invoice_number' => 0,
            'customer_name' => 1,
            'phone' => 2,
            'month' => 4,
            'year' => 5,
            'amount' => 6,
            'status' => 7,
            'payment_method' => 8,
            'paid_at' => 9,
        ];

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $i => $row) {
            // Need at least: Nama/Telepon, Bulan, Tahun, Jumlah
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                $skipped++;
                continue;
            }

            // Col 0: No.Invoice (opsional, auto-generate jika kosong)
            // Col 1: Nama Pelanggan
            // Col 2: No. Telepon — digunakan untuk lookup customer
            // Col 3: Paket (opsional)
            // Col 4: Bulan (nama atau angka)
            // Col 5: Tahun
            // Col 6: Jumlah (Rp) — opsional, pakai harga paket jika kosong
            // Col 7: Status
            // Col 8: Metode Bayar
            // Col 9: Tanggal Bayar

            $invoiceNumber = $this->invoiceRowValue($row, $headerMap, $fallbackColumns, 'invoice_number');
            $customerName  = $this->invoiceRowValue($row, $headerMap, $fallbackColumns, 'customer_name');
            $phone         = $this->normalizeInvoicePhone($this->invoiceRowValue($row, $headerMap, $fallbackColumns, 'phone'));
            $bulanRaw      = $this->invoiceRowValue($row, $headerMap, $fallbackColumns, 'month');
            $tahunRaw      = $this->invoiceRowValue($row, $headerMap, $fallbackColumns, 'year');
            $jumlahRaw     = $this->invoiceRowValue($row, $headerMap, $fallbackColumns, 'amount');
            $statusRaw     = $this->invoiceRowValue($row, $headerMap, $fallbackColumns, 'status');
            $metodeBayar   = $this->invoiceRowValue($row, $headerMap, $fallbackColumns, 'payment_method');
            $tanggalBayar  = $this->invoiceRowValue($row, $headerMap, $fallbackColumns, 'paid_at');

            // Resolve month: accept "Januari", "1", "01"
            $month = $this->normalizeInvoiceMonth($bulanRaw, $monthMap);
            $tahun = (int) preg_replace('/[^0-9]/', '', $tahunRaw);

            if ($month < 1 || $month > 12 || $tahun < 2000 || $tahun > 2099) {
                $errors[] = 'Baris ' . ($i + 2) . ': Bulan/Tahun tidak valid.';
                $skipped++;
                continue;
            }

            // Find customer by phone or name
            $customer = $this->findCustomerForInvoiceImport($phone, $customerName);

            if (!$customer) {
                $errors[] = 'Baris ' . ($i + 2) . ": Pelanggan '{$customerName}' ({$phone}) tidak ditemukan.";
                $skipped++;
                continue;
            }

            // Resolve amount — fallback to package price
            $amount = $this->normalizeInvoiceAmount($jumlahRaw) ?: ($customer->package ? $customer->package->price : 0);
            if ($amount <= 0 && $customer->package) {
                $amount = $customer->package->price;
            }

            $status = $this->normalizeInvoiceStatus($statusRaw);
            $paidAt = $this->normalizeInvoicePaidAt($tanggalBayar);

            // Check duplicate
            $exists = Invoice::where('customer_id', $customer->id)
                ->where('month', $month)
                ->where('year', $tahun)
                ->first();

            if ($exists) {
                // Update status/amount if changed
                $updateData = [
                    'amount' => $amount ?: $exists->amount,
                    'status' => $status,
                    'payment_method' => null,
                    'paid_at' => null,
                ];
                if ($status === 'paid') {
                    $updateData['payment_method'] = $metodeBayar ?: ($exists->payment_method ?? 'Cash');
                    $updateData['paid_at'] = $paidAt ?? ($exists->paid_at ?? Carbon::now());
                }
                $exists->update($updateData);
                $imported++;
                continue;
            }

            // Create new invoice
            $data = [
                'customer_id' => $customer->id,
                'month'       => $month,
                'year'        => $tahun,
                'amount'      => $amount,
                'status'      => $status,
            ];

            if (!empty($invoiceNumber)) {
                $data['invoice_number'] = $invoiceNumber;
            }

            if ($status === 'paid') {
                $data['payment_method'] = $metodeBayar ?: 'Cash';
                $data['paid_at'] = $paidAt ?? Carbon::now();
            }

            Invoice::create($data);
            $imported++;
        }

        $message = "Berhasil mengimpor/memperbarui {$imported} tagihan.";
        if ($skipped > 0) $message .= " {$skipped} baris dilewati.";
        if (!empty($errors)) {
            $message .= ' Catatan: ' . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) $message .= ' ... dan ' . (count($errors) - 3) . ' lainnya.';
        }

        return redirect()->route('invoices.index')->with('success', $message);
    }

    private function invoiceImportHeaderMap(array $header): array
    {
        $aliases = [
            'invoice_number' => ['noinvoice', 'nomorinvoice', 'invoice', 'invoiceid', 'noinvoices', 'nomortagihan', 'notagihan'],
            'customer_name' => ['namapelanggan', 'pelanggan', 'nama', 'name', 'customer'],
            'phone' => ['notelepon', 'notelpon', 'telepon', 'telpon', 'phone', 'hp', 'nohp', 'nowa', 'whatsapp'],
            'month' => ['bulan', 'periodebulan', 'month'],
            'year' => ['tahun', 'periodetahun', 'year'],
            'amount' => ['jumlahrp', 'jumlah', 'nominal', 'tagihan', 'amount', 'total'],
            'status' => ['status', 'statustagihan'],
            'payment_method' => ['metodebayar', 'metodepembayaran', 'paymentmethod', 'bank', 'carabayar'],
            'paid_at' => ['tanggalbayar', 'tglbayar', 'tanggalpembayaran', 'paidat', 'waktubayar'],
        ];

        $map = [];
        foreach ($header as $index => $label) {
            $normalized = $this->normalizeInvoiceImportHeader((string) $label);
            foreach ($aliases as $field => $names) {
                if (in_array($normalized, $names, true)) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    private function normalizeInvoiceImportHeader(string $label): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim($label)));
    }

    private function hasInvoiceImportHeader(array $headerMap): bool
    {
        return isset($headerMap['customer_name']) || isset($headerMap['phone']) || isset($headerMap['invoice_number']);
    }

    private function invoiceRowValue(array $row, array $headerMap, array $fallbackColumns, string $field): string
    {
        $index = $headerMap[$field] ?? $fallbackColumns[$field] ?? null;

        if ($index === null) {
            return '';
        }

        return trim((string) ($row[$index] ?? ''));
    }

    private function normalizeInvoicePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '62')) {
            return '0' . substr($digits, 2);
        }

        return $digits;
    }

    private function findCustomerForInvoiceImport(string $phone, string $customerName): ?Customer
    {
        $customer = null;

        if ($phone !== '') {
            $phoneVariants = array_unique([
                $phone,
                str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone,
            ]);

            foreach ($phoneVariants as $variant) {
                $customer = Customer::where('phone', $variant)->first();
                if ($customer) {
                    return $customer;
                }
            }

            foreach ($phoneVariants as $variant) {
                $customer = Customer::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), '-', ''), ' ', ''), '.', '') = ?", [$variant])
                    ->first();
                if ($customer) {
                    return $customer;
                }
            }
        }

        if ($customerName !== '') {
            $customer = Customer::where('name', 'like', "%{$customerName}%")->first();
        }

        return $customer;
    }

    private function normalizeInvoiceAmount(string $amount): int
    {
        $digits = preg_replace('/[^0-9]/', '', $amount);

        return $digits === '' ? 0 : (int) $digits;
    }

    private function normalizeInvoiceStatus(string $status): string
    {
        $normalized = $this->normalizeInvoiceImportHeader($status);

        return in_array($normalized, ['lunas', 'paid', 'sudahbayar', 'terbayar'], true)
            ? 'paid'
            : 'unpaid';
    }

    private function normalizeInvoiceMonth(string $month, array $monthMap): int
    {
        $month = trim($month);

        if ($month === '') {
            return 0;
        }

        if (is_numeric($month)) {
            return (int) $month;
        }

        $normalized = strtolower($this->replaceIndonesianMonthNames($month));
        $normalizedKey = strtolower(trim($month));

        $extraMonthMap = [
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,
        ];

        if (isset($monthMap[$normalizedKey])) {
            return $monthMap[$normalizedKey];
        }

        if (isset($extraMonthMap[$normalized])) {
            return $extraMonthMap[$normalized];
        }

        try {
            return Carbon::parse($normalized)->month;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function normalizeInvoicePaidAt(string $paidAt): ?Carbon
    {
        $paidAt = trim($paidAt);

        if ($paidAt === '') {
            return null;
        }

        if (is_numeric($paidAt) && (float) $paidAt > 25000) {
            $days = floor((float) $paidAt);
            $seconds = (int) round((((float) $paidAt) - $days) * 86400);

            return Carbon::create(1899, 12, 30)->addDays((int) $days)->addSeconds($seconds);
        }

        try {
            return Carbon::parse($this->replaceIndonesianMonthNames($paidAt));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function replaceIndonesianMonthNames(string $value): string
    {
        return str_ireplace(
            ['Januari', 'Februari', 'Maret', 'Mei', 'Juni', 'Juli', 'Agustus', 'Oktober', 'Desember'],
            ['January', 'February', 'March', 'May', 'June', 'July', 'August', 'October', 'December'],
            $value
        );
    }
}
