<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Package;
use App\Models\User;
use App\Models\WaTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate superadmin user
        $this->user = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($this->user);

        // Create a package and customer
        $this->package = Package::create([
            'name' => 'Paket Basic 10Mbps',
            'price' => 150000,
            'speed' => '10 Mbps',
            'description' => 'Paket standar rumah tangga',
        ]);

        $this->customer = Customer::create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi.santoso@gmail.com',
            'package_id' => $this->package->id,
            'address' => 'Perum Indah Lestari Blok A/12',
            'billing_day' => 10,
            'status_rumah' => 'milik_sendiri',
            'status' => 'active',
        ]);

        // Create WA templates
        WaTemplate::create([
            'name' => 'kirim_tagihan',
            'title' => 'Kirim Tagihan',
            'content' => 'Halo {nama_pelanggan}, tagihan bulan {bulan} sebesar Rp {nominal} telah terbit.',
            'is_system' => true,
        ]);
    }

    public function test_can_view_invoice_list_with_wa_templates()
    {
        // Create an unpaid invoice
        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'month' => 6,
            'year' => 2026,
            'amount' => 150000,
            'status' => 'unpaid',
        ]);

        $response = $this->get(route('invoices.index', ['month' => 6, 'year' => 2026]));

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
        $response->assertSee('INV-202606-');
        $response->assertSee('Kirim WhatsApp');
        $response->assertViewHas('waTemplates');
    }

    public function test_can_pay_invoices_batch()
    {
        // Create an unpaid invoice
        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'month' => 6,
            'year' => 2026,
            'amount' => 150000,
            'status' => 'unpaid',
        ]);

        $response = $this->post(route('invoices.pay-batch'), [
            'invoice_ids' => [$invoice->id],
            'payment_method' => 'Transfer (BCA)',
            'send_wa' => 0,
        ]);

        $response->assertRedirect();
        
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals('Transfer (BCA)', $invoice->fresh()->payment_method);
    }
}
