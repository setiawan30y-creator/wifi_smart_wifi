<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge(Theme::defaults(), ['theme_mode' => 'system'], $overrides);
    }

    public function test_default_theme_prints_no_override(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'tenant_id' => 1]))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="tenant-theme"', false);
    }

    public function test_admin_can_change_colors_and_layout_uses_them(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);

        $this->actingAs($admin)
            ->post(route('settings.theme'), $this->payload(['theme_brand' => '14B8A6', 'theme_accent' => '#0ea5e9', 'theme_mode' => 'light']))
            ->assertRedirect(route('settings.index', ['tab' => 'appearance']))
            ->assertSessionHasNoErrors();

        $this->assertSame('#14b8a6', Setting::forTenant(1)->where('key', 'theme_brand')->value('value'));

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertSee(':root{--brand:#14b8a6;--accent:#0ea5e9}', false)
            ->assertSee('<meta name="theme-color" content="#14b8a6">', false)
            ->assertSee('const tenantDefault = "light"', false);
    }

    public function test_theme_is_per_tenant(): void
    {
        $tenantB = Tenant::create(['name' => 'Perusahaan B', 'status' => 'active']);
        app(TenantContext::class)->run(1, fn () => Setting::set('theme_brand', '#f97316'));

        $userB = User::factory()->create(['role' => 'admin', 'tenant_id' => $tenantB->id]);

        $this->actingAs($userB)->get(route('dashboard'))->assertDontSee('#f97316', false);
    }

    public function test_invalid_color_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'tenant_id' => 1]))
            ->post(route('settings.theme'), $this->payload(['theme_brand' => 'red;}body{display:none']))
            ->assertSessionHasErrors('theme_brand');
    }

    public function test_reset_restores_defaults(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        app(TenantContext::class)->run(1, fn () => Setting::set('theme_brand', '#f97316'));

        $this->actingAs($admin)->post(route('settings.theme.reset'))->assertRedirect();

        $this->assertNull(Setting::forTenant(1)->where('key', 'theme_brand')->value('value'));
    }

    public function test_technician_cannot_change_theme(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'tehnisi', 'tenant_id' => 1]))
            ->post(route('settings.theme'), $this->payload())
            ->assertForbidden();
    }
}
