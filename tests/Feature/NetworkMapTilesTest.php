<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peta dasar Peta Jaringan: tanpa API key CARTO memakai OpenStreetMap
 * (CARTO menampilkan watermark "API KEY REQUIRED" untuk permintaan tanpa key).
 */
class NetworkMapTilesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    }

    public function test_without_key_uses_openstreetmap(): void
    {
        $html = $this->actingAs($this->admin())->get(route('network-map'))->assertOk()->getContent();

        $this->assertStringContainsString('const cartoKey = null;', $html);
        $this->assertStringContainsString('tile.openstreetmap.org', $html);
    }

    public function test_with_tenant_key_uses_carto_with_key(): void
    {
        app(TenantContext::class)->run(1, fn () => Setting::set('map_carto_api_key', 'kunci-uji_123'));

        $this->actingAs($this->admin())->get(route('network-map'))
            ->assertOk()
            ->assertSee('const cartoKey = "kunci-uji_123";', false);
    }

    public function test_admin_can_save_map_key_and_invalid_key_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('settings.map'), ['map_carto_api_key' => 'abc"><script>'])
            ->assertSessionHasErrors('map_carto_api_key');

        $this->actingAs($admin)->post(route('settings.map'), ['map_carto_api_key' => ' kunci.valid-1 '])
            ->assertRedirect(route('settings.index', ['tab' => 'appearance']));

        $this->assertSame('kunci.valid-1', Setting::forTenant(1)->where('key', 'map_carto_api_key')->value('value'));
    }
}
