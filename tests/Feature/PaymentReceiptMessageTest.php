<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Billing\BillingMessages;
use App\Services\InvoiceService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentReceiptMessageTest extends TestCase
{
    public const TEMPLATE = "*✅ PEMBAYARAN DITERIMA*\n\nHalo Kak *{nama_pelanggan}*,\nPembayaran internet Anda sudah kami terima.\n\n📅 Periode : *{periode_ringkas}*\n🧾 No. Tagihan : {nomor_tagihan_ringkas}\n💰 Jumlah : *{nominal_dibayar}*\n_{nominal_dibayar_terbilang}_\n🗓️ Tanggal bayar : {tanggal_bayar}\n💳 Metode : {metode_bayar}\n\n📌 *Status tagihan*\n{sisa_tagihan_ringkas}";

    private function invoice(int $month, int $year, string $number): Invoice
    {
        return (new Invoice())->forceFill(['month' => $month, 'year' => $year, 'invoice_number' => $number, 'amount' => 150000, 'customer_id' => 1]);
    }

    public function test_period_range_compacts_consecutive_months(): void
    {
        $inv = fn (array $pairs) => collect($pairs)->map(fn ($p) => $this->invoice($p[0], $p[1], 'X'));

        $this->assertSame('Oktober 2026', BillingMessages::periodRange($inv([[10, 2026]])));
        $this->assertSame('Januari – Oktober 2026', BillingMessages::periodRange($inv(array_map(fn ($m) => [$m, 2026], range(1, 10)))));
        $this->assertSame('November 2025 – Februari 2026', BillingMessages::periodRange($inv([[11, 2025], [12, 2025], [1, 2026], [2, 2026]])));
        $this->assertSame('Januari – Februari 2026, Mei 2026', BillingMessages::periodRange($inv([[1, 2026], [2, 2026], [5, 2026]])));
    }

    public function test_receipt_for_many_months_is_compact_and_indonesian(): void
    {
        $customer = (new Customer())->forceFill(['id' => 1, 'name' => 'Nama Pelanggan', 'phone' => '081200000000', 'billing_day' => 5]);
        $invoices = collect(range(1, 10))->map(function ($m) use ($customer) {
            return $this->invoice($m, 2026, sprintf('INV-2026%02d-0074', $m))->setRelation('customer', $customer);
        });

        $message = app(InvoiceService::class)->buildPaymentReceiptMessage(
            $customer, $invoices, 1500000, 10, 'Transfer (Mandiri)', collect(), self::TEMPLATE, Carbon::create(2026, 10, 28, 10, 0)
        );

        $this->assertStringContainsString('Periode : *Januari – Oktober 2026 (10 bulan)*', $message);
        $this->assertStringContainsString('No. Tagihan : INV-202601-0074 s/d INV-202610-0074 (10 tagihan)', $message);
        $this->assertStringContainsString('_(satu juta lima ratus ribu Rupiah)_', $message);
        $this->assertStringContainsString('Tanggal bayar : 28 Oktober 2026', $message);
        $this->assertStringContainsString('✅ Semua tagihan sudah lunas.', $message);
    }
}
