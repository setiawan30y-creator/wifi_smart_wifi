<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WaTemplate;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(int $tenantId = 1): User
    {
        return User::factory()->create(['role' => 'admin', 'tenant_id' => $tenantId]);
    }

    private function setting(string $key, int $tenantId = 1): ?string
    {
        return Setting::forTenant($tenantId)->where('key', $key)->value('value');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'billing_auto_generate_enabled' => '1',
            'billing_auto_generate_days_before_due' => 5,
            'billing_auto_generate_time' => '02:30',
            'billing_auto_generate_send_wa' => '1',
            'billing_auto_generate_wa_delay_min' => 3,
            'billing_auto_generate_wa_delay_max' => 9,
            'billing_reminder_time' => '08:15',
            'billing_reminder_before_enabled' => '1',
            'billing_reminder_before_days' => ['3', '1', '0'],
            'billing_overdue_wa_enabled' => '1',
            'billing_reminder_after_days' => ['1', '3', '7', '14'],
        ], $overrides);
    }

    public function test_page_renders_with_defaults(): void
    {
        $this->withoutVite();

        $this->actingAs($this->admin())
            ->get(route('invoices.automation-settings'))
            ->assertOk()
            ->assertSee('Pengingat sebelum jatuh tempo')
            ->assertSee('Pengingat sesudah jatuh tempo')
            ->assertSee('H+7');
    }

    public function test_saves_schedule_and_keeps_legacy_keys_in_sync(): void
    {
        $this->actingAs($this->admin())
            ->post(route('invoices.automation-settings.update'), $this->payload())
            ->assertRedirect(route('invoices.automation-settings'))
            ->assertSessionHasNoErrors();

        $this->assertSame('1', $this->setting('billing_auto_generate_enabled'));
        $this->assertSame('[3,1,0]', $this->setting('billing_reminder_before_days'));
        $this->assertSame('[1,3,7,14]', $this->setting('billing_reminder_after_days'));
        $this->assertSame('08:15', $this->setting('billing_reminder_time'));
        $this->assertSame('0', $this->setting('billing_auto_generate_group_same_phone'));
        $this->assertSame('1', $this->setting('billing_overdue_days_after_due'));
        $this->assertSame('4', $this->setting('billing_overdue_max_reminders'));
    }

    public function test_default_messages_can_be_edited_and_reset(): void
    {
        $admin = $this->admin();
        $custom = "Halo {nama_pelanggan}, tagihan {periode} sebesar {nominal} jatuh tempo {sisa_hari}.";

        $this->actingAs($admin)
            ->post(route('invoices.automation-settings.update'), $this->payload([
                'billing_message_before' => $custom,
                'billing_message_after' => \App\Services\Billing\BillingMessages::DEFAULT_OVERDUE,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($custom, $this->setting('billing_message_before'));
        $this->assertSame('', $this->setting('billing_message_after'), 'Teks asli tidak disimpan sebagai versi khusus');

        app(\App\Support\Tenancy\TenantContext::class)->run(1, function () use ($custom) {
            $this->assertSame($custom, \App\Services\Billing\BillingMessages::defaultText('before'));
            $this->assertSame(\App\Services\Billing\BillingMessages::DEFAULT_OVERDUE, \App\Services\Billing\BillingMessages::defaultText('after'));
        });

        $this->withoutVite();
        $this->actingAs($admin)->get(route('invoices.automation-settings'))
            ->assertOk()
            ->assertSee('Ubah pesan bawaan')
            ->assertSee('jatuh tempo {sisa_hari}.', false);
    }

    public function test_enabled_section_needs_at_least_one_day(): void
    {
        $this->actingAs($this->admin())
            ->post(route('invoices.automation-settings.update'), $this->payload(['billing_reminder_after_days' => []]))
            ->assertSessionHasErrors('billing_reminder_after_days');
    }

    public function test_template_of_another_tenant_is_rejected(): void
    {
        $tenantB = Tenant::create(['name' => 'Perusahaan B', 'status' => 'active']);
        $foreign = app(TenantContext::class)->run($tenantB->id, fn () => WaTemplate::create(['name' => 'b', 'title' => 'Template B', 'content' => 'x']));

        $this->actingAs($this->admin())
            ->post(route('invoices.automation-settings.update'), $this->payload(['billing_overdue_wa_template_id' => $foreign->id]))
            ->assertSessionHasErrors('billing_overdue_wa_template_id');
    }

    public function test_technician_without_invoice_permission_is_forbidden(): void
    {
        $tech = User::factory()->create(['role' => 'tehnisi', 'tenant_id' => 1]);

        $this->actingAs($tech)->get(route('invoices.automation-settings'))->assertForbidden();
        $this->actingAs($tech)->post(route('invoices.automation-settings.update'), $this->payload())->assertForbidden();
    }
}
