<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, ?int $tenantId = null): User
    {
        return User::factory()->create(['role' => $role, 'tenant_id' => $tenantId ?? 1]);
    }

    public function test_technician_cannot_open_router_or_settings(): void
    {
        $tehnisi = $this->userWithRole('tehnisi');

        $this->actingAs($tehnisi)->get(route('routers.index'))->assertForbidden();
        $this->actingAs($tehnisi)->get(route('settings.index'))->assertForbidden();
    }

    public function test_client_cannot_open_staff_modules(): void
    {
        $client = $this->userWithRole('client');

        $this->actingAs($client)->get(route('customers.index'))->assertForbidden();
        $this->actingAs($client)->get(route('routers.index'))->assertForbidden();
        $this->actingAs($client)->get(route('pppoe.index'))->assertForbidden();
        $this->actingAs($client)->post(route('wa.broadcast'), [])->assertForbidden();
        $this->actingAs($client)->post(route('wa.send'), [])->assertForbidden();
        $this->actingAs($client)->delete(route('pppoe.reset-table'), ['confirmation' => 'HAPUS'])->assertForbidden();
    }

    public function test_superadmin_can_open_router_page(): void
    {
        $this->actingAs($this->userWithRole('superadmin'))->get(route('routers.index'))->assertOk();
    }

    public function test_client_can_never_get_staff_module_even_if_stored(): void
    {
        $matrix = Permissions::normalize(['client' => ['customers' => true]]);

        $this->assertFalse($matrix['client']['customers']);
    }

    public function test_technician_can_never_get_settings_even_if_stored(): void
    {
        $matrix = Permissions::normalize(['tehnisi' => ['settings' => true]]);

        $this->assertFalse($matrix['tehnisi']['settings']);
    }

    public function test_permission_settings_are_per_tenant(): void
    {
        $tenantB = Tenant::create(['name' => 'Perusahaan B', 'status' => 'active']);
        $tehnisiA = $this->userWithRole('tehnisi', 1);
        $tehnisiB = $this->userWithRole('tehnisi', $tenantB->id);

        // Tenant A mematikan modul Pelanggan untuk teknisi.
        app(TenantContext::class)->run(1, fn () => Setting::set('permissions_map', ['tehnisi' => ['customers' => false]]));

        $this->actingAs($tehnisiA)->get(route('customers.index'))->assertForbidden();
        $this->actingAs($tehnisiB)->get(route('customers.index'))->assertOk();
    }

    public function test_saving_permissions_from_settings_page(): void
    {
        $admin = $this->userWithRole('superadmin');

        $this->actingAs($admin)->post(route('settings.permissions'), [
            'permissions' => [
                'tehnisi' => ['dashboard' => 1, 'routers' => 1, 'settings' => 1],
            ],
        ])->assertRedirect();

        $matrix = app(TenantContext::class)->run(1, fn () => Permissions::matrix());

        $this->assertTrue($matrix['tehnisi']['routers']);
        $this->assertFalse($matrix['tehnisi']['customers']);
        $this->assertFalse($matrix['tehnisi']['settings']);
    }
}
