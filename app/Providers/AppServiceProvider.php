<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
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

        // Define dynamic gates based on modules
        $modules = [
            'dashboard',
            'dashboard_card_pppoe_active',
            'dashboard_card_pppoe_offline',
            'dashboard_card_odp_offline',
            'dashboard_card_pppoe_disabled',
            'dashboard_card_pppoe_isolated',
            'dashboard_card_traffic_live',
            'dashboard_card_active_customers',
            'dashboard_card_monthly_target',
            'dashboard_card_collected_revenue',
            'dashboard_card_unpaid_amount',
            'dashboard_card_collection_progress',
            'dashboard_card_invoice_generator',
            'dashboard_card_recent_payments',
            'customers',
            'packages',
            'invoices',
            'odps',
            'olts',
            'network_map',
            'device_stocks',
            'expenses',
            'financial_reports',
            'wa_templates',
            'tickets',
            'wallet',
            'ppob',
            'settings'
        ];

        foreach ($modules as $module) {
            \Illuminate\Support\Facades\Gate::define($module, function ($user) use ($module) {
                return $user->hasPermission($module);
            });
        }
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
