<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle the login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            
            // Cek kompabilitas domain: Jangan izinkan user dari tenant A login di domain tenant B
            $domainTenantId = Session::get('tenant_id');
            if ($domainTenantId && $user->tenant_id !== $domainTenantId && !$user->is_saas_admin) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akses ditolak: Akun Anda tidak terdaftar pada perusahaan di alamat ini.',
                ])->onlyInput('email');
            }

            // Periksa status langganan Tenant
            if (!$user->is_saas_admin && $user->tenant && $user->tenant->status !== 'active') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akses ditolak: Perusahaan Anda sedang dinonaktifkan (Suspended).',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            
            // Simpan tenant_id ke session
            if ($user->tenant_id) {
                $request->session()->put('tenant_id', $user->tenant_id);
            }

            // Redirect SaaS Admin ke rute khusus
            if ($user->isSaasOwner()) {
                return redirect()->intended('/saas/dashboard')
                    ->with('success', 'Selamat datang kembali, Global Administrator!');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }
}
