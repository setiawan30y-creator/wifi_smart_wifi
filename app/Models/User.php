<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
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
     * Apakah user boleh membuka modul tertentu (lihat App\Support\Permissions).
     */
    public function hasPermission(string $module): bool
    {
        return \App\Support\Permissions::allows($this, $module);
    }
}
