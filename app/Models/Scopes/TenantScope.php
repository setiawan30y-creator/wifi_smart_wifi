<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Session;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // 1. Try to get tenant_id from Session first
        $tenantId = Session::get('tenant_id');

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
            return;
        }

        // 2. Allow User model to bypass to prevent infinite recursion during Auth::user()
        if ($model instanceof \App\Models\User || $model instanceof \App\Models\Tenant) {
            return;
        }

        // 3. Fallback to Auth::user() if session is lost (e.g., Remember Me cookie)
        // Since User model bypasses scope above, this will not recurse.
        if (auth()->check()) {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            // Optionally save it back to session for subsequent queries
            Session::put('tenant_id', $tenantId);

            $builder->where($model->getTable() . '.tenant_id', $tenantId);
            return;
        }

        // 4. Fail safe: If no session and not logged in, block access to tenant-bound models
        if (!app()->runningInConsole()) {
            $builder->where($model->getTable() . '.tenant_id', -1);
        }
    }
}
