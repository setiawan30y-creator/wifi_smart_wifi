<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Jasa WA gateway siap pakai selain Fonnte: cukup URL server + token (+ instance/session). */
class WaGatewayPresetsTest extends TestCase
{
    use RefreshDatabase;

    private function configure(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::withoutGlobalScopes()->updateOrCreate(['tenant_id' => 1, 'key' => $key], ['value' => $value]);
        }
    }

    private function sendAndCapture(array $settings, array $response = ['status' => true]): Request
    {
        // Sisa pengaturan Fonnte/custom lama tidak boleh ikut terpakai.
        $this->configure($settings + [
            'wa_gateway_auth_prefix' => 'Bearer',
            'wa_gateway_target_field' => 'target',
            'wa_gateway_extra_payload' => '{"countryCode":"62"}',
        ]);

        $sent = null;
        Http::fake(function (Request $request) use (&$sent, $response) {
            $sent = $request;

            return Http::response($response);
        });

        $result = WhatsAppGateway::forTenant(1)->send('0812-3456-7890', 'Tagihan bulan ini');
        $this->assertTrue($result['success'], $result['message']);

        return $sent;
    }

    public function test_wablas(): void
    {
        $request = $this->sendAndCapture([
            'wa_gateway_provider' => 'wablas',
            'wa_gateway_endpoint' => 'https://tegal.wablas.com/api/send-message',
            'wa_gateway_token' => 'tokenabc.secretxyz',
        ]);

        $this->assertSame('https://tegal.wablas.com/api/send-message', $request->url());
        $this->assertSame('tokenabc.secretxyz', $request->header('Authorization')[0]);
        $this->assertSame(['phone' => '6281234567890', 'message' => 'Tagihan bulan ini'], $request->data());
    }

    public function test_starsender_uses_default_url(): void
    {
        $request = $this->sendAndCapture([
            'wa_gateway_provider' => 'starsender',
            'wa_gateway_endpoint' => 'https://api.fonnte.com/send',
            'wa_gateway_token' => 'kunci-starsender',
        ], ['success' => true]);

        $this->assertSame('https://api.starsender.online/api/send', $request->url());
        $this->assertSame(['messageType' => 'text', 'to' => '6281234567890', 'body' => 'Tagihan bulan ini'], $request->data());
    }

    public function test_evolution_api(): void
    {
        $request = $this->sendAndCapture([
            'wa_gateway_provider' => 'evolution',
            'wa_gateway_endpoint' => 'https://evo.example.test/',
            'wa_gateway_instance' => 'kantor-1',
            'wa_gateway_token' => 'evo-key',
        ], ['key' => ['id' => 'ABC']]);

        $this->assertSame('https://evo.example.test/message/sendText/kantor-1', $request->url());
        $this->assertSame('evo-key', $request->header('apikey')[0]);
        $this->assertSame(['number' => '6281234567890', 'text' => 'Tagihan bulan ini'], $request->data());
    }

    public function test_waha(): void
    {
        $request = $this->sendAndCapture([
            'wa_gateway_provider' => 'waha',
            'wa_gateway_endpoint' => 'https://waha.example.test',
            'wa_gateway_token' => 'waha-key',
        ], ['id' => 'true_628@c.us_ABC']);

        $this->assertSame('https://waha.example.test/api/sendText', $request->url());
        $this->assertSame('waha-key', $request->header('X-Api-Key')[0]);
        $this->assertSame(['session' => 'default', 'chatId' => '6281234567890@c.us', 'text' => 'Tagihan bulan ini'], $request->data());
    }

    public function test_gateway_error_is_reported(): void
    {
        $this->configure(['wa_gateway_provider' => 'wablas', 'wa_gateway_endpoint' => 'https://tegal.wablas.com', 'wa_gateway_token' => 'salah']);
        Http::fake(fn () => Http::response(['status' => false, 'message' => 'token invalid'], 200));

        $result = WhatsAppGateway::forTenant(1)->send('081234567890', 'Halo');

        $this->assertFalse($result['success']);
        $this->assertSame('token invalid', $result['message']);
    }

    public function test_missing_server_url_is_reported_without_request(): void
    {
        $this->configure(['wa_gateway_provider' => 'evolution', 'wa_gateway_endpoint' => '', 'wa_gateway_token' => 'x']);
        Http::fake();

        $result = WhatsAppGateway::forTenant(1)->send('081234567890', 'Halo');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('URL server Evolution API', $result['message']);
        Http::assertNothingSent();
    }

    public function test_settings_accept_new_providers(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);

        $this->actingAs($admin)->get(route('settings.index'))->assertOk()->assertSee('Evolution API (server sendiri)');

        $this->actingAs($admin)->post(route('settings.company'), [
            'company_name' => 'Perusahaan', 'company_address' => 'Alamat', 'company_phone' => '081200000000', 'wa_gateway_provider' => 'waha',
            'wa_gateway_endpoint' => 'https://waha.example.test', 'wa_gateway_token' => 'k', 'wa_gateway_instance' => 'sesi-1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('waha', Setting::forTenant(1)->where('key', 'wa_gateway_provider')->value('value'));
        $this->assertSame('sesi-1', Setting::forTenant(1)->where('key', 'wa_gateway_instance')->value('value'));
    }
}
