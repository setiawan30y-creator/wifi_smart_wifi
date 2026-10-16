<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceGenerateTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(Package $package, array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Pelanggan ' . uniqid(),
            'phone' => '0812' . random_int(10000000, 99999999),
            'address' => 'Alamat uji',
            'package_id' => $package->id,
            'billing_day' => 5,
            'status' => 'active',
            'status_rumah' => 'milik_sendiri',
            'tanggal_pasang' => '2026-01-01',
        ], $attributes));
    }

    public function test_generate_button_creates_invoices_once_for_billable_customers(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $package = Package::create(['name' => 'Paket 20 Mbps', 'price' => 200000, 'speed' => '20 Mbps']);

        $billable = $this->makeCustomer($package);
        $free = $this->makeCustomer($package, ['billing_type' => 'family']);
        $prepaid = $this->makeCustomer($package, ['billing_mode' => 'prepaid']);
        $later = $this->makeCustomer($package, ['tanggal_pasang' => '2026-12-15']);

        $this->post(route('invoices.generate'), ['month' => 11, 'year' => 2026])->assertRedirect()->assertSessionHas('success');
        $this->post(route('invoices.generate'), ['month' => 11, 'year' => 2026])->assertSessionHas('info');

        $this->assertSame(1, Invoice::where('customer_id', $billable->id)->count());
        $this->assertEquals(200000, (float) Invoice::where('customer_id', $billable->id)->value('amount'));
        foreach ([$free, $prepaid, $later] as $customer) {
            $this->assertSame(0, Invoice::where('customer_id', $customer->id)->count());
        }
    }
}
