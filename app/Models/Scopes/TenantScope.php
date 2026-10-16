<?php

namespace App\Models\Scopes;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
            return;
        }

        // Pencarian user saat login dilakukan sebelum tenant diketahui.
        if ($model instanceof User) {
            return;
        }

        // Command console (billing otomatis, import, dll.) memfilter tenant secara eksplisit.
        if (app()->runningInConsole()) {
            return;
        }

        // Request web tanpa tenant: tutup semua akses ke data milik tenant.
        $builder->whereRaw('1 = 0');
    }
}
