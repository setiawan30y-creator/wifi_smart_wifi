<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\WhatsAppGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** WA Gateway provider "ALMARA Gateway WA" (pengganti Fonnte). @2026-Alifiandra */
class AlmaraGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function configure(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::withoutGlobalScopes()->updateOrCreate(['tenant_id' => 1, 'key' => $key], ['value' => $value]);
        }
    }

    public function test_endpoint_is_normalized_from_any_url(): void
    {
        $this->assertSame('https://wa.example.test/api/send', WhatsAppGateway::almaraEndpoint('https://wa.example.test'));
        $this->assertSame('https://wa.example.test/api/send', WhatsAppGateway::almaraEndpoint('https://wa.example.test/'));
        $this->assertSame('https://wa.example.test/api/send', WhatsAppGateway::almaraEndpoint('https://wa.example.test/api/send-message'));
        $this->assertSame('https://wa.example.test/api/send', WhatsAppGateway::almaraEndpoint('https://wa.example.test/api/v1/messages/send'));
        $this->assertSame('', WhatsAppGateway::almaraEndpoint('https://api.fonnte.com/send'));
    }

    public function test_sends_through_almara_with_only_url_and_token(): void
    {
        $this->configure([
            'wa_gateway_provider' => 'almara',
            'wa_gateway_endpoint' => 'https://wa.example.test',
            'wa_gateway_token' => 'alm_tokenperangkat',
            // sisa pengaturan lama Fonnte/custom harus diabaikan
            'wa_gateway_auth_prefix' => 'Bearer',
            'wa_gateway_target_field' => 'phone',
        ]);

        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = $request;

            return Http::response(['status' => true, 'detail' => 'success! message in queue', 'id' => ['01ABC']]);
        });

        $result = WhatsAppGateway::forTenant(1)->send('0812-3456-7890', 'Tagihan bulan ini');

        $this->assertTrue($result['success'], $result['message']);
        $this->assertCount(1, $sent);
        $this->assertSame('https://wa.example.test/api/send', $sent[0]->url());
        $this->assertSame('alm_tokenperangkat', $sent[0]->header('Authorization')[0]);
        $body = $sent[0]->body();
        $this->assertStringContainsString('name="target"', $body);
        $this->assertStringContainsString('Tagihan bulan ini', $body);
        $this->assertTrue(WhatsAppGateway::forTenant(1)->usesFonnteFormat());
        $this->assertFalse(WhatsAppGateway::forTenant(1)->isFonnte()); // broadcast dikirim per pelanggan
    }

    public function test_almara_error_message_is_reported(): void
    {
        $this->configure([
            'wa_gateway_provider' => 'almara',
            'wa_gateway_endpoint' => 'https://wa.example.test',
            'wa_gateway_token' => 'alm_salah',
        ]);
        Http::fake(fn () => Http::response(['success' => false, 'error' => ['code' => 'UNAUTHORIZED', 'message' => 'API key tidak valid.']], 401));

        $result = WhatsAppGateway::forTenant(1)->send('081234567890', 'Halo');

        $this->assertFalse($result['success']);
        $this->assertSame('API key tidak valid.', $result['message']);
    }

    public function test_missing_url_gives_clear_message(): void
    {
        $this->configure(['wa_gateway_provider' => 'almara', 'wa_gateway_endpoint' => 'https://api.fonnte.com/send', 'wa_gateway_token' => 'alm_x']);
        Http::fake();

        $result = WhatsAppGateway::forTenant(1)->send('081234567890', 'Halo');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('URL ALMARA Gateway WA', $result['message']);
        Http::assertNothingSent();
    }
}
