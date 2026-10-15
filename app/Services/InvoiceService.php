<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Setting;
use App\Http\Controllers\WaGatewayController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class InvoiceService
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Mark multiple invoices as paid and generate a response message.
     */
    public function payBatch(array $invoiceIds, string $paymentMethod, $proofFile = null, ?string $paidAt = null): array
    {
        $proofPath = null;
        if ($proofFile) {
            $proofPath = $this->fileUploadService->upload($proofFile, 'uploads/payment_proofs', 'proof');
        }

        $paymentDate = $paidAt ? Carbon::parse($paidAt) : Carbon::now();

        $invoices = Invoice::with('customer.package')
            ->whereIn('id', $invoiceIds)
            ->where('status', 'unpaid')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        if ($invoices->isEmpty()) {
            return ['status' => 'error', 'message' => 'Tidak ada tagihan yang valid untuk dibayar.'];
        }

        $customerIds = $invoices->pluck('customer_id')->unique()->values();
        if ($customerIds->count() > 1) {
            $phones = $invoices
                ->pluck('customer.phone')
                ->map(fn ($phone) => $this->normalizePhone($phone))
                ->filter()
                ->unique()
                ->values();

            if ($phones->count() !== 1) {
                return ['status' => 'error', 'message' => 'Pembayaran manual beberapa pelanggan hanya bisa diproses jika nomor WhatsApp pelanggan sama.'];
            }
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $paidNumbers = [];
        $totalAmount = 0;
        $customer = $invoices->first()->customer;

        foreach ($invoices as $invoice) {
            $paymentData = [
                'status'         => 'paid',
                'paid_at'        => $paymentDate,
                'payment_method' => $paymentMethod,
            ];

            if (Schema::hasColumn('invoices', 'payment_proof')) {
                $paymentData['payment_proof'] = $proofPath;
            }

            $invoice->update($paymentData);
            $paidNumbers[] = $invoice->invoice_number . ' (' . ($monthNames[$invoice->month] ?? $invoice->month) . ' ' . $invoice->year . ')';
            $totalAmount += $invoice->amount;
        }

        $remainingUnpaidInvoices = Invoice::with('customer')
            ->whereIn('customer_id', $customerIds)
            ->where('status', 'unpaid')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $count = count($paidNumbers);
        $message = "Berhasil membayar {$count} tagihan: " . implode(', ', $paidNumbers) . " — Total Rp " . number_format($totalAmount, 0, ',', '.') . " via {$paymentMethod}.";

        return [
            'status' => 'success',
            'message' => $message,
            'customer' => $customer,
            'invoices' => $invoices,
            'remainingUnpaidInvoices' => $remainingUnpaidInvoices,
            'totalAmount' => $totalAmount,
            'count' => $count,
            'paidAt' => $paymentDate,
        ];
    }

    /**
     * Build WA URL redirect.
     */
    public function buildWaRedirectUrl(Customer $customer, $invoices, int $totalAmount, int $count, string $paymentMethod, $remainingUnpaidInvoices = null, ?string $templateContent = null, ?Carbon $paidAt = null): string
    {
        $phone = preg_replace('/[^0-9]/', '', $customer->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return 'https://api.whatsapp.com/send?phone=' . $phone . '&text=' . urlencode(
            $this->buildPaymentReceiptMessage($customer, $invoices, $totalAmount, $count, $paymentMethod, $remainingUnpaidInvoices, $templateContent, $paidAt)
        );
    }

    public function buildPaymentReceiptMessage(Customer $customer, $invoices, int $totalAmount, int $count, string $paymentMethod, $remainingUnpaidInvoices = null, ?string $templateContent = null, ?Carbon $paidAt = null): string
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $hasMultipleCustomers = collect($invoices)->pluck('customer_id')->unique()->count() > 1;
        $customerNames = collect($invoices)->pluck('customer.name')->filter()->unique()->values()->all();
        $paidLines = [];
        $paidPeriodLines = [];
        $invoiceNumbers = [];
        $periods = [];
        $dueDateLines = [];
        foreach ($invoices as $inv) {
            $period = ($monthNames[$inv->month] ?? $inv->month) . " {$inv->year}";
            $invoiceNumbers[] = $inv->invoice_number;
            $periods[] = $period;
            $billingDay = max(1, min((int) ($inv->customer->billing_day ?: 1), Carbon::create((int) $inv->year, (int) $inv->month, 1)->daysInMonth));
            $dueDateLines[] = Carbon::create((int) $inv->year, (int) $inv->month, $billingDay)->translatedFormat('d F Y');
            $customerPrefix = $hasMultipleCustomers ? ($inv->customer->name ?? 'Pelanggan') . ' - ' : '';
            $paidLines[] = "- {$customerPrefix}{$period} ({$inv->invoice_number}): Rp " . number_format($inv->amount, 0, ',', '.');
            $paidPeriodLines[] = "- {$customerPrefix}{$period}";
        }

        $remainingLines = [];
        $remainingPeriodLines = [];
        if ($remainingUnpaidInvoices && $remainingUnpaidInvoices->isNotEmpty()) {
            foreach ($remainingUnpaidInvoices as $inv) {
                $period = ($monthNames[$inv->month] ?? $inv->month) . " {$inv->year}";
                $customerPrefix = $hasMultipleCustomers ? ($inv->customer->name ?? 'Pelanggan') . ' - ' : '';
                $remainingLines[] = "- {$customerPrefix}{$period} ({$inv->invoice_number}): Rp " . number_format($inv->amount, 0, ',', '.');
                $remainingPeriodLines[] = "- {$customerPrefix}{$period}";
            }
        }

        $paidAt ??= Carbon::now();
        $totalPaidText = 'Rp ' . number_format($totalAmount, 0, ',', '.');
        $totalPaidWords = $this->rupiahWords($totalAmount);
        $remainingTotal = $remainingUnpaidInvoices && $remainingUnpaidInvoices->isNotEmpty()
            ? (int) $remainingUnpaidInvoices->sum('amount')
            : 0;
        $remainingTotalText = 'Rp ' . number_format($remainingTotal, 0, ',', '.');
        $remainingDetailText = $remainingLines ? implode("\n", $remainingLines) : 'Tidak ada sisa tagihan.';
        $remainingPeriodText = $remainingPeriodLines ? implode("\n", $remainingPeriodLines) : 'Tidak ada sisa tagihan.';
        $remainingStatusText = $remainingLines ? 'Masih ada sisa tagihan.' : 'Tidak ada sisa tagihan.';
        $remainingText = $remainingLines
            ? "Masih ada sisa tagihan:\n" . $remainingDetailText . "\nTotal sisa tagihan: " . $remainingTotalText
            : 'Tidak ada sisa tagihan.';
        $remainingSummaryText = $remainingLines
            ? "Periode:\n" . $remainingPeriodText . "\nNominal: " . $remainingTotalText
            : 'Tidak ada sisa tagihan.';
        $recipientName = $hasMultipleCustomers ? implode(', ', $customerNames) : $customer->name;

        if (filled($templateContent)) {
            return strtr((string) $templateContent, [
                '{nama}' => $recipientName,
                '{nama_pelanggan}' => $recipientName,
                '{no_invoice}' => implode(', ', $invoiceNumbers),
                '{nomor_tagihan}' => implode(', ', $invoiceNumbers),
                '{bulan}' => implode(', ', $periods),
                '{periode}' => implode(', ', $periods),
                '{periode_bayar}' => implode(', ', $periods),
                '{periode_bayar_baris}' => implode("\n", $paidPeriodLines),
                '{tanggal_jatuh_tempo}' => implode("\n", $dueDateLines),
                '{jumlah}' => $totalPaidText,
                '{nominal}' => $totalPaidText,
                '{nominal_dibayar}' => $totalPaidText,
                '{nominal_dibayar_miring}' => $totalPaidWords,
                '{nominal_dibayar_terbilang}' => $totalPaidWords,
                '{metode_bayar}' => $paymentMethod,
                '{tanggal_bayar}' => $paidAt->translatedFormat('d F Y'),
                '{tanggal_bayar_lengkap}' => $paidAt->translatedFormat('d F Y H:i'),
                '{jumlah_bulan}' => (string) $count,
                '{rincian_tagihan}' => implode("\n", $paidLines),
                '{sisa_tagihan}' => $remainingText,
                '{rincian_sisa_tagihan}' => $remainingDetailText,
                '{periode_sisa_tagihan}' => $remainingPeriodText,
                '{total_sisa_tagihan}' => $remainingTotalText,
                '{status_sisa_tagihan}' => $remainingStatusText,
                '{info_sisa_tagihan}' => $remainingSummaryText,
                '{odp}' => $customer->odp->name ?? '-',
                '{nama_odp}' => $customer->odp->name ?? '-',
            ]);
        }

        $waText = "Halo {$recipientName},\n\n";
        $waText .= "Pembayaran tagihan Anda telah kami terima.\n\n";
        $waText .= "Rincian tagihan yang dibayar ({$count} bulan):\n";
        $waText .= implode("\n", $paidLines) . "\n";
        $waText .= "\nTotal dibayar: Rp " . number_format($totalAmount, 0, ',', '.') . "\n";
        $waText .= "Metode: {$paymentMethod}\n\n";

        if ($remainingUnpaidInvoices && $remainingUnpaidInvoices->isNotEmpty()) {
            $waText .= "Catatan sisa tagihan belum dibayar:\n";
            $waText .= implode("\n", $remainingLines) . "\n";
            $waText .= "\nTotal sisa tagihan: Rp " . number_format($remainingTotal, 0, ',', '.') . "\n\n";
        } else {
            $waText .= "Tidak ada sisa tagihan yang belum dibayar.\n\n";
        }
        $waText .= "Terima kasih.";

        return $waText;
    }

    private function rupiahWords(int $amount): string
    {
        return '(' . $this->numberToIndonesianWords($amount) . ' Rupiah)';
    }

    private function numberToIndonesianWords(int $number): string
    {
        $number = abs($number);

        if ($number === 0) {
            return 'nol';
        }

        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($number < 12) {
            return $words[$number];
        }

        if ($number < 20) {
            return $this->numberToIndonesianWords($number - 10) . ' belas';
        }

        if ($number < 100) {
            $remainder = $number % 10;

            return trim($this->numberToIndonesianWords(intdiv($number, 10)) . ' puluh' . ($remainder ? ' ' . $this->numberToIndonesianWords($remainder) : ''));
        }

        if ($number < 200) {
            $remainder = $number - 100;

            return trim('seratus' . ($remainder ? ' ' . $this->numberToIndonesianWords($remainder) : ''));
        }

        if ($number < 1000) {
            $remainder = $number % 100;

            return trim($this->numberToIndonesianWords(intdiv($number, 100)) . ' ratus' . ($remainder ? ' ' . $this->numberToIndonesianWords($remainder) : ''));
        }

        if ($number < 2000) {
            $remainder = $number - 1000;

            return trim('seribu' . ($remainder ? ' ' . $this->numberToIndonesianWords($remainder) : ''));
        }

        if ($number < 1000000) {
            $remainder = $number % 1000;

            return trim($this->numberToIndonesianWords(intdiv($number, 1000)) . ' ribu' . ($remainder ? ' ' . $this->numberToIndonesianWords($remainder) : ''));
        }

        if ($number < 1000000000) {
            $remainder = $number % 1000000;

            return trim($this->numberToIndonesianWords(intdiv($number, 1000000)) . ' juta' . ($remainder ? ' ' . $this->numberToIndonesianWords($remainder) : ''));
        }

        if ($number < 1000000000000) {
            $remainder = $number % 1000000000;

            return trim($this->numberToIndonesianWords(intdiv($number, 1000000000)) . ' miliar' . ($remainder ? ' ' . $this->numberToIndonesianWords($remainder) : ''));
        }

        $remainder = $number % 1000000000000;

        return trim($this->numberToIndonesianWords(intdiv($number, 1000000000000)) . ' triliun' . ($remainder ? ' ' . $this->numberToIndonesianWords($remainder) : ''));
    }

    /**
     * Generate monthly invoices for active customers.
     */
    public function generateMonthlyInvoices(int $month, int $year, ?string $waTemplateTitle = null, bool $sendWa = false, ?string $waTemplateContent = null): array
    {
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();

        $activeCustomers = Customer::whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->where(function ($query) {
                $query->whereNull('billing_type')
                    ->orWhere('billing_type', Customer::BILLING_PAID);
            })
            ->where(function ($query) {
                $query->whereNull('billing_mode')
                    ->orWhere('billing_mode', Customer::MODE_POSTPAID);
            })
            ->with('package')
            ->get();
        $inactiveCount = Customer::whereRaw('LOWER(TRIM(status)) <> ?', ['active'])->count();
        $freeCount = Customer::whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->whereNotNull('billing_type')
            ->where('billing_type', '<>', Customer::BILLING_PAID)
            ->count();
        $createdCount = 0;
        $skippedCount = 0;
        $withoutPackageCount = 0;
        $futureCustomerCount = 0;
        $createdInvoices = collect();

        foreach ($activeCustomers as $customer) {
            $billingStartDate = $customer->tanggal_pasang
                ? Carbon::parse($customer->tanggal_pasang)
                : ($customer->created_at ? $customer->created_at->copy() : null);

            if ($billingStartDate && $billingStartDate->startOfDay()->greaterThan($periodEnd)) {
                $futureCustomerCount++;
                continue;
            }

            if (!$customer->package) {
                $withoutPackageCount++;
                continue;
            }

            $exists = Invoice::where('customer_id', $customer->id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists();

            if (!$exists) {
                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'month'       => $month,
                    'year'        => $year,
                    'amount'      => $customer->package->price,
                    'status'      => 'unpaid',
                ]);
                $invoice->setRelation('customer', $customer);
                $createdInvoices->push($invoice);
                $createdCount++;
            } else {
                $skippedCount++;
            }
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $monthName = $monthNames[$month] ?? $month;

        if ($createdCount > 0) {
            $message = "Berhasil membuat {$createdCount} tagihan baru untuk periode {$monthName} {$year}.";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} tagihan dilewati karena sudah ada)";
            }
            if ($inactiveCount > 0) {
                $message .= " {$inactiveCount} pelanggan nonaktif/isolir/berhenti tidak dibuatkan tagihan.";
            }
            if ($freeCount > 0) {
                $message .= " {$freeCount} pelanggan gratis/admin/sosial tidak dibuatkan tagihan.";
            }
            if ($withoutPackageCount > 0) {
                $message .= " {$withoutPackageCount} pelanggan aktif dilewati karena belum punya paket.";
            }
            if ($futureCustomerCount > 0) {
                $message .= " {$futureCustomerCount} pelanggan baru dilewati karena tanggal pasang/daftarnya setelah periode {$monthName} {$year}.";
            }
            if ($waTemplateTitle) {
                $message .= " Template WA bawaan: {$waTemplateTitle}.";
            }
            if ($sendWa) {
                $waResult = $this->sendGeneratedInvoiceNotifications($createdInvoices, $waTemplateContent);
                $message .= " WA Gateway: {$waResult['sent']} terkirim, {$waResult['failed']} gagal, {$waResult['skipped']} dilewati.";
            }
            return ['status' => 'success', 'message' => $message];
        } else {
            $message = "Tidak ada tagihan baru untuk periode {$monthName} {$year}.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} pelanggan aktif sudah memiliki tagihan.";
            }
            if ($inactiveCount > 0) {
                $message .= " {$inactiveCount} pelanggan nonaktif/isolir/berhenti tidak dibuatkan tagihan.";
            }
            if ($freeCount > 0) {
                $message .= " {$freeCount} pelanggan gratis/admin/sosial tidak dibuatkan tagihan.";
            }
            if ($withoutPackageCount > 0) {
                $message .= " {$withoutPackageCount} pelanggan aktif dilewati karena belum punya paket.";
            }
            if ($futureCustomerCount > 0) {
                $message .= " {$futureCustomerCount} pelanggan baru dilewati karena tanggal pasang/daftarnya setelah periode {$monthName} {$year}.";
            }
            if ($waTemplateTitle) {
                $message .= " Template WA bawaan: {$waTemplateTitle}.";
            }

            return ['status' => 'info', 'message' => $message];
        }
    }

    private function sendGeneratedInvoiceNotifications($invoices, ?string $templateContent): array
    {
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $gateway = app(WaGatewayController::class);
        $delayMin = max(0, (int) Setting::get('billing_auto_generate_wa_delay_min', 0));
        $delayMax = max($delayMin, (int) Setting::get('billing_auto_generate_wa_delay_max', 0));

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;
            if (!$customer || !$customer->phone) {
                $skipped++;
                continue;
            }

            $periodDate = Carbon::create((int) $invoice->year, (int) $invoice->month, 1);
            $billingDay = max(1, min((int) ($customer->billing_day ?: 1), $periodDate->daysInMonth));
            $dueDate = $periodDate->copy()->day($billingDay);
            $message = $this->renderGeneratedInvoiceMessage($templateContent, $customer, $invoice, $dueDate);
            $result = $gateway->sendDirect($customer->phone, $message);

            ($result['success'] ?? false) ? $sent++ : $failed++;

            if ($delayMax > 0 && $invoice->id !== $invoices->last()->id) {
                sleep(random_int($delayMin, $delayMax));
            }
        }

        return compact('sent', 'failed', 'skipped');
    }

    private function renderGeneratedInvoiceMessage(?string $templateContent, Customer $customer, Invoice $invoice, Carbon $dueDate): string
    {
        $monthName = $dueDate->translatedFormat('F');
        $message = filled($templateContent) ? $templateContent : "Halo {nama_pelanggan},\n\nTagihan internet periode {periode} telah terbit.\nNomor tagihan: {nomor_tagihan}\nJumlah: {nominal}\nJatuh tempo: {tanggal_jatuh_tempo}\n\nTerima kasih.";

        return strtr($message, [
            '{nama}' => $customer->name,
            '{nama_pelanggan}' => $customer->name,
            '{nomor_tagihan}' => $invoice->invoice_number,
            '{no_invoice}' => $invoice->invoice_number,
            '{bulan}' => $monthName,
            '{tahun}' => (string) $invoice->year,
            '{periode}' => $monthName . ' ' . $invoice->year,
            '{nominal}' => 'Rp ' . number_format((int) $invoice->amount, 0, ',', '.'),
            '{jumlah}' => 'Rp ' . number_format((int) $invoice->amount, 0, ',', '.'),
            '{tanggal_jatuh_tempo}' => $dueDate->translatedFormat('d F Y'),
            '{nama_odp}' => $customer->odp->name ?? '-',
            '{odp}' => $customer->odp->name ?? '-',
        ]);
    }

    private function normalizePhone(?string $phone): ?string
    {
        $number = preg_replace('/[^0-9]/', '', (string) $phone);

        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '0')) {
            return '62' . substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return '62' . $number;
        }

        return $number;
    }
}
