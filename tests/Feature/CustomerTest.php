<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Odp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $package;
    protected $odp;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate superadmin user
        $this->user = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($this->user);

        // Create a package
        $this->package = Package::create([
            'name' => 'Paket Basic 10Mbps',
            'price' => 150000,
            'speed' => '10 Mbps',
            'description' => 'Paket standar rumah tangga',
        ]);

        // Create an ODP
        $this->odp = Odp::create([
            'name' => 'ODP-BLOK-A01',
            'fo_ports' => 8,
            'lan_ports' => 4,
            'address' => 'Blok A RT 01',
        ]);
    }

    public function test_can_view_customer_list()
    {
        $customer = Customer::create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi.santoso@gmail.com',
            'package_id' => $this->package->id,
            'odp_id' => $this->odp->id,
            'address' => 'Perum Indah Lestari Blok A/12',
            'billing_day' => 10,
            'status_rumah' => 'milik_sendiri',
            'status' => 'active',
        ]);

        $response = $this->get(route('customers.index'));

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
        $response->assertSee('081234567890');
        $response->assertSee('Paket Basic 10Mbps');
        $response->assertSee('ODP-BLOK-A01');
    }

    public function test_can_access_print_customer_page()
    {
        $customer = Customer::create([
            'name' => 'Ahmad Yani',
            'phone' => '081344556677',
            'email' => 'yani.ahmad@gmail.com',
            'package_id' => $this->package->id,
            'odp_id' => $this->odp->id,
            'address' => 'Perum Indah Lestari Blok B/10',
            'billing_day' => 10,
            'status_rumah' => 'milik_sendiri',
            'status' => 'active',
        ]);

        $response = $this->get(route('customers.print', $customer->id));

        $response->assertStatus(200);
        $response->assertSee('Ahmad Yani');
        $response->assertSee('081344556677');
        $response->assertSee('Paket Basic 10Mbps');
        $response->assertSee('ODP-BLOK-A01');
        $response->assertSee('DATA PELANGGAN');
        $response->assertSee('window.print()');
    }
}
