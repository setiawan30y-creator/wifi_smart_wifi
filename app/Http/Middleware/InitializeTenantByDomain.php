<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;

class InitializeTenantByDomain
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $context = app(TenantContext::class);
        $host = $request->getHost();
        $baseHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'billing_rtrw.test';

        // Jika mengakses domain pusat
        if ($host === $baseHost) {
            // Domain pusat: tenant mengikuti user yang login (null jika belum login).
            $context->forget();
            $context->set(auth()->user()?->tenant_id ? (int) auth()->user()->tenant_id : null);

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

        $context->set($tenant->id);

        return $next($request);
    }
}
