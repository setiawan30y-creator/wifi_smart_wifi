<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SaasAdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isSaasOwner()) {
            return $next($request);
        }

        abort(403, 'Akses ditolak. Halaman ini khusus untuk Global Administrator SaaS.');
    }
}
