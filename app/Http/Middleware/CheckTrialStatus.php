<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckTrialStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Lewati pengecekan jika user adalah superadmin (tidak terikat tenant spesifik atau pemilik SaaS utama)
        if ($user && $user->role === 'superadmin') {
            return $next($request);
        }

        if ($user && $user->tenant) {
            $tenant = $user->tenant;
            
            // Jika masa trial habis dan status masih trial
            if ($tenant->subscription_status === 'trial' && $tenant->trial_ends_at && Carbon::now()->greaterThan($tenant->trial_ends_at)) {
                // Ubah status jadi expired
                $tenant->update(['subscription_status' => 'expired']);
            }
            
            // Jika status expired atau suspended, kunci akses ke dashboard
            if (in_array($tenant->subscription_status, ['expired', 'suspended'])) {
                // Hindari redirect loop jika sudah di halaman locked atau logout
                if (!$request->is('subscription/locked') && !$request->is('logout') && !$request->is('payment/*')) {
                    return redirect()->route('subscription.locked');
                }
            }
        }

        return $next($request);
    }
}
