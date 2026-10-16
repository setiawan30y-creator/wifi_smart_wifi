<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Billing\BillingMessages;
use Tests\TestCase;

class BillingMessagesTest extends TestCase
{
    public function test_arrears_message_lists_all_unpaid_periods(): void
    {
        $customer = new Customer(['name' => 'Pelanggan Uji', 'billing_day' => 10]);
        $invoices = collect([
            new Invoice(['invoice_number' => 'INV-202608-0001', 'month' => 8, 'year' => 2026, 'amount' => 150000]),
            new Invoice(['invoice_number' => 'INV-202609-0001', 'month' => 9, 'year' => 2026, 'amount' => 150000]),
        ]);

        $message = BillingMessages::arrears('{nama} {jumlah_tunggakan} {periode} {nominal}', $customer, $invoices);

        $this->assertSame('Pelanggan Uji 2 Agustus 2026, September 2026 Rp 300.000', $message);
    }

    public function test_invoice_message_uses_default_when_template_empty(): void
    {
        $customer = new Customer(['name' => 'Pelanggan Uji', 'billing_day' => 5]);
        $invoice = new Invoice(['invoice_number' => 'INV-202610-0007', 'month' => 10, 'year' => 2026, 'amount' => 99000]);

        $message = BillingMessages::invoice('', $customer, $invoice, \Illuminate\Support\Carbon::create(2026, 10, 5));

        $this->assertStringContainsString('INV-202610-0007', $message);
        $this->assertStringContainsString('Rp 99.000', $message);
    }
}
