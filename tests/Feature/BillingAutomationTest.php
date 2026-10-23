<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Perilaku billing otomatis (command billing:automation).
 * Test ini ditulis terhadap perilaku lama agar restruktur tidak mengubah hasil.
 */
class BillingAutomationTest extends TestCase
{
    use RefreshDatabase;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 10, 3, 2, 0, 0));
        Http::fake(['*' => Http::response(['status' => true], 200)]);

        Tenant::whereKey(1)->update(['status' => 'active']);

        $this->inTenant(1, function () {
            $this->package = Package::create(['name' => 'Paket 10 Mbps', 'price' => 150000, 'speed' => '10 Mbps']);
            foreach ([
                'fonnte_token' => 'token-uji',
                'billing_auto_generate_enabled' => '1',
                'billing_auto_generate_time' => '02:00',
                'billing_auto_generate_days_before_due' => '3',
                'billing_auto_generate_send_wa' => '1',
                'billing_auto_generate_wa_delay_min' => '0',
                'billing_auto_generate_wa_delay_max' => '0',
                'billing_auto_generate_group_same_phone' => '1',
                'billing_overdue_wa_enabled' => '1',
                'billing_overdue_days_after_due' => '1',
                'billing_overdue_max_reminders' => '2',
                'billing_reminder_time' => '02:00',
            ] as $key => $value) {
                Setting::set($key, $value);
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function inTenant(int $tenantId, callable $callback)
    {
        return app(TenantContext::class)->run($tenantId, $callback);
    }

    private function customer(array $attributes = [], int $tenantId = 1): Customer
    {
        return $this->inTenant($tenantId, fn () => Customer::create(array_merge([
            'name' => 'Pelanggan ' . uniqid(),
            'phone' => '0812' . random_int(10000000, 99999999),
            'address' => 'Alamat uji',
            'package_id' => $this->package->id ?? null,
            'billing_day' => 5,
            'status' => 'active',
            'status_rumah' => 'milik_sendiri',
            'tanggal_pasang' => '2026-01-01',
        ], $attributes)));
    }

    private function runBilling(array $options = ['--force' => true]): void
    {
        $this->artisan('billing:automation', $options)->assertSuccessful();
    }

    private function invoicesFor(Customer $customer)
    {
        return Invoice::withoutGlobalScopes()->where('customer_id', $customer->id)->get();
    }

    public function test_generates_invoice_for_billable_customer_in_window(): void
    {
        $customer = $this->customer();

        $this->runBilling();

        $invoice = $this->invoicesFor($customer)->sole();
        $this->assertSame(10, (int) $invoice->month);
        $this->assertSame(2026, (int) $invoice->year);
        $this->assertEquals(150000, (float) $invoice->amount);
        $this->assertSame(1, (int) $invoice->tenant_id);
        Http::assertSentCount(1);
    }

    public function test_does_not_create_duplicate_on_second_run(): void
    {
        $customer = $this->customer();

        $this->runBilling();
        $this->runBilling();

        $this->assertCount(1, $this->invoicesFor($customer));
    }

    public function test_skips_free_prepaid_inactive_and_not_yet_installed_customers(): void
    {
        $free = $this->customer(['billing_type' => 'social']);
        $prepaid = $this->customer(['billing_mode' => 'prepaid']);
        $inactive = $this->customer(['status' => 'inactive']);
        $future = $this->customer(['tanggal_pasang' => '2026-12-01']);

        $this->runBilling();

        foreach ([$free, $prepaid, $inactive, $future] as $customer) {
            $this->assertCount(0, $this->invoicesFor($customer), $customer->name);
        }
    }

    public function test_waits_until_window_before_due_date(): void
    {
        $customer = $this->customer(['billing_day' => 20]);

        $this->runBilling();

        $this->assertCount(0, $this->invoicesFor($customer));
    }

    public function test_generation_waits_for_configured_time_without_force(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 1, 0, 0));
        $customer = $this->customer();

        $this->runBilling([]);

        $this->assertCount(0, $this->invoicesFor($customer));
    }

    public function test_customers_with_same_phone_get_one_grouped_message(): void
    {
        $this->customer(['phone' => '081211112222']);
        $this->customer(['phone' => '081211112222']);

        $this->runBilling();

        Http::assertSentCount(1);
    }

    public function test_other_tenant_is_not_billed_when_its_automation_is_off(): void
    {
        $tenantB = Tenant::create(['name' => 'Perusahaan B', 'status' => 'active']);
        $this->inTenant($tenantB->id, fn () => $this->package = Package::create(['name' => 'Paket B', 'price' => 100000, 'speed' => '5 Mbps']));
        $customerB = $this->customer([], $tenantB->id);

        $this->runBilling();

        $this->assertCount(0, $this->invoicesFor($customerB));
    }

    public function test_overdue_reminder_is_sent_once_per_day_up_to_max(): void
    {
        // Pengaturan lama "mulai H+1, maks. 2 kali" => tahap H+1 dan H+2.
        $customer = $this->customer(['billing_day' => 2]);
        $invoice = $this->inTenant(1, fn () => Invoice::create([
            'customer_id' => $customer->id, 'month' => 10, 'year' => 2026, 'amount' => 150000, 'status' => 'unpaid',
        ]));
        Setting::forTenant(1)->where('key', 'billing_auto_generate_enabled')->update(['value' => '0']);

        $this->runBilling();
        $this->runBilling();
        $this->assertSame(1, (int) $invoice->fresh()->overdue_reminder_count);

        Carbon::setTestNow(Carbon::create(2026, 10, 4, 2, 0, 0));
        $this->runBilling();
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 2, 0, 0));
        $this->runBilling();

        $this->assertSame(2, (int) $invoice->fresh()->overdue_reminder_count);
    }

    public function test_paid_invoice_gets_no_overdue_reminder(): void
    {
        $customer = $this->customer(['billing_day' => 1]);
        $this->inTenant(1, fn () => Invoice::create([
            'customer_id' => $customer->id, 'month' => 9, 'year' => 2026, 'amount' => 150000, 'status' => 'paid', 'paid_at' => now(),
        ]));
        Setting::forTenant(1)->where('key', 'billing_auto_generate_enabled')->update(['value' => '0']);

        $this->runBilling();

        Http::assertNothingSent();
    }

    public function test_payment_promise_reminder_is_sent_once(): void
    {
        $customer = $this->customer();
        $invoice = $this->inTenant(1, fn () => Invoice::create([
            'customer_id' => $customer->id, 'month' => 9, 'year' => 2026, 'amount' => 150000, 'status' => 'unpaid',
            'payment_promise_date' => '2026-10-02',
        ]));
        Setting::forTenant(1)->whereIn('key', ['billing_auto_generate_enabled', 'billing_overdue_wa_enabled'])->update(['value' => '0']);

        $this->runBilling();
        $this->runBilling();

        $this->assertNotNull($invoice->fresh()->payment_promise_wa_sent_at);
        Http::assertSentCount(1);
    }

    public function test_generation_catches_up_later_the_same_day_but_only_once(): void
    {
        // Scheduler sempat mati pada jam 02:00; saat hidup lagi jam 09:00 generate tetap jalan.
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 9, 0, 0));
        $customer = $this->customer();

        $this->runBilling([]);
        $this->assertCount(1, $this->invoicesFor($customer));

        $second = $this->customer();
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 9, 5, 0));
        $this->runBilling([]);

        $this->assertCount(0, $this->invoicesFor($second), 'Generate hanya sekali per hari tanpa --force');
    }

    public function test_messages_use_tenant_wa_gateway_settings(): void
    {
        $this->inTenant(1, fn () => Setting::set('wa_gateway_endpoint', 'https://gateway.contoh.test/send'));
        $this->customer();

        $this->runBilling();

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://gateway.contoh.test/send'));
    }
}
