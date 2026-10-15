<?php

namespace Tests\Feature;

use App\Models\Odp;
use App\Models\DeviceSpecification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdpTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate superadmin user
        $this->user = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($this->user);

        // Seed default specs since tests start with clean DB
        DeviceSpecification::create([
            'device_type' => 'FO',
            'name' => '1/8',
            'label' => 'Splitter 1/8 (8 Port FO)',
        ]);
        DeviceSpecification::create([
            'device_type' => 'FO',
            'name' => '1/16',
            'label' => 'Splitter 1/16 (16 Port FO)',
        ]);
        DeviceSpecification::create([
            'device_type' => 'LAN',
            'name' => '2fo4lan',
            'label' => '2 Port FO & 4 Port LAN',
        ]);
    }

    public function test_can_view_odp_list()
    {
        $odp = Odp::create([
            'name' => 'ODP-TEST-01',
            'fo_ports' => 8,
            'lan_ports' => 0,
            'address' => 'Test Address',
            'description' => 'Test Description',
            'maps_link' => 'https://maps.google.com/?q=-6.123,106.123',
        ]);
        
        $odp->devices()->create([
            'device_type' => 'FO',
            'device_spec' => '1/8',
            'fo_ports' => 8,
            'lan_ports' => 0,
        ]);

        $response = $this->get(route('odps.index'));

        $response->assertStatus(200);
        $response->assertSee('ODP-TEST-01');
        $response->assertSee('FO (1/8)');
        $response->assertSee('8 Port');
    }

    public function test_can_create_odp_with_auto_ports()
    {
        // Test multiple devices creation
        $response1 = $this->post(route('odps.store'), [
            'name' => 'ODP-NEW-01',
            'address' => 'New Test Address 1',
            'devices' => [
                [
                    'device_type' => 'FO',
                    'device_spec' => '1/16',
                ],
                [
                    'device_type' => 'LAN',
                    'device_spec' => '2fo4lan',
                ]
            ]
        ]);

        $response1->assertRedirect(route('odps.index'));
        
        $this->assertDatabaseHas('odps', [
            'name' => 'ODP-NEW-01',
            'fo_ports' => 18, // 16 from 1/16 and 2 from 2fo4lan
            'lan_ports' => 4,  // 4 from 2fo4lan
        ]);

        $odp = Odp::where('name', 'ODP-NEW-01')->first();
        $this->assertCount(2, $odp->devices);
    }

    public function test_can_create_device_specification_via_ajax()
    {
        $response = $this->postJson(route('device-specifications.store'), [
            'device_type' => 'FO',
            'name' => '1/32',
            'label' => 'Splitter 1/32 (32 Port FO)',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('device_specifications', [
            'device_type' => 'FO',
            'name' => '1/32',
            'label' => 'Splitter 1/32 (32 Port FO)',
        ]);

        // Test duplicate error
        $duplicateResponse = $this->postJson(route('device-specifications.store'), [
            'device_type' => 'FO',
            'name' => '1/32',
            'label' => 'Splitter 1/32 Duplicate',
        ]);

        $duplicateResponse->assertStatus(422);
        $duplicateResponse->assertJson([
            'success' => false,
            'message' => 'Spesifikasi tersebut sudah terdaftar.',
        ]);
    }

    public function test_can_update_odp_with_auto_ports()
    {
        $odp = Odp::create([
            'name' => 'ODP-OLD-03',
            'fo_ports' => 8,
            'lan_ports' => 0,
            'address' => 'Old Address',
        ]);

        $odp->devices()->create([
            'device_type' => 'FO',
            'device_spec' => '1/8',
            'fo_ports' => 8,
            'lan_ports' => 0,
        ]);

        $response = $this->put(route('odps.update', $odp->id), [
            'name' => 'ODP-UPDATED-03',
            'address' => 'Updated Address',
            'devices' => [
                [
                    'device_type' => 'LAN',
                    'device_spec' => '2fo4lan',
                ]
            ]
        ]);

        $response->assertRedirect(route('odps.index'));
        
        $this->assertDatabaseHas('odps', [
            'id' => $odp->id,
            'name' => 'ODP-UPDATED-03',
            'fo_ports' => 2,
            'lan_ports' => 4,
        ]);

        $odp->refresh();
        $this->assertCount(1, $odp->devices);
        $this->assertEquals('LAN', $odp->devices->first()->device_type);
        $this->assertEquals('2fo4lan', $odp->devices->first()->device_spec);
    }

    public function test_can_delete_odp()
    {
        $odp = Odp::create([
            'name' => 'ODP-TO-DELETE',
            'fo_ports' => 8,
            'lan_ports' => 0,
            'address' => 'Delete Address',
        ]);

        $odp->devices()->create([
            'device_type' => 'FO',
            'device_spec' => '1/8',
            'fo_ports' => 8,
            'lan_ports' => 0,
        ]);

        $response = $this->delete(route('odps.destroy', $odp->id));

        $response->assertRedirect(route('odps.index'));
        $this->assertDatabaseMissing('odps', [
            'id' => $odp->id,
        ]);
        $this->assertDatabaseMissing('odp_devices', [
            'odp_id' => $odp->id,
        ]);
    }

    public function test_auto_coordinates_extraction()
    {
        $odp = Odp::create([
            'name' => 'ODP-COORDS-01',
            'address' => 'Test address',
            'maps_link' => 'https://maps.google.com/?q=-6.2080,106.8450',
        ]);

        $this->assertEquals(-6.2080, (float) $odp->latitude);
        $this->assertEquals(106.8450, (float) $odp->longitude);

        $package = \App\Models\Package::create([
            'name' => 'Test Package',
            'price' => 100000,
            'speed' => '10 Mbps',
        ]);

        $customer = \App\Models\Customer::create([
            'package_id' => $package->id,
            'name' => 'Test Customer Coords',
            'phone' => '0812345678',
            'address' => 'Test Address Customer',
            'maps_link' => 'https://www.google.com/maps/place/-6.2092,106.8462',
            'status' => 'active',
            'billing_day' => 5,
        ]);

        $this->assertEquals(-6.2092, (float) $customer->latitude);
        $this->assertEquals(106.8462, (float) $customer->longitude);
    }

    public function test_can_create_customer_with_extended_fields()
    {
        $package = \App\Models\Package::create([
            'name' => 'Test Package 2',
            'price' => 150000,
            'speed' => '20 Mbps',
        ]);

        $response = $this->post(route('customers.store'), [
            'package_id' => $package->id,
            'name' => 'Pelanggan Baru',
            'phone' => '08987654321',
            'address' => 'Alamat Lengkap Baru',
            'billing_day' => 10,
            'tanggal_pasang' => '2026-06-06',
            'pppoe_username' => 'pppoe-baru',
            'pppoe_password' => 'passbaru',
            'status_rumah' => 'ngontrak',
            'pemilik_rumah' => 'Pemilik Kontrakan',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Pelanggan Baru',
            'pppoe_username' => 'pppoe-baru',
            'status_rumah' => 'ngontrak',
            'pemilik_rumah' => 'Pemilik Kontrakan',
            'tanggal_pasang' => '2026-06-06',
        ]);
    }

    public function test_can_upload_customer_photos()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $package = \App\Models\Package::create([
            'name' => 'Test Package 3',
            'price' => 200000,
            'speed' => '30 Mbps',
        ]);

        $ktpFile = \Illuminate\Http\UploadedFile::fake()->image('ktp.jpg');
        $rumahFile = \Illuminate\Http\UploadedFile::fake()->image('rumah.jpg');

        $response = $this->post(route('customers.store'), [
            'package_id' => $package->id,
            'name' => 'Pelanggan Foto',
            'phone' => '08111222333',
            'address' => 'Alamat Foto',
            'billing_day' => 15,
            'status_rumah' => 'milik_sendiri',
            'foto_ktp' => $ktpFile,
            'foto_rumah' => $rumahFile,
        ]);

        $response->assertRedirect(route('customers.index'));
        
        $customer = \App\Models\Customer::where('name', 'Pelanggan Foto')->first();
        $this->assertNotNull($customer->foto_ktp);
        $this->assertNotNull($customer->foto_rumah);
        
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($customer->foto_ktp);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($customer->foto_rumah);
    }

    public function test_can_save_customer_medium_and_device()
    {
        $package = \App\Models\Package::create([
            'name' => 'Test Package 4',
            'price' => 200000,
            'speed' => '30 Mbps',
        ]);

        $response = $this->post(route('customers.store'), [
            'package_id' => $package->id,
            'name' => 'Pelanggan Jalur Device',
            'phone' => '08222333444',
            'address' => 'Alamat Jalur Device',
            'billing_day' => 20,
            'status_rumah' => 'milik_sendiri',
            'jalur' => 'FO',
            'perangkat' => 'ZTE F640',
        ]);

        $response->assertRedirect(route('customers.index'));
        
        $this->assertDatabaseHas('customers', [
            'name' => 'Pelanggan Jalur Device',
            'jalur' => 'FO',
            'perangkat' => 'ZTE F640',
        ]);
    }
}
