<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Traits\BelongsToTenant;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['tenant_id', 'name', 'email', 'password', 'role', 'is_saas_admin', 'photo', 'phone', 'address'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use BelongsToTenant;

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'role' => 'admin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_saas_admin' => 'boolean',
        ];
    }

    public function odps()
    {
        return $this->hasMany(Odp::class, 'tenant_id', 'tenant_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketReplies()
    {
        return $this->hasMany(TicketReply::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function isSaasOwner(): bool
    {
        return (bool) $this->is_saas_admin
            || (
                $this->role === 'superadmin'
                && (int) $this->tenant_id === 1
                && strtolower((string) $this->email) === 'admin@rtrw.net'
            );
    }

    /**
     * Check if user has permission to access a specific module.
     */
    public function hasPermission(string $module): bool
    {
        if ($this->role === 'superadmin') {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            $hasDatabasePermission = $this->roles
                ->flatMap(fn ($role) => $role->permissions)
                ->contains('name', $module);

            if ($hasDatabasePermission) {
                return true;
            }
        } else {
            $hasDatabasePermission = $this->roles()
                ->whereHas('permissions', fn ($query) => $query->where('name', $module))
                ->exists();

            if ($hasDatabasePermission) {
                return true;
            }
        }

        // Get permissions map from settings
        $permissionsJson = Setting::get('permissions_map');
        $permissions = $permissionsJson ? json_decode($permissionsJson, true) : [];
        
        // Default fallbacks if settings aren't seeded yet
        if (empty($permissions)) {
            $permissions = [
                'admin' => [
                    'dashboard' => true,
                    'customers' => true,
                    'installation_requests' => true,
                    'packages' => true,
                    'invoices' => true,
                    'odps' => true,
                    'olts' => true,
                    'network_map' => true,
                    'device_stocks' => true,
                    'expenses' => true,
                    'financial_reports' => true,
                    'wa_templates' => true,
                    'tickets' => true,
                    'wallet' => true,
                    'ppob' => true,
                    'settings' => true
                ],
                'tehnisi' => [
                    'dashboard' => true,
                    'customers' => true,
                    'installation_requests' => true,
                    'packages' => false,
                    'invoices' => false,
                    'odps' => true,
                    'olts' => true,
                    'network_map' => true,
                    'device_stocks' => true,
                    'expenses' => false,
                    'financial_reports' => false,
                    'wa_templates' => false,
                    'tickets' => true,
                    'wallet' => false,
                    'ppob' => false,
                    'settings' => false
                ],
                'client' => [
                    'dashboard' => true,
                    'customers' => false,
                    'installation_requests' => false,
                    'packages' => false,
                    'invoices' => true,
                    'odps' => false,
                    'olts' => false,
                    'network_map' => false,
                    'device_stocks' => false,
                    'expenses' => false,
                    'financial_reports' => false,
                    'wa_templates' => false,
                    'tickets' => true,
                    'wallet' => true,
                    'ppob' => true,
                    'settings' => false
                ]
            ];
        }

        $dashboardCardPermissions = [
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
        ];

        foreach ($dashboardCardPermissions as $dashboardCardPermission) {
            $permissions['admin'][$dashboardCardPermission] ??= true;
            $permissions['tehnisi'][$dashboardCardPermission] ??= true;
            $permissions['client'][$dashboardCardPermission] ??= false;
        }

        return !empty($permissions[$this->role][$module]);
    }
}
