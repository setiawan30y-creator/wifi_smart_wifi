<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!empty($model->tenant_id)) {
                return;
            }

            $tenantId = app(TenantContext::class)->id();

            if ($tenantId) {
                $model->tenant_id = $tenantId;
                return;
            }

            if (!app()->runningInConsole()) {
                throw new LogicException('Tidak bisa menyimpan ' . class_basename($model) . ' tanpa tenant aktif.');
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Query untuk tenant tertentu tanpa bergantung pada tenant yang sedang aktif.
     */
    public static function forTenant(int $tenantId): Builder
    {
        return static::withoutGlobalScope(TenantScope::class)
            ->where((new static)->qualifyColumn('tenant_id'), $tenantId);
    }
}
