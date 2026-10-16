<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * File hasil Export harus bisa di-Import kembali (format .xls XML dari sistem ini).
 */
class ImportExportRoundTripTest extends TestCase
{
    use RefreshDatabase;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $this->package = Package::create(['name' => 'Paket 10 Mbps', 'price' => 150000, 'speed' => '10 Mbps']);
    }

    private function makeCustomer(string $name, string $phone): Customer
    {
        return Customer::create([
            'name' => $name,
            'phone' => $phone,
            'address' => 'Alamat ' . $name,
            'package_id' => $this->package->id,
            'billing_day' => 7,
            'status' => 'active',
            'status_rumah' => 'milik_sendiri',
        ]);
    }

    private function exportedFile(string $route, string $name): UploadedFile
    {
        $content = $this->get(route($route))->assertOk()->getContent();
        $path = tempnam(sys_get_temp_dir(), 'exp');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, 'application/vnd.ms-excel', null, true);
    }

    public function test_customer_export_can_be_imported_back(): void
    {
        $this->makeCustomer('Pelanggan Satu', '081200000001');
        $deleted = $this->makeCustomer('Pelanggan Dua', '081200000002');

        $file = $this->exportedFile('customers.export', 'pelanggan.xls');
        $deleted->delete();

        $this->post(route('customers.import'), ['import_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $restored = Customer::where('name', 'Pelanggan Dua')->first();
        $this->assertNotNull($restored);
        $this->assertSame(7, (int) $restored->billing_day);
        $this->assertSame(2, Customer::count());
    }

    public function test_invoice_export_can_be_imported_back(): void
    {
        $customer = $this->makeCustomer('Pelanggan Tagihan', '081200000003');
        Invoice::create(['customer_id' => $customer->id, 'month' => 9, 'year' => 2026, 'amount' => 150000, 'status' => 'unpaid']);
        $deleted = Invoice::create(['customer_id' => $customer->id, 'month' => 10, 'year' => 2026, 'amount' => 150000, 'status' => 'unpaid']);

        $file = $this->exportedFile('invoices.export', 'tagihan.xls');
        $deleted->delete();

        $this->post(route('invoices.import'), ['import_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, Invoice::where('customer_id', $customer->id)->count());
        $this->assertTrue(Invoice::where('customer_id', $customer->id)->where('month', 10)->exists());
    }

    public function test_import_rejects_unsupported_extension(): void
    {
        $file = UploadedFile::fake()->create('data.pdf', 10, 'application/pdf');

        $this->post(route('customers.import'), ['import_file' => $file])->assertSessionHas('error');
        $this->post(route('invoices.import'), ['import_file' => $file])->assertSessionHas('error');
    }
}
