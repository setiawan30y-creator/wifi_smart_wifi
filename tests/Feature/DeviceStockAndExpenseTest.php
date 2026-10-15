<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DeviceStock;
use App\Models\Expense;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceStockAndExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($this->user);
    }

    /**
     * Test creating a device stock.
     */
    public function test_can_create_device_stock()
    {
        $response = $this->actingAs($this->user)->post(route('device-stocks.store'), [
            'brand' => 'ZTE',
            'name' => 'ZTE F640',
            'type' => 'FO',
            'stock' => 5,
            'unit_price' => 150000,
            'description' => 'Test ONT GPON'
        ]);

        $response->assertRedirect(route('device-stocks.index'));
        $this->assertDatabaseHas('device_stocks', [
            'name' => 'ZTE F640',
            'brand' => 'ZTE',
            'stock' => 5,
        ]);
    }

    /**
     * Test purchasing a device via Expense increments the device stock.
     */
    public function test_purchasing_device_increments_stock()
    {
        $device = DeviceStock::create([
            'brand' => 'Tenda',
            'name' => 'Tenda F3',
            'type' => 'LAN',
            'stock' => 5,
            'unit_price' => 100000,
        ]);

        $response = $this->actingAs($this->user)->post(route('expenses.store'), [
            'title' => 'Beli Router LAN Tenda F3',
            'category' => 'Pembelian Perangkat',
            'amount' => 1200000,
            'date' => now()->toDateString(),
            'device_stock_id' => $device->id,
            'quantity' => 10,
        ]);

        $response->assertRedirect(route('expenses.index'));
        
        // Stock should be 5 + 10 = 15
        $this->assertEquals(15, $device->fresh()->stock);
        // Unit price should be 1200000 / 10 = 120000
        $this->assertEquals(120000, $device->fresh()->unit_price);
    }

    /**
     * Test updating a purchase expense correctly adjusts the stock.
     */
    public function test_updating_purchase_expense_adjusts_stock()
    {
        $device = DeviceStock::create([
            'brand' => 'Tenda',
            'name' => 'Tenda F3',
            'type' => 'LAN',
            'stock' => 5,
            'unit_price' => 100000,
        ]);

        $expense = Expense::create([
            'title' => 'Beli Router LAN Tenda F3',
            'category' => 'Pembelian Perangkat',
            'amount' => 1200000,
            'date' => now()->toDateString(),
            'device_stock_id' => $device->id,
            'quantity' => 10,
        ]);

        // Adjust stock manually to simulate the initial creation action (since we manually created it above without triggers)
        $device->increment('stock', 10); // becomes 15

        $response = $this->actingAs($this->user)->put(route('expenses.update', $expense->id), [
            'title' => 'Beli Router LAN Tenda F3 (Ralat Qty)',
            'category' => 'Pembelian Perangkat',
            'amount' => 600000,
            'date' => now()->toDateString(),
            'device_stock_id' => $device->id,
            'quantity' => 5, // Changed from 10 to 5
        ]);

        $response->assertRedirect(route('expenses.index'));
        
        // Stock should be: 15 (current) - 10 (old qty) + 5 (new qty) = 10
        $this->assertEquals(10, $device->fresh()->stock);
    }

    /**
     * Test creating a customer with a device decrements the device stock.
     */
    public function test_customer_creation_decrements_device_stock()
    {
        $device = DeviceStock::create([
            'brand' => 'ZTE',
            'name' => 'ZTE F640',
            'type' => 'FO',
            'stock' => 10,
            'unit_price' => 150000,
        ]);

        $package = Package::create([
            'name' => 'Paket Test',
            'price' => 100000,
            'speed' => '10 Mbps',
        ]);

        $response = $this->actingAs($this->user)->post(route('customers.store'), [
            'package_id' => $package->id,
            'name' => 'Customer Baru',
            'phone' => '08123456789',
            'address' => 'Alamat Customer',
            'status' => 'active',
            'billing_day' => 5,
            'status_rumah' => 'milik_sendiri',
            'jalur' => 'FO',
            'perangkat' => 'ZTE F640', // Matches device name
        ]);

        $response->assertRedirect(route('customers.index'));
        
        // Stock should be decremented: 10 - 1 = 9
        $this->assertEquals(9, $device->fresh()->stock);
    }

    /**
     * Test updating a customer device updates stock levels.
     */
    public function test_updating_customer_device_updates_stock()
    {
        $device1 = DeviceStock::create([
            'brand' => 'ZTE',
            'name' => 'ZTE F640',
            'type' => 'FO',
            'stock' => 10,
            'unit_price' => 150000,
        ]);

        $device2 = DeviceStock::create([
            'brand' => 'Tenda',
            'name' => 'Tenda N301',
            'type' => 'LAN',
            'stock' => 5,
            'unit_price' => 110000,
        ]);

        $package = Package::create([
            'name' => 'Paket Test',
            'price' => 100000,
            'speed' => '10 Mbps',
        ]);

        $customer = Customer::create([
            'package_id' => $package->id,
            'name' => 'Customer Baru',
            'phone' => '08123456789',
            'address' => 'Alamat Customer',
            'status' => 'active',
            'billing_day' => 5,
            'status_rumah' => 'milik_sendiri',
            'jalur' => 'FO',
            'perangkat' => 'ZTE F640',
        ]);

        // Manually adjust the stock since Eloquent event booted() is checked
        // If booted() did run, stock1 is already 9. Let's make sure:
        $device1Stock = $device1->fresh()->stock; // should be 9
        
        // Now update customer to use device 2
        $response = $this->actingAs($this->user)->put(route('customers.update', $customer->id), [
            'package_id' => $package->id,
            'name' => 'Customer Baru',
            'phone' => '08123456789',
            'address' => 'Alamat Customer',
            'status' => 'active',
            'billing_day' => 5,
            'status_rumah' => 'milik_sendiri',
            'jalur' => 'LAN',
            'perangkat' => 'Tenda N301',
        ]);

        $response->assertRedirect(route('customers.index'));

        // Device 1 stock should return to original: 9 + 1 = 10
        $this->assertEquals($device1Stock + 1, $device1->fresh()->stock);
        // Device 2 stock should decrement: 5 - 1 = 4
        $this->assertEquals(4, $device2->fresh()->stock);
    }

    /**
     * Test the financial reports computations.
     */
    public function test_financial_reports_computes_correctly()
    {
        // 1. Create a paid invoice (Income)
        $package = Package::create([
            'name' => 'Paket Test',
            'price' => 150000,
            'speed' => '10 Mbps',
        ]);

        $customer = Customer::create([
            'package_id' => $package->id,
            'name' => 'Budi',
            'phone' => '081',
            'address' => 'A',
            'status' => 'active',
            'billing_day' => 5,
            'status_rumah' => 'milik_sendiri',
        ]);

        Invoice::create([
            'customer_id' => $customer->id,
            'month' => date('n'),
            'year' => date('Y'),
            'amount' => 150000,
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => 'Transfer',
        ]);

        // 2. Create an expense (Expense)
        Expense::create([
            'title' => 'Beli Kabel LAN',
            'category' => 'Operasional',
            'amount' => 50000,
            'date' => now()->toDateString(),
        ]);

        // 3. Request financial reports page
        $response = $this->actingAs($this->user)->get(route('financial-reports.index'));

        $response->assertStatus(200);
        $response->assertViewHas('totalIncome', 150000);
        $response->assertViewHas('totalExpense', 50000);
        $response->assertViewHas('netProfit', 100000);
    }

    /**
     * Test creating an expense and registering a new device on the fly.
     */
    public function test_can_create_expense_with_new_device_on_the_fly()
    {
        $response = $this->actingAs($this->user)->post(route('expenses.store'), [
            'title' => 'Beli Router Baru Totolink',
            'category' => 'Pembelian Perangkat',
            'amount' => 1000000,
            'date' => now()->toDateString(),
            'device_stock_id' => 'NEW_DEVICE',
            'quantity' => 10,
            'new_device_brand' => 'Totolink',
            'new_device_name' => 'Totolink N200RE',
            'new_device_type' => 'LAN',
        ]);

        $response->assertRedirect(route('expenses.index'));

        // Check if the device was created with correct stock (0 + 10 = 10)
        $this->assertDatabaseHas('device_stocks', [
            'brand' => 'Totolink',
            'name' => 'Totolink N200RE',
            'type' => 'LAN',
            'stock' => 10,
            'unit_price' => 100000, // 1000000 / 10
        ]);
    }

    /**
     * Test creating a device stock with an image.
     */
    public function test_can_create_device_stock_with_image()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('router.jpg');

        $response = $this->actingAs($this->user)->post(route('device-stocks.store'), [
            'brand' => 'ZTE',
            'name' => 'ZTE F640 Image Test',
            'type' => 'FO',
            'stock' => 5,
            'unit_price' => 150000,
            'description' => 'Test ONT GPON with Image',
            'image' => $file,
        ]);

        $response->assertRedirect(route('device-stocks.index'));
        
        $device = DeviceStock::where('name', 'ZTE F640 Image Test')->first();
        $this->assertNotNull($device->image);

        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($device->image);
    }

    /**
     * Test updating a device stock with an image cleans up the old one.
     */
    public function test_updating_device_stock_cleans_up_old_image()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $oldFile = \Illuminate\Http\UploadedFile::fake()->image('old_router.jpg');
        $oldPath = $oldFile->store('devices', 'public');

        $device = DeviceStock::create([
            'brand' => 'ZTE',
            'name' => 'ZTE F640 Update Image Test',
            'type' => 'FO',
            'stock' => 5,
            'unit_price' => 150000,
            'image' => $oldPath,
        ]);

        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($oldPath);

        $newFile = \Illuminate\Http\UploadedFile::fake()->image('new_router.jpg');

        $response = $this->actingAs($this->user)->put(route('device-stocks.update', $device->id), [
            'brand' => 'ZTE',
            'name' => 'ZTE F640 Update Image Test',
            'type' => 'FO',
            'stock' => 5,
            'unit_price' => 150000,
            'image' => $newFile,
        ]);

        $response->assertRedirect(route('device-stocks.index'));

        // Old file should be deleted
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($oldPath);
        
        // New file should be stored
        $newPath = $device->fresh()->image;
        $this->assertNotEquals($oldPath, $newPath);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($newPath);
    }
}
