<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

/**
 * Untuk request API (token Sanctum): tenant aktif diambil dari user pemilik token.
 * Request web sudah ditangani InitializeTenantByDomain + event Authenticated.
 */
class SetTenantFromUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            app(TenantContext::class)->set((int) $user->tenant_id);
        }

        return $next($request);
    }
}
