<?php

namespace App\Providers;

use App\Support\Permissions;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tenant aktif mengikuti user yang login.
        $setTenantFromUser = function ($event) {
            if ($event->user && $event->user->tenant_id) {
                app(TenantContext::class)->set((int) $event->user->tenant_id);
            }
        };
        Event::listen(Authenticated::class, $setTenantFromUser);
        Event::listen(Login::class, $setTenantFromUser);
        Event::listen(Logout::class, fn () => app(TenantContext::class)->forget());

        if ($this->shouldForceHttpsScheme()) {
            URL::forceScheme('https');
        }

        // Satu Gate per modul hak akses (daftar modul ada di App\Support\Permissions).
        foreach (Permissions::modules() as $module) {
            Gate::define($module, fn ($user) => $user->hasPermission($module));
        }

        // Kirim WA dipakai dari halaman Pelanggan maupun Invoice.
        // Ekstrak koordinat Google Maps dipakai form Pelanggan, Calon Pasang, dan ODP.
        Gate::define('coordinates', fn ($user) => $user->hasPermission('customers')
            || $user->hasPermission('installation_requests')
            || $user->hasPermission('odps'));

        // Pelanggan (role client) tidak boleh memakai gateway WA perusahaan.
        Gate::define('whatsapp', fn ($user) => $user->role !== 'client'
            && ($user->hasPermission('customers') || $user->hasPermission('invoices')));
    }

    private function shouldForceHttpsScheme(): bool
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            return true;
        }

        if (request()->isSecure()) {
            return true;
        }

        return strtolower((string) request()->server('HTTP_X_FORWARDED_PROTO')) === 'https';
    }
}
