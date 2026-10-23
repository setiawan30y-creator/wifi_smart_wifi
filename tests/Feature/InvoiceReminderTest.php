<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pengingat WA sebelum dan sesudah jatuh tempo.
 * Pelanggan uji jatuh tempo tanggal 10 Oktober 2026.
 */
class InvoiceReminderTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Invoice $invoice;
    private bool $gatewayUp = true;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(fn () => $this->gatewayUp
            ? Http::response(['status' => true], 200)
            : Http::response(['status' => false, 'reason' => 'device offline'], 200));
        Tenant::whereKey(1)->update(['status' => 'active']);

        $this->inTenant(function () {
            $package = Package::create(['name' => 'Paket Uji', 'price' => 150000, 'speed' => '10 Mbps']);

            $this->settings([
                'fonnte_token' => 'token-uji',
                'billing_auto_generate_enabled' => '0',
                'billing_auto_generate_wa_delay_min' => '0',
                'billing_auto_generate_wa_delay_max' => '0',
                'billing_reminder_time' => '08:00',
                'billing_reminder_before_enabled' => '1',
                'billing_reminder_before_days' => '[3,1,0]',
                'billing_overdue_wa_enabled' => '1',
                'billing_reminder_after_days' => '[1,3,7]',
            ]);

            $this->customer = Customer::create([
                'name' => 'Pelanggan Uji', 'phone' => '081234567890', 'address' => 'Alamat uji',
                'package_id' => $package->id, 'billing_day' => 10, 'status' => 'active',
                'status_rumah' => 'milik_sendiri', 'tanggal_pasang' => '2026-01-01',
            ]);

            Carbon::setTestNow(Carbon::create(2026, 10, 1, 1, 0, 0));
            $this->invoice = Invoice::create([
                'customer_id' => $this->customer->id, 'month' => 10, 'year' => 2026, 'amount' => 150000, 'status' => 'unpaid',
            ]);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function inTenant(callable $callback)
    {
        return app(TenantContext::class)->run(1, $callback);
    }

    private function settings(array $values): void
    {
        $this->inTenant(function () use ($values) {
            foreach ($values as $key => $value) {
                Setting::set($key, $value);
            }
        });
    }

    private function runAt(string $dateTime): void
    {
        Carbon::setTestNow(Carbon::parse($dateTime));
        $this->artisan('billing:automation')->assertSuccessful();
    }

    /** @return string[] label tahap yang tercatat terkirim */
    private function sentStages(): array
    {
        return InvoiceReminder::withoutGlobalScopes()->where('status', 'sent')->orderBy('id')->get()
            ->map(fn (InvoiceReminder $r) => $r->label())->all();
    }

    public function test_full_schedule_before_and_after_due_date(): void
    {
        foreach (range(5, 20) as $day) {
            $this->runAt(sprintf('2026-10-%02d 09:00', $day));
        }

        $this->assertSame(['H-3', 'H-1', 'Hari H', 'H+1', 'H+3', 'H+7'], $this->sentStages());
        Http::assertSentCount(6);
        $this->assertSame(3, (int) $this->invoice->fresh()->overdue_reminder_count);
    }

    public function test_nothing_sent_before_reminder_time_or_twice_a_day(): void
    {
        $this->runAt('2026-10-07 07:59');
        Http::assertNothingSent();

        $this->runAt('2026-10-07 08:00');
        $this->runAt('2026-10-07 08:01');
        $this->runAt('2026-10-07 20:00');

        Http::assertSentCount(1);
    }

    public function test_after_downtime_only_latest_stage_is_sent(): void
    {
        // Server mati sampai H+5: hanya tahap H+3 yang dikirim, bukan H-3..H+3 sekaligus.
        $this->runAt('2026-10-15 09:00');

        $this->assertSame(['H+3'], $this->sentStages());
        Http::assertSentCount(1);
    }

    public function test_paid_invoice_stops_reminders(): void
    {
        $this->runAt('2026-10-07 09:00');
        $this->invoice->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

        $this->runAt('2026-10-09 09:00');
        $this->runAt('2026-10-11 09:00');

        Http::assertSentCount(1);
    }

    public function test_payment_promise_pauses_overdue_reminders(): void
    {
        $this->invoice->forceFill(['payment_promise_date' => '2026-10-15'])->save();

        $this->runAt('2026-10-11 09:00');
        $this->runAt('2026-10-13 09:00');
        Http::assertNothingSent();

        // Pada tanggal janji: pengingat janji bayar (bukan pengingat H+).
        $this->runAt('2026-10-15 09:00');
        Http::assertSentCount(1);
        $this->assertSame([], $this->sentStages());

        // Janji lewat dan belum bayar: pengingat H+ berjalan lagi.
        $this->runAt('2026-10-17 09:00');
        $this->assertSame(['H+7'], $this->sentStages());
    }

    public function test_disabled_sections_send_nothing(): void
    {
        $this->settings(['billing_reminder_before_enabled' => '0', 'billing_overdue_wa_enabled' => '0']);

        $this->runAt('2026-10-09 09:00');
        $this->runAt('2026-10-11 09:00');

        Http::assertNothingSent();
    }

    public function test_before_reminder_uses_days_left_text(): void
    {
        $this->runAt('2026-10-09 09:00');

        Http::assertSent(fn (Request $request) => str_contains($request->body(), 'akan jatuh tempo besok'));
    }

    public function test_edited_default_message_is_used(): void
    {
        $this->settings(['billing_message_before' => 'PENGINGAT KHUSUS {nama_pelanggan} {sisa_hari}']);

        $this->runAt('2026-10-09 09:00');

        Http::assertSent(fn (Request $request) => str_contains($request->body(), 'PENGINGAT KHUSUS Pelanggan Uji besok'));
    }

    public function test_customer_with_several_overdue_invoices_gets_one_message(): void
    {
        $this->inTenant(fn () => Invoice::create([
            'customer_id' => $this->customer->id, 'month' => 9, 'year' => 2026, 'amount' => 150000, 'status' => 'unpaid',
        ]));

        $this->runAt('2026-10-11 09:00');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => str_contains($request->body(), 'Rp 300.000'));
    }

    public function test_failed_send_is_retried_the_next_day(): void
    {
        $this->gatewayUp = false;
        $this->runAt('2026-10-11 09:00');
        $this->gatewayUp = true;
        $this->runAt('2026-10-11 10:00');
        $this->assertSame([], $this->sentStages(), 'Gagal hari ini dicoba lagi besok, bukan tiap menit');
        $this->assertSame('failed', InvoiceReminder::withoutGlobalScopes()->sole()->status);

        $this->runAt('2026-10-12 09:00');
        $this->assertSame(['H+1'], $this->sentStages());
    }

    public function test_inactive_or_isolated_customer_gets_no_reminder(): void
    {
        foreach (['inactive', 'isolated'] as $status) {
            $this->customer->forceFill(['status' => $status])->save();

            $this->runAt('2026-10-09 09:00');
            $this->runAt('2026-10-11 09:00');
        }

        Http::assertNothingSent();
    }

    public function test_free_customer_gets_no_reminder(): void
    {
        $this->customer->forceFill(['billing_type' => 'social'])->save();

        $this->runAt('2026-10-09 09:00');

        Http::assertNothingSent();
    }
}
