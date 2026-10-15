<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SaasOwnerSeeder extends Seeder
{
    public function run(): void
    {
        User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'admin@rtrw.net'],
            [
                'tenant_id' => 1,
                'name' => 'Pemilik SaaS',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
                'is_saas_admin' => true,
            ]
        );
    }
}
