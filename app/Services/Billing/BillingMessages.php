<?php

namespace App\Services\Billing;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

/**
 * Isi pesan WA terkait tagihan dan placeholder template, di satu tempat.
 *
 * Placeholder: {nama} {nama_pelanggan} {no_invoice} {nomor_tagihan} {bulan} {tahun} {periode}
 * {jumlah} {nominal} {tanggal_jatuh_tempo} {odp} {nama_odp}
 * Janji bayar: {tanggal_janji_bayar} {catatan_janji_bayar}
 * Pesan gabungan: {rincian_tagihan} {jumlah_pelanggan}
 * Pengingat: {sisa_hari} (sebelum jatuh tempo), {hari_terlambat} (sesudah jatuh tempo)
 */
final class BillingMessages
{
    public const DEFAULT_GENERATED = "Halo {nama_pelanggan},\n\nTagihan internet periode {periode} telah terbit.\nNo. Invoice: {nomor_tagihan}\nJumlah: {nominal}\nJatuh tempo: {tanggal_jatuh_tempo}\n\nTerima kasih.";

    public const DEFAULT_OVERDUE = "Halo {nama_pelanggan},\n\nKami informasikan tagihan internet periode {periode} masih belum lunas.\nNo. Invoice: {nomor_tagihan}\nJumlah: {nominal}\nJatuh tempo: {tanggal_jatuh_tempo}\n\nMohon segera melakukan pembayaran. Terima kasih.";

    public const DEFAULT_BEFORE_DUE = "Halo {nama_pelanggan},\n\nMengingatkan tagihan internet periode {periode} akan jatuh tempo {sisa_hari}.\nNo. Invoice: {nomor_tagihan}\nJumlah: {nominal}\nJatuh tempo: {tanggal_jatuh_tempo}\n\nAbaikan pesan ini jika sudah membayar. Terima kasih.";

    public const DEFAULT_PAYMENT_PROMISE = "Halo {nama_pelanggan},\n\nKami ingatkan kembali janji pembayaran internet Anda pada tanggal {tanggal_janji_bayar}.\n\nNo. Invoice: {nomor_tagihan}\nPeriode: {periode}\nNominal: {nominal}\nCatatan: {catatan_janji_bayar}\n\nMohon melakukan pembayaran sesuai janji. Jika sudah membayar, abaikan pesan ini. Terima kasih.";

    public static function invoice(?string $template, Customer $customer, Invoice $invoice, Carbon $dueDate, string $fallback = self::DEFAULT_GENERATED, array $extra = []): string
    {
        $message = trim((string) $template) !== '' ? (string) $template : $fallback;

        return strtr($message, $extra + self::invoicePlaceholders($customer, $invoice, $dueDate));
    }

    /**
     * Pengingat sebelum jatuh tempo. $daysUntilDue 0 = hari ini.
     */
    public static function beforeDue(?string $template, Customer $customer, Invoice $invoice, Carbon $dueDate, int $daysUntilDue): string
    {
        return self::invoice($template, $customer, $invoice, $dueDate, self::DEFAULT_BEFORE_DUE, [
            '{sisa_hari}' => self::daysLeftText($daysUntilDue),
            '{hari_terlambat}' => '0',
        ]);
    }

    /**
     * Pengingat sesudah jatuh tempo untuk satu tagihan.
     */
    public static function overdue(?string $template, Customer $customer, Invoice $invoice, Carbon $dueDate, int $daysOverdue): string
    {
        return self::invoice($template, $customer, $invoice, $dueDate, self::DEFAULT_OVERDUE, [
            '{sisa_hari}' => '-',
            '{hari_terlambat}' => (string) $daysOverdue,
        ]);
    }

    public static function daysLeftText(int $days): string
    {
        return match (true) {
            $days <= 0 => 'hari ini',
            $days === 1 => 'besok',
            default => "dalam {$days} hari",
        };
    }

    public static function paymentPromise(?string $template, Customer $customer, Invoice $invoice, Carbon $dueDate): string
    {
        $message = trim((string) $template) !== '' ? (string) $template : self::DEFAULT_PAYMENT_PROMISE;
        $promiseDate = Carbon::parse($invoice->payment_promise_date);

        return strtr($message, self::invoicePlaceholders($customer, $invoice, $dueDate) + [
            '{tanggal_janji_bayar}' => $promiseDate->translatedFormat('d F Y'),
            '{catatan_janji_bayar}' => $invoice->payment_promise_note ?: '-',
        ]);
    }

    /**
     * Satu pesan untuk beberapa tagihan yang nomor WA-nya sama.
     *
     * @param  array<int, array{customer: Customer, invoice: Invoice, dueDate: Carbon, template: ?string}>  $items
     */
    public static function grouped(array $items): string
    {
        $template = trim((string) ($items[0]['template'] ?? ''));
        $names = collect($items)->map(fn ($item) => $item['customer']->name)->filter()->unique()->values()->all();
        $invoiceNumbers = collect($items)->map(fn ($item) => $item['invoice']->invoice_number)->filter()->values()->all();
        $periods = collect($items)->map(fn ($item) => $item['dueDate']->translatedFormat('F Y'))->unique()->values()->all();
        $dueDates = collect($items)->map(fn ($item) => $item['dueDate']->translatedFormat('d F Y'))->unique()->values()->all();
        $total = collect($items)->sum(fn ($item) => (int) $item['invoice']->amount);

        $details = collect($items)->map(function ($item) {
            return "- {$item['customer']->name} | {$item['dueDate']->translatedFormat('F Y')} ({$item['invoice']->invoice_number}) | "
                . self::rupiah((int) $item['invoice']->amount)
                . " | JT {$item['dueDate']->translatedFormat('d F Y')}";
        })->implode("\n");

        if ($template !== '') {
            return strtr($template, [
                '{nama}' => implode(', ', $names),
                '{nama_pelanggan}' => implode(', ', $names),
                '{no_invoice}' => implode(', ', $invoiceNumbers),
                '{nomor_tagihan}' => implode(', ', $invoiceNumbers),
                '{bulan}' => implode(', ', $periods),
                '{periode}' => implode(', ', $periods),
                '{jumlah}' => self::rupiah($total),
                '{nominal}' => self::rupiah($total),
                '{tanggal_jatuh_tempo}' => implode(', ', $dueDates),
                '{rincian_tagihan}' => $details,
                '{jumlah_pelanggan}' => (string) count($items),
            ]);
        }

        return "Halo,\n\n"
            . "Tagihan internet untuk " . count($items) . " pelanggan pada nomor WhatsApp ini telah terbit.\n\n"
            . "Rincian tagihan:\n{$details}\n\n"
            . "Total tagihan: " . self::rupiah($total) . "\n\n"
            . "Mohon melakukan pembayaran sesuai rincian di atas. Terima kasih.";
    }

    public const DEFAULT_ARREARS = "Halo {nama_pelanggan},\n\nKami informasikan masih ada {jumlah_tunggakan} periode tagihan yang belum lunas.\nPeriode: {periode}\nNomor tagihan: {nomor_tagihan}\nTotal tunggakan: {nominal}\nJatuh tempo tertua: {tanggal_jatuh_tempo}\n\nMohon segera melakukan pembayaran. Terima kasih.";

    /**
     * Pesan tunggakan untuk satu pelanggan dengan beberapa tagihan belum lunas
     * (urut dari yang tertua). Placeholder tambahan: {jumlah_tunggakan}.
     */
    public static function arrears(?string $template, Customer $customer, \Illuminate\Support\Collection $unpaidInvoices, int $daysOverdue = 0): string
    {
        $message = trim((string) $template) !== '' ? (string) $template : self::DEFAULT_ARREARS;
        $oldest = $unpaidInvoices->first();
        $numbers = $unpaidInvoices->pluck('invoice_number')->implode(', ');
        $periods = $unpaidInvoices
            ->map(fn ($invoice) => self::monthName((int) $invoice->month) . ' ' . $invoice->year)
            ->implode(', ');
        $total = self::rupiah((int) $unpaidInvoices->sum('amount'));
        $odpName = $customer->odp?->name ?? '-';

        return strtr($message, [
            '{nama}' => $customer->name,
            '{nama_pelanggan}' => $customer->name,
            '{nomor_tagihan}' => $numbers,
            '{no_invoice}' => $numbers,
            '{periode}' => $periods,
            '{bulan}' => $periods,
            '{nominal}' => $total,
            '{jumlah}' => $total,
            '{jumlah_tunggakan}' => (string) $unpaidInvoices->count(),
            '{hari_terlambat}' => (string) $daysOverdue,
            '{sisa_hari}' => '-',
            '{tahun}' => (string) ($oldest->year ?? ''),
            '{tanggal_jatuh_tempo}' => $oldest ? DueDate::for($customer, (int) $oldest->month, (int) $oldest->year)->translatedFormat('d F Y') : '-',
            '{nama_odp}' => $odpName,
            '{odp}' => $odpName,
        ]);
    }

    public static function monthName(int $month): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$month] ?? (string) $month;
    }

    public static function rupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private static function invoicePlaceholders(Customer $customer, Invoice $invoice, Carbon $dueDate): array
    {
        $monthName = $dueDate->translatedFormat('F');
        $odpName = $customer->odp->name ?? '-';

        return [
            '{nama}' => $customer->name,
            '{nama_pelanggan}' => $customer->name,
            '{no_invoice}' => $invoice->invoice_number,
            '{nomor_tagihan}' => $invoice->invoice_number,
            '{bulan}' => $monthName,
            '{tahun}' => (string) $invoice->year,
            '{periode}' => $monthName . ' ' . $invoice->year,
            '{jumlah}' => self::rupiah((int) $invoice->amount),
            '{nominal}' => self::rupiah((int) $invoice->amount),
            '{tanggal_jatuh_tempo}' => $dueDate->translatedFormat('d F Y'),
            '{odp}' => $odpName,
            '{nama_odp}' => $odpName,
        ];
    }
}
