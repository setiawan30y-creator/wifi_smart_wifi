<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test displaying the templates index via settings page tab.
     */
    public function test_can_display_wa_templates_index()
    {
        // Populate default templates via seeder
        $this->seed(\Database\Seeders\WaTemplateSeeder::class);

        $response = $this->actingAs($this->user)->get(route('settings.index', ['tab' => 'wa-templates']));

        $response->assertStatus(200);
        $response->assertViewHas('waTemplates');
        $response->assertSee('Kirim Tagihan');
        $response->assertSee('Konfirmasi Pembayaran');
    }

    /**
     * Test wa-templates.index route redirects to settings.index tab.
     */
    public function test_wa_templates_index_redirects_to_settings_tab()
    {
        $response = $this->actingAs($this->user)->get(route('wa-templates.index'));
        $response->assertRedirect(route('settings.index', ['tab' => 'wa-templates']));
    }

    /**
     * Test storing a custom template.
     */
    public function test_can_store_custom_wa_template()
    {
        $response = $this->actingAs($this->user)->post(route('wa-templates.store'), [
            'name' => 'tagihan_ulang_cepat',
            'title' => 'Tagihan Ulang Cepat',
            'content' => 'Halo {nama_pelanggan}, tagihan Anda sudah lewat jatuh tempo.',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'wa-templates']));
        $this->assertDatabaseHas('wa_templates', [
            'name' => 'tagihan_ulang_cepat',
            'title' => 'Tagihan Ulang Cepat',
            'is_system' => false,
        ]);
    }

    /**
     * Test updating a template.
     */
    public function test_can_update_wa_template()
    {
        $tmpl = WaTemplate::create([
            'name' => 'custom_tmpl',
            'title' => 'Custom Template',
            'content' => 'Old content',
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->user)->put(route('wa-templates.update', $tmpl->id), [
            'name' => 'custom_tmpl_updated',
            'title' => 'Custom Template Updated',
            'content' => 'New content',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'wa-templates']));
        $this->assertDatabaseHas('wa_templates', [
            'id' => $tmpl->id,
            'name' => 'custom_tmpl_updated',
            'title' => 'Custom Template Updated',
            'content' => 'New content',
        ]);
    }

    /**
     * Test updating a system template protects its name.
     */
    public function test_updating_system_template_protects_its_name()
    {
        $tmpl = WaTemplate::create([
            'name' => 'kirim_tagihan',
            'title' => 'Kirim Tagihan',
            'content' => 'Old content',
            'is_system' => true,
        ]);

        $response = $this->actingAs($this->user)->put(route('wa-templates.update', $tmpl->id), [
            'name' => 'kirim_tagihan_hacked',
            'title' => 'Kirim Tagihan Updated',
            'content' => 'New content',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'wa-templates']));
        
        // Name should NOT change, but title and content can
        $this->assertDatabaseHas('wa_templates', [
            'id' => $tmpl->id,
            'name' => 'kirim_tagihan', // Remains unchanged
            'title' => 'Kirim Tagihan Updated',
            'content' => 'New content',
        ]);
    }

    /**
     * Test deleting a custom template.
     */
    public function test_can_delete_custom_wa_template()
    {
        $tmpl = WaTemplate::create([
            'name' => 'custom_tmpl',
            'title' => 'Custom Template',
            'content' => 'Some content',
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->user)->delete(route('wa-templates.destroy', $tmpl->id));

        $response->assertRedirect(route('settings.index', ['tab' => 'wa-templates']));
        $this->assertDatabaseMissing('wa_templates', [
            'id' => $tmpl->id,
        ]);
    }

    /**
     * Test deleting a system template is blocked.
     */
    public function test_cannot_delete_system_wa_template()
    {
        $tmpl = WaTemplate::create([
            'name' => 'kirim_tagihan',
            'title' => 'Kirim Tagihan',
            'content' => 'Some content',
            'is_system' => true,
        ]);

        $response = $this->actingAs($this->user)->delete(route('wa-templates.destroy', $tmpl->id));

        $response->assertRedirect(route('settings.index', ['tab' => 'wa-templates']));
        $response->assertSessionHas('error');
        
        // Template should still exist in database
        $this->assertDatabaseHas('wa_templates', [
            'id' => $tmpl->id,
        ]);
    }
}
