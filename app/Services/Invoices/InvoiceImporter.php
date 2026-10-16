<?php

namespace App\Services\Invoices;

use App\Models\Customer;
use App\Models\Invoice;
use App\Support\SpreadsheetReader;
use Illuminate\Support\Carbon;

/**
 * Import tagihan dari file .xlsx / .xls (hasil export sistem) / .csv / .txt.
 * Format kolom: No. Invoice | Nama Pelanggan | No. Telepon | Paket | Bulan | Tahun | Jumlah | Status | Metode Bayar | Tanggal Bayar
 */
class InvoiceImporter
{
    public const EXTENSIONS = ['csv', 'txt', 'xls', 'xlsx'];

    /**
     * @return array{rows: int, imported: int, skipped: int, errors: array<int, string>}
     */
    public function import(string $path, string $extension): array
    {
        $rows = SpreadsheetReader::read($path, $extension);

        if ($extension === 'xlsx') {
            // Untuk XLSX: nilai sel di-trim dan baris tanpa sel dilewati.
            $rows = array_values(array_filter(array_map(fn ($row) => array_map('trim', $row), $rows), fn ($row) => $row !== []));
        }

        if (empty($rows)) {
            return ['rows' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => []];
        }

        $monthMap = [
            'januari'=>1,'februari'=>2,'maret'=>3,'april'=>4,
            'mei'=>5,'juni'=>6,'juli'=>7,'agustus'=>8,
            'september'=>9,'oktober'=>10,'november'=>11,'desember'=>12,
        ];

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

        return ['rows' => count($rows) + 1, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
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
