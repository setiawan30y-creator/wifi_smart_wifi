<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        // Clean phone number format
        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Find user by phone, bypassing TenantScope since we don't know the tenant yet
        $user = User::withoutGlobalScopes()->where('phone', $phone)
                    ->orWhere('phone', '0' . substr($phone, 2))
                    ->orWhere('phone', '+' . $phone)
                    ->first();

        if (!$user) {
            return back()->withErrors(['phone' => 'Nomor WhatsApp tidak terdaftar di sistem.'])->withInput();
        }

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store OTP in cache for 5 minutes
        Cache::put('password_reset_otp_' . $user->id, $otp, now()->addMinutes(5));
        
        // Save user ID to session so we know who is resetting password
        session(['reset_password_user_id' => $user->id]);

        // Send OTP via WA Fonnte
        // Use user's tenant token, or fallback to superadmin token (tenant_id = 1)
        $token = Setting::withoutGlobalScopes()->where('tenant_id', $user->tenant_id)->where('key', 'fonnte_token')->value('value');
        if (empty($token)) {
            $token = Setting::withoutGlobalScopes()->where('tenant_id', 1)->where('key', 'fonnte_token')->value('value');
        }

        if (empty($token)) {
            return back()->withErrors(['phone' => 'Sistem tidak dapat mengirim OTP karena Token WA Gateway belum dikonfigurasi.']);
        }

        $message = "Halo {$user->name},\n\nAnda meminta reset kata sandi untuk akun Billing RT/RW Anda.\n\nKode OTP Anda adalah: *{$otp}*\n\nKode ini berlaku selama 5 menit. Jangan berikan kode ini kepada siapa pun.";

        try {
            $response = Http::withHeaders([
                'Authorization' => $token
            ])->post('https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                return redirect()->route('password.verify')->with('success', 'Kode OTP telah dikirim ke nomor WhatsApp Anda.');
            } else {
                return back()->withErrors(['phone' => 'Gagal mengirim OTP via WhatsApp.'])->withInput();
            }
        } catch (\Exception $e) {
            return back()->withErrors(['phone' => 'Gagal terhubung ke server WhatsApp Gateway.'])->withInput();
        }
    }

    public function showVerifyOtpForm()
    {
        if (!session()->has('reset_password_user_id')) {
            return redirect()->route('password.request');
        }
        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:6',
        ]);

        $userId = session('reset_password_user_id');
        if (!$userId) {
            return redirect()->route('password.request')->withErrors(['phone' => 'Sesi kedaluwarsa. Silakan ulangi dari awal.']);
        }

        $cachedOtp = Cache::get('password_reset_otp_' . $userId);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
        }

        // OTP valid, allow user to proceed to reset password
        session(['otp_verified' => true]);
        
        return redirect()->route('password.reset');
    }

    public function showResetForm()
    {
        if (!session('otp_verified') || !session('reset_password_user_id')) {
            return redirect()->route('password.request');
        }
        
        return view('auth.reset-password');
    }

    public function reset(Request $request)
    {
        if (!session('otp_verified') || !session('reset_password_user_id')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::withoutGlobalScopes()->find(session('reset_password_user_id'));
        
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // Clear session and cache
        Cache::forget('password_reset_otp_' . $user->id);
        session()->forget(['reset_password_user_id', 'otp_verified']);

        return redirect()->route('login')->with('info', 'Kata sandi berhasil direset. Silakan login dengan kata sandi baru Anda.');
    }
}
