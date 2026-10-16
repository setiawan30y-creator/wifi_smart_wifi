<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $adminA;
    private Customer $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Perusahaan A', 'status' => 'active']);
        $this->tenantB = Tenant::create(['name' => 'Perusahaan B', 'status' => 'active']);
        $this->adminA = User::factory()->create(['role' => 'superadmin', 'tenant_id' => $this->tenantA->id]);

        $context = app(TenantContext::class);

        $context->run($this->tenantA->id, function () {
            $package = Package::create(['name' => 'Paket A', 'price' => 100000, 'speed' => '10 Mbps']);
            Customer::create($this->customerData('Pelanggan Milik A', '081100000001', $package->id));
            Router::create(['name' => 'Router A', 'ip_address' => '10.0.0.1', 'port' => 8728, 'username' => 'a', 'password' => 'a']);
        });

        $context->run($this->tenantB->id, function () {
            $package = Package::create(['name' => 'Paket B', 'price' => 100000, 'speed' => '10 Mbps']);
            $this->customerB = Customer::create($this->customerData('Pelanggan Milik B', '081100000002', $package->id));
            Router::create(['name' => 'Router B', 'ip_address' => '10.0.0.2', 'port' => 8728, 'username' => 'b', 'password' => 'b']);
        });
    }

    private function customerData(string $name, string $phone, int $packageId): array
    {
        return [
            'name' => $name,
            'phone' => $phone,
            'address' => 'Alamat uji',
            'package_id' => $packageId,
            'billing_day' => 5,
            'status' => 'active',
            'status_rumah' => 'milik_sendiri',
        ];
    }

    public function test_records_get_the_tenant_of_the_context(): void
    {
        $this->assertSame($this->tenantB->id, (int) $this->customerB->tenant_id);
        $this->assertSame(2, Router::withoutGlobalScopes()->whereNotNull('tenant_id')->count());
    }

    public function test_customer_list_only_shows_own_tenant(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Pelanggan Milik A')
            ->assertDontSee('Pelanggan Milik B');
    }

    public function test_cannot_modify_customer_of_another_tenant(): void
    {
        $this->actingAs($this->adminA)
            ->delete(route('customers.destroy', $this->customerB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('customers', ['id' => $this->customerB->id]);
    }

    public function test_router_list_only_shows_own_tenant(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('routers.index'))
            ->assertOk()
            ->assertSee('Router A')
            ->assertDontSee('Router B');
    }

    public function test_active_router_is_resolved_per_tenant(): void
    {
        Router::withoutGlobalScopes()->update(['status' => 'online']);

        $router = app(TenantContext::class)->run($this->tenantA->id, fn () => Router::active());

        $this->assertSame('Router A', $router?->name);
    }

    public function test_new_customer_is_saved_to_logged_in_tenant(): void
    {
        $package = Package::forTenant($this->tenantA->id)->first();

        $this->actingAs($this->adminA)->post(route('customers.store'), [
            'package_id' => $package->id,
            'name' => 'Pelanggan Baru A',
            'phone' => '081100000099',
            'address' => 'Alamat baru',
            'billing_day' => 10,
            'billing_type' => 'paid',
            'billing_mode' => 'postpaid',
            'status_rumah' => 'milik_sendiri',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['name' => 'Pelanggan Baru A', 'tenant_id' => $this->tenantA->id]);
    }
}
