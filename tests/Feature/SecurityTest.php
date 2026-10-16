<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MikrotikWebhookToken;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response(['status' => true, 'success' => true], 200)]);

        app(TenantContext::class)->run(1, function () {
            $package = Package::create(['name' => 'Paket Uji', 'price' => 100000, 'speed' => '10 Mbps']);
            $this->customer = Customer::create([
                'name' => 'Pelanggan Uji',
                'email' => 'pelanggan@contoh.test',
                'phone' => '6281200000001',
                'address' => 'Alamat uji',
                'package_id' => $package->id,
                'billing_day' => 5,
                'status' => 'active',
                'status_rumah' => 'milik_sendiri',
                'pppoe_username' => 'pelanggan-uji',
            ]);
            Setting::set('fonnte_token', 'token-uji');
            Setting::set('admin_whatsapp', '6281299999999');
        });

        $this->clientUser = User::factory()->create([
            'role' => 'client',
            'tenant_id' => 1,
            'email' => 'pelanggan@contoh.test',
        ]);
    }

    // ---------- Wallet ----------

    public function test_client_topup_does_not_add_balance(): void
    {
        $this->actingAs($this->clientUser)->post(route('client.wallet.topup'), ['amount' => 50000]);

        $this->assertEquals(0, (float) $this->customer->fresh()->balance);
    }

    public function test_client_cannot_topup_via_api(): void
    {
        $this->actingAs($this->clientUser, 'sanctum')
            ->postJson('/api/v1/wallet/topup', ['amount' => 50000, 'customer_id' => $this->customer->id])
            ->assertForbidden();

        $this->assertEquals(0, (float) $this->customer->fresh()->balance);
    }

    public function test_client_pays_oldest_invoice_with_balance(): void
    {
        $this->customer->forceFill(['balance' => 150000])->save();
        $invoice = app(TenantContext::class)->run(1, fn () => Invoice::create([
            'customer_id' => $this->customer->id, 'month' => 9, 'year' => 2026, 'amount' => 100000, 'status' => 'unpaid',
        ]));

        $this->actingAs($this->clientUser)->post(route('client.wallet.pay-wifi'))->assertRedirect();

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertEquals(50000, (float) $this->customer->fresh()->balance);
    }

    // ---------- Lupa password ----------

    public function test_forgot_password_gives_same_answer_for_unknown_number(): void
    {
        $this->post(route('password.email'), ['phone' => '0899999999'])
            ->assertRedirect(route('password.verify'))
            ->assertSessionHas('success');
    }

    public function test_otp_is_invalidated_after_too_many_wrong_attempts(): void
    {
        $user = User::factory()->create(['tenant_id' => 1, 'phone' => '081277777777']);

        $this->post(route('password.email'), ['phone' => '081277777777']);
        $this->assertNotNull(Cache::get('password_reset_otp_' . $user->id));

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.verify.post'), ['otp' => '000000']);
        }

        $this->assertNull(Cache::get('password_reset_otp_' . $user->id));
    }

    // ---------- Tripay ----------

    private function groupTransaction(): PaymentTransaction
    {
        return app(TenantContext::class)->run(1, function () {
            Setting::set('tripay_private_key', 'kunci-rahasia');
            $invoice = Invoice::create([
                'customer_id' => $this->customer->id, 'month' => 10, 'year' => 2026, 'amount' => 100000, 'status' => 'unpaid',
            ]);

            return PaymentTransaction::create([
                'customer_id' => $this->customer->id,
                'reference' => 'INV-GRP-UJI001',
                'amount' => 100000,
                'invoice_ids' => [$invoice->id],
                'status' => 'UNPAID',
            ]);
        });
    }

    private function sendCallback(array $body, ?string $signature = null)
    {
        $json = json_encode($body);

        return $this->call('POST', route('tripay.callback'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'HTTP_X_CALLBACK_SIGNATURE' => $signature ?? hash_hmac('sha256', $json, 'kunci-rahasia'),
        ], $json);
    }

    public function test_tripay_callback_rejects_bad_signature(): void
    {
        $this->groupTransaction();

        $this->sendCallback(['reference' => 'T1', 'merchant_ref' => 'INV-GRP-UJI001', 'status' => 'PAID', 'total_amount' => 100000], 'salah')
            ->assertStatus(400);
    }

    public function test_tripay_callback_rejects_wrong_amount(): void
    {
        $transaction = $this->groupTransaction();

        $this->sendCallback(['reference' => 'T1', 'merchant_ref' => 'INV-GRP-UJI001', 'status' => 'PAID', 'total_amount' => 1000])
            ->assertStatus(400);

        $this->assertSame('UNPAID', $transaction->fresh()->status);
    }

    public function test_tripay_callback_marks_group_paid_once(): void
    {
        $transaction = $this->groupTransaction();
        $body = ['reference' => 'T1', 'merchant_ref' => 'INV-GRP-UJI001', 'status' => 'PAID', 'total_amount' => 101500, 'fee_customer' => 1500];

        $this->sendCallback($body)->assertOk();
        $this->sendCallback($body)->assertOk();

        $this->assertSame('PAID', $transaction->fresh()->status);
        $this->assertSame('paid', Invoice::withoutGlobalScopes()->find($transaction->invoice_ids[0])->status);
    }

    // ---------- Webhook MikroTik ----------

    public function test_mikrotik_webhook_requires_token(): void
    {
        $this->postJson(route('mikrotik.client-status'), ['username' => 'pelanggan-uji', 'status' => 'down'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_mikrotik_webhook_with_valid_token_notifies_admin(): void
    {
        $token = app(TenantContext::class)->run(1, fn () => app(MikrotikWebhookToken::class)->current());

        $this->postJson(route('mikrotik.client-status', ['token' => $token]), ['username' => 'pelanggan-uji', 'status' => 'down'])
            ->assertOk();

        Http::assertSentCount(1);
    }

    // ---------- Role & trial ----------

    public function test_admin_cannot_create_superadmin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);

        $this->actingAs($admin)->post(route('settings.users.store'), [
            'name' => 'Akun Baru', 'email' => 'baru@contoh.test', 'password' => 'rahasia123', 'role' => 'superadmin',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'baru@contoh.test']);
    }

    public function test_admin_cannot_change_superadmin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        $owner = User::factory()->create(['role' => 'superadmin', 'tenant_id' => 1]);

        $this->actingAs($admin)->put(route('settings.accounts.update', $owner), [
            'email' => $owner->email, 'role' => 'admin',
        ])->assertForbidden();
    }

    public function test_expired_tenant_locks_its_superadmin(): void
    {
        $tenant = Tenant::create(['name' => 'Perusahaan Trial', 'status' => 'active', 'subscription_status' => 'trial', 'trial_ends_at' => now()->subDay()]);
        $owner = User::factory()->create(['role' => 'superadmin', 'tenant_id' => $tenant->id]);

        $this->actingAs($owner)->get(route('customers.index'))->assertRedirect(route('subscription.locked'));
    }
}
