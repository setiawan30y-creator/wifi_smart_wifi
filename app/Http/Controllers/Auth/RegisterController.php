<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create Tenant (RTRW Net) with 14 days free trial
            $tenant = Tenant::create([
                'name' => $request->company_name,
                'status' => 'active',
                'subscription_status' => 'trial',
                'trial_ends_at' => Carbon::now()->addDays(14),
            ]);

            // 2. Create User (Owner/Admin)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'admin',
                'tenant_id' => $tenant->id,
            ]);

            DB::commit();

            // 3. Auto Login
            Auth::login($user);

            // 4. Redirect to dashboard
            return redirect()->route('dashboard')->with('success', 'Pendaftaran Berhasil! Selamat datang di Ruang Kerja Anda. Nikmati Masa Uji Coba Gratis 14 Hari.');

        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Pendaftaran tenant gagal.', ['error' => $e->getMessage()]);

            return back()->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'Pendaftaran gagal diproses. Silakan coba lagi beberapa saat lagi.');
        }
    }
}
