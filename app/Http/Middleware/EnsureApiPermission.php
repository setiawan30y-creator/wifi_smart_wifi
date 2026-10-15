<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureApiPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user || !$user->hasPermission($permission)) {
            return ApiResponse::error('Anda tidak memiliki akses ke fitur ini.', 403, 'FORBIDDEN');
        }

        return $next($request);
    }
}
