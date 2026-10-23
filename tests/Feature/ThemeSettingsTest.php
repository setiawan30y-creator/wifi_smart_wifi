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
        return array_merge(Theme::defaults(), \App\Support\Theme::OPTION_DEFAULTS, $overrides);
    }

    public function test_default_theme_prints_no_override(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'tenant_id' => 1]))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<style id="tenant-theme"></style>', false)
            ->assertDontSee('data-tone-dark', false);
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

    public function test_surfaces_font_shape_and_contrast(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);

        $this->actingAs($admin)->post(route('settings.theme'), $this->payload([
            'theme_font' => 'poppins',
            'theme_font_size' => 'large',
            'theme_radius' => 'sharp',
            'theme_shadow' => 'none',
            'theme_table_striped' => '1',
            'theme_dark_sidebar' => '#f1f5f9',
            'theme_light_card' => '#fefce8',
            'theme_light_table_head' => '#1e293b',
        ]))->assertSessionHasNoErrors();

        $html = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString("--app-font:'Poppins'", $html);
        $this->assertStringContainsString('--app-font-size:17px', $html);
        $this->assertStringContainsString('--radius-scale:0.4', $html);
        $this->assertStringContainsString('--shadow-card:none', $html);
        $this->assertStringContainsString('--table-stripe:', $html);
        $this->assertStringContainsString('html:not([data-theme="light"]){--sidebar-bg:#f1f5f9}', $html);
        $this->assertStringContainsString('--card-bg:#fefce8;--neutral-900:#fefce8', $html);
        $this->assertStringContainsString('--table-head-text:#cbd5e1', $html, 'Kepala tabel gelap memakai teks terang');
        $this->assertStringContainsString('data-tone-dark="light"', $html, 'Sidebar terang di mode gelap memakai teks gelap');
        $this->assertStringContainsString('fonts.googleapis.com/css2?family=Poppins', $html);
    }

    public function test_preview_returns_css_without_saving(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'tenant_id' => 1]))
            ->postJson(route('settings.theme.preview'), $this->payload(['theme_brand' => '#f97316', 'theme_light_sidebar' => '#111827']))
            ->assertOk()
            ->assertJsonPath('tones.sidebar.light', 'dark')
            ->assertJson(fn ($json) => $json->where('css', fn ($css) => str_contains($css, '--brand:#f97316'))->etc());

        $this->assertNull(Setting::forTenant(1)->where('key', 'theme_brand')->value('value'));
    }

    public function test_technician_cannot_change_theme(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'tehnisi', 'tenant_id' => 1]))
            ->post(route('settings.theme'), $this->payload())
            ->assertForbidden();
    }
}
