<?php

namespace App\Support\Tenancy;

/**
 * Menyimpan tenant (perusahaan) yang sedang aktif untuk request / proses saat ini.
 *
 * Sumber nilai:
 *  - Middleware InitializeTenantByDomain (domain/subdomain tenant).
 *  - Event Login/Authenticated (tenant milik user yang login).
 *  - Proses tanpa login (console, webhook) memakai run($tenantId, fn () => ...).
 *
 * Model yang memakai trait BelongsToTenant otomatis difilter berdasarkan nilai ini.
 */
class TenantContext
{
    private ?int $tenantId = null;

    public function id(): ?int
    {
        return $this->tenantId;
    }

    public function has(): bool
    {
        return $this->tenantId !== null;
    }

    public function set(?int $tenantId): void
    {
        $this->tenantId = $tenantId ?: null;

        // Kompatibilitas: sebagian kode lama masih membaca session('tenant_id').
        if (app()->bound('session.store') && app('session.store')->isStarted()) {
            if ($this->tenantId) {
                app('session.store')->put('tenant_id', $this->tenantId);
            } else {
                app('session.store')->forget('tenant_id');
            }
        }
    }

    public function forget(): void
    {
        $this->set(null);
    }

    /**
     * Jalankan callback dengan tenant tertentu, lalu kembalikan tenant sebelumnya.
     * Dipakai oleh command console dan webhook yang tidak punya user login.
     */
    public function run(int $tenantId, callable $callback): mixed
    {
        $previous = $this->tenantId;
        $this->tenantId = $tenantId;

        try {
            return $callback();
        } finally {
            $this->tenantId = $previous;
        }
    }
}
