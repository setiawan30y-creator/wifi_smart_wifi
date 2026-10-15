<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $superadmin;
    protected $tehnisi;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed settings
        Setting::set('company_name', 'Billing RT/RW Test');
        
        // Create users
        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'super@rtrw.net',
            'password' => bcrypt('password'),
        ]);

        $this->tehnisi = User::factory()->create([
            'role' => 'tehnisi',
            'email' => 'tech@rtrw.net',
            'password' => bcrypt('password'),
        ]);

        // Default permission matrix
        $permissions = [
            'tehnisi' => [
                'dashboard' => true,
                'customers' => true,
                'packages' => false,
                'invoices' => false,
                'odps' => true,
                'network_map' => true,
                'device_stocks' => true,
                'expenses' => false,
                'financial_reports' => false,
                'wa_templates' => false,
                'settings' => false
            ]
        ];
        Setting::set('permissions_map', $permissions);
    }

    /**
     * Test guests are redirected to login.
     */
    public function test_guests_are_redirected_to_login()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test successful login.
     */
    public function test_successful_login()
    {
        $response = $this->post('/login', [
            'email' => 'super@rtrw.net',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->superadmin);
    }

    /**
     * Test superadmin can access settings.
     */
    public function test_superadmin_can_access_settings()
    {
        $response = $this->actingAs($this->superadmin)->get(route('settings.index'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Sistem');
        $response->assertSee('Billing RT/RW Test');
    }

    /**
     * Test technician is forbidden from settings.
     */
    public function test_technician_is_forbidden_from_settings()
    {
        $response = $this->actingAs($this->tehnisi)->get(route('settings.index'));
        $response->assertStatus(403);
    }

    /**
     * Test technician is forbidden from financial reports.
     */
    public function test_technician_is_forbidden_from_financial_reports()
    {
        $response = $this->actingAs($this->tehnisi)->get(route('financial-reports.index'));
        $response->assertStatus(403);
    }

    /**
     * Test updating company profile with logo upload.
     */
    public function test_can_update_company_profile()
    {
        Storage::fake('public');
        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($this->superadmin)->post(route('settings.company'), [
            'company_name' => 'Billing New Name',
            'company_address' => 'New Address',
            'company_phone' => '089999999',
            'company_logo' => $logo,
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'company']));
        $this->assertEquals('Billing New Name', Setting::get('company_name'));
        $this->assertEquals('New Address', Setting::get('company_address'));
        
        $logoPath = Setting::get('company_logo');
        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);
    }

    /**
     * Test creating a new user.
     */
    public function test_can_create_new_user()
    {
        $response = $this->actingAs($this->superadmin)->post(route('settings.users.store'), [
            'name' => 'Staf Baru',
            'email' => 'staf@rtrw.net',
            'password' => 'password123',
            'role' => 'tehnisi',
            'phone' => '08777777',
            'address' => 'Staf Address',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'users']));
        $this->assertDatabaseHas('users', [
            'name' => 'Staf Baru',
            'email' => 'staf@rtrw.net',
            'role' => 'tehnisi',
            'phone' => '08777777',
        ]);
    }

    /**
     * Test updating user profile.
     */
    public function test_can_update_user_profile()
    {
        $response = $this->actingAs($this->superadmin)->put(route('settings.users.update', $this->tehnisi->id), [
            'name' => 'Staf Updated',
            'phone' => '08888888',
            'address' => 'Updated Address',
            'password' => 'newpassword123',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'users']));
        $this->assertDatabaseHas('users', [
            'id' => $this->tehnisi->id,
            'name' => 'Staf Updated',
            'phone' => '08888888',
            'address' => 'Updated Address',
        ]);

        $this->tehnisi->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $this->tehnisi->password));
    }

    /**
     * Test deleting a user.
     */
    public function test_can_delete_user()
    {
        $response = $this->actingAs($this->superadmin)->delete(route('settings.users.destroy', $this->tehnisi->id));

        $response->assertRedirect(route('settings.index', ['tab' => 'users']));
        $this->assertDatabaseMissing('users', [
            'id' => $this->tehnisi->id,
        ]);
    }
}
