<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppGateway;
use App\Support\Phone;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Reset kata sandi lewat OTP WhatsApp.
 *
 * Pengamanan:
 *  - Pesan selalu sama, baik nomor terdaftar maupun tidak (tidak membocorkan akun).
 *  - OTP 6 digit dari random_int(), disimpan dalam bentuk hash, berlaku 5 menit.
 *  - Maksimal 5 kali salah memasukkan OTP; setelah itu OTP hangus.
 *  - Route dibatasi jumlah percobaannya (throttle) di routes/web.php.
 *  - WA dikirim memakai gateway milik tenant user (atau gateway SaaS), bukan tenant lain.
 */
class ForgotPasswordController extends Controller
{
    private const OTP_TTL_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;

    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:30',
        ]);

        $phone = Phone::normalize($request->phone);
        $user = $phone ? $this->findUserByPhone($phone) : null;

        session()->forget(['reset_password_user_id', 'otp_verified']);

        if ($user) {
            $otp = (string) random_int(100000, 999999);

            Cache::put($this->cacheKey($user->id), [
                'hash' => Hash::make($otp),
                'attempts' => 0,
            ], now()->addMinutes(self::OTP_TTL_MINUTES));

            session(['reset_password_user_id' => $user->id]);

            $message = "Halo {$user->name},\n\nAnda meminta reset kata sandi akun Anda.\n\n"
                . "Kode OTP Anda: *{$otp}*\n\n"
                . "Kode berlaku " . self::OTP_TTL_MINUTES . " menit. Jangan berikan kode ini kepada siapa pun.";

            $result = app(TenantContext::class)->run((int) $user->tenant_id, function () use ($phone, $message) {
                return app(WhatsAppGateway::class)->send($phone, $message);
            });

            if (!($result['success'] ?? false)) {
                Log::warning('OTP reset password gagal dikirim.', [
                    'user_id' => $user->id,
                    'reason' => $result['message'] ?? null,
                ]);
            }
        }

        return redirect()->route('password.verify')
            ->with('success', 'Jika nomor tersebut terdaftar, kode OTP telah dikirim ke WhatsApp.');
    }

    public function showVerifyOtpForm()
    {
        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $userId = session('reset_password_user_id');
        $entry = $userId ? Cache::get($this->cacheKey($userId)) : null;

        if (!$entry) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa. Silakan minta kode baru.']);
        }

        if (!Hash::check((string) $request->otp, $entry['hash'])) {
            $entry['attempts']++;

            if ($entry['attempts'] >= self::MAX_ATTEMPTS) {
                Cache::forget($this->cacheKey($userId));
                session()->forget('reset_password_user_id');

                return redirect()->route('password.request')
                    ->withErrors(['phone' => 'Terlalu banyak percobaan. Silakan minta kode OTP baru.']);
            }

            Cache::put($this->cacheKey($userId), $entry, now()->addMinutes(self::OTP_TTL_MINUTES));

            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
        }

        Cache::forget($this->cacheKey($userId));
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

        session()->forget(['reset_password_user_id', 'otp_verified']);

        if (!$user) {
            return redirect()->route('password.request');
        }

        $user->password = Hash::make($request->password);
        $user->setRememberToken(null);
        $user->save();

        session()->regenerate();

        return redirect()->route('login')->with('info', 'Kata sandi berhasil direset. Silakan login dengan kata sandi baru Anda.');
    }

    private function findUserByPhone(string $normalizedPhone): ?User
    {
        $variants = array_unique([
            $normalizedPhone,
            '0' . substr($normalizedPhone, 2),
            '+' . $normalizedPhone,
        ]);

        return User::withoutGlobalScopes()->whereIn('phone', $variants)->orderBy('id')->first();
    }

    private function cacheKey(int $userId): string
    {
        return 'password_reset_otp_' . $userId;
    }
}
