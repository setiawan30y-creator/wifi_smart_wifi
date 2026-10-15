<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MikrotikWebhookController extends Controller
{
    /**
     * Handle client up/down status from MikroTik PPP Profile Script
     */
    public function clientStatus(Request $request)
    {
        $username = $request->input('username');
        $status = $request->input('status'); // 'up' or 'down'

        if (!$username || !$status) {
            return response()->json(['success' => false, 'message' => 'Missing parameters'], 400);
        }

        // Find customer without tenant scope because this is a webhook with no session
        $customer = Customer::withoutGlobalScopes()->where('pppoe_username', $username)->first();

        if (!$customer) {
            Log::warning("MikroTik Webhook: Customer not found for username: {$username}");
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        // Get tenant ID
        $tenantId = $customer->tenant_id;

        // Get Admin WhatsApp and Fonnte Token for this specific tenant
        $adminWa = Setting::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('key', 'admin_whatsapp')
            ->value('value');
            
        $fonnteToken = Setting::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('key', 'fonnte_token')
            ->value('value');

        // Fallback to primary tenant if not found (optional, but good for multi-tenant setups)
        if (!$fonnteToken) {
            $fonnteToken = Setting::withoutGlobalScopes()
                ->where('tenant_id', 1)
                ->where('key', 'fonnte_token')
                ->value('value');
        }

        if (empty($adminWa) || empty($fonnteToken)) {
            Log::warning("MikroTik Webhook: Admin WA or Fonnte Token not configured for tenant {$tenantId}");
            return response()->json(['success' => false, 'message' => 'Not configured'], 400);
        }

        // Prepare the message
        $time = date('d-m-Y H:i:s');
        if ($status === 'down') {
            $message = "⚠️ *PERINGATAN GANGGUAN KLIEN*\n\n"
                     . "Koneksi internet klien telah *TERPUTUS* (DOWN).\n\n"
                     . "👤 *Nama:* {$customer->name}\n"
                     . "🔑 *Username (PPPoE):* {$customer->pppoe_username}\n"
                     . "📞 *Nomor Klien:* {$customer->phone}\n"
                     . "🕒 *Waktu Terputus:* {$time}\n\n"
                     . "_Pesan otomatis dari Sistem Billing RT/RW_";
        } else {
            $message = "✅ *INFORMASI PEMULIHAN KLIEN*\n\n"
                     . "Koneksi internet klien telah *KEMBALI NORMAL* (UP).\n\n"
                     . "👤 *Nama:* {$customer->name}\n"
                     . "🔑 *Username (PPPoE):* {$customer->pppoe_username}\n"
                     . "🕒 *Waktu Terhubung:* {$time}\n\n"
                     . "_Pesan otomatis dari Sistem Billing RT/RW_";
        }

        // Send via Fonnte
        try {
            $response = Http::withHeaders([
                'Authorization' => $fonnteToken
            ])->post('https://api.fonnte.com/send', [
                'target' => $adminWa,
                'message' => $message,
                'delay' => '2'
            ]);

            $result = $response->json();
            
            if ($response->successful() && isset($result['status']) && $result['status'] == true) {
                return response()->json(['success' => true, 'message' => 'Notification sent to Admin']);
            } else {
                Log::error("MikroTik Webhook: Fonnte API Error", ['response' => $result]);
                return response()->json(['success' => false, 'message' => 'Fonnte failed'], 500);
            }
        } catch (\Exception $e) {
            Log::error("MikroTik Webhook: Exception sending WA", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Exception occurred'], 500);
        }
    }
}
