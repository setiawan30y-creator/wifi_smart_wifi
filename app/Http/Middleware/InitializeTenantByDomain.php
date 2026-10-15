<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class InitializeTenantByDomain
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $baseHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'billing_rtrw.test';

        // Jika mengakses domain pusat
        if ($host === $baseHost) {
            // Hapus session tenant jika ada
            Session::forget('tenant_id');
            return $next($request);
        }

        // Cari tenant berdasarkan host (domain/subdomain)
        $tenant = Tenant::where('domain', $host)->first();

        if (!$tenant) {
            // Jika tenant tidak ditemukan, redirect ke domain utama
            return redirect(config('app.url'))->with('error', 'Perusahaan tidak ditemukan.');
        }

        if ($tenant->status !== 'active') {
            return response('Perusahaan Anda sedang dinonaktifkan.', 403);
        }

        // Set session tenant
        Session::put('tenant_id', $tenant->id);

        return $next($request);
    }
}
