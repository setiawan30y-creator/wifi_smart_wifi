<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Setting;
use App\Services\MikrotikWebhookToken;
use App\Services\WhatsAppGateway;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook dari script PPP Profile MikroTik saat klien terhubung (up) / terputus (down).
 * Mengirim notifikasi WA ke nomor admin tenant.
 *
 * URL wajib memuat token rahasia tenant (lihat Pengaturan → Trafik & MikroTik).
 * Respon selalu sama untuk username yang ada/tidak ada agar tidak bisa dipakai menebak data.
 */
class MikrotikWebhookController extends Controller
{
    public function clientStatus(Request $request, MikrotikWebhookToken $tokens)
    {
        $tenantId = $tokens->tenantIdFor($request->query('token', $request->input('token')));

        if (!$tenantId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $username = trim((string) $request->input('username'));
        $status = strtolower(trim((string) $request->input('status')));

        if ($username === '' || !in_array($status, ['up', 'down'], true)) {
            return response()->json(['success' => false, 'message' => 'Missing parameters'], 422);
        }

        app(TenantContext::class)->run($tenantId, function () use ($username, $status) {
            $customer = Customer::where('pppoe_username', $username)->first();
            $adminWa = Setting::get('admin_whatsapp');

            if (!$customer || blank($adminWa)) {
                Log::info('MikroTik webhook diabaikan.', [
                    'username' => $username,
                    'reason' => !$customer ? 'customer_not_found' : 'admin_whatsapp_empty',
                ]);

                return;
            }

            $result = app(WhatsAppGateway::class)->send($adminWa, $this->message($customer, $status));

            if (!($result['success'] ?? false)) {
                Log::warning('MikroTik webhook: WA gagal dikirim.', ['customer_id' => $customer->id, 'reason' => $result['message'] ?? null]);
            }
        });

        return response()->json(['success' => true]);
    }

    private function message(Customer $customer, string $status): string
    {
        $time = now()->format('d-m-Y H:i:s');

        if ($status === 'down') {
            return "⚠️ *PERINGATAN GANGGUAN KLIEN*\n\n"
                . "Koneksi internet klien *TERPUTUS* (DOWN).\n\n"
                . "👤 *Nama:* {$customer->name}\n"
                . "🔑 *Username (PPPoE):* {$customer->pppoe_username}\n"
                . "📞 *Nomor Klien:* {$customer->phone}\n"
                . "🕒 *Waktu Terputus:* {$time}\n\n"
                . "_Pesan otomatis dari sistem billing_";
        }

        return "✅ *INFORMASI PEMULIHAN KLIEN*\n\n"
            . "Koneksi internet klien *KEMBALI NORMAL* (UP).\n\n"
            . "👤 *Nama:* {$customer->name}\n"
            . "🔑 *Username (PPPoE):* {$customer->pppoe_username}\n"
            . "🕒 *Waktu Terhubung:* {$time}\n\n"
            . "_Pesan otomatis dari sistem billing_";
    }
}
