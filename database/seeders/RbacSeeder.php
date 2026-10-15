<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard' => ['Dashboard', 'dashboard'],
            'dashboard_card_pppoe_active' => ['Dashboard: Kartu PPPoE Aktif', 'dashboard'],
            'dashboard_card_pppoe_offline' => ['Dashboard: Kartu PPPoE Offline', 'dashboard'],
            'dashboard_card_pppoe_disabled' => ['Dashboard: Kartu PPPoE Disable', 'dashboard'],
            'dashboard_card_pppoe_isolated' => ['Dashboard: Kartu PPPoE Isolir', 'dashboard'],
            'dashboard_card_traffic_live' => ['Dashboard: Trafik Jaringan Live', 'dashboard'],
            'dashboard_card_active_customers' => ['Dashboard: Kartu Pelanggan Aktif', 'dashboard'],
            'dashboard_card_monthly_target' => ['Dashboard: Kartu Target Bulanan', 'dashboard'],
            'dashboard_card_collected_revenue' => ['Dashboard: Kartu Dana Terkumpul', 'dashboard'],
            'dashboard_card_unpaid_amount' => ['Dashboard: Kartu Tertunggak', 'dashboard'],
            'dashboard_card_collection_progress' => ['Dashboard: Progress Penagihan', 'dashboard'],
            'dashboard_card_invoice_generator' => ['Dashboard: Pembuatan Tagihan', 'dashboard'],
            'dashboard_card_recent_payments' => ['Dashboard: Riwayat Pembayaran', 'dashboard'],
            'customers' => ['Pelanggan', 'billing'],
            'installation_requests' => ['Calon Pasang', 'billing'],
            'packages' => ['Paket Layanan', 'billing'],
            'invoices' => ['Tagihan', 'billing'],
            'odps' => ['ODP', 'network'],
            'olts' => ['OLT', 'network'],
            'network_map' => ['Peta Jaringan', 'network'],
            'device_stocks' => ['Stok Perangkat', 'inventory'],
            'expenses' => ['Pengeluaran', 'finance'],
            'financial_reports' => ['Laporan Keuangan', 'finance'],
            'wa_templates' => ['Template WhatsApp', 'settings'],
            'settings' => ['Pengaturan', 'settings'],
            'tickets' => ['Tiket Bantuan', 'support'],
            'wallet' => ['Wallet', 'wallet'],
            'ppob' => ['PPOB', 'ppob'],
        ];

        $permissionModels = collect($permissions)->mapWithKeys(function ($meta, $name) {
            return [
                $name => Permission::updateOrCreate(
                    ['name' => $name],
                    ['label' => $meta[0], 'group' => $meta[1]]
                ),
            ];
        });

        $superadmin = Role::firstOrCreate(['tenant_id' => null, 'name' => 'superadmin'], ['label' => 'Super Admin', 'is_system' => true]);
        $admin = Role::firstOrCreate(['tenant_id' => null, 'name' => 'admin'], ['label' => 'Admin', 'is_system' => true]);
        $tehnisi = Role::firstOrCreate(['tenant_id' => null, 'name' => 'tehnisi'], ['label' => 'Teknisi', 'is_system' => true]);
        $client = Role::firstOrCreate(['tenant_id' => null, 'name' => 'client'], ['label' => 'Pelanggan', 'is_system' => true]);

        $superadmin->permissions()->sync($permissionModels->pluck('id'));
        $admin->permissions()->sync($permissionModels->pluck('id'));
        $tehnisi->permissions()->sync($permissionModels->only([
            'dashboard',
            'dashboard_card_pppoe_active',
            'dashboard_card_pppoe_offline',
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
            'installation_requests',
            'odps',
            'olts',
            'network_map',
            'device_stocks',
            'tickets',
        ])->pluck('id'));
        $client->permissions()->sync($permissionModels->only(['tickets', 'wallet', 'ppob', 'invoices'])->pluck('id'));

        User::query()->get()->each(function (User $user) use ($superadmin, $admin, $tehnisi, $client) {
            $role = match ($user->role) {
                'superadmin' => $superadmin,
                'tehnisi' => $tehnisi,
                'client' => $client,
                default => $admin,
            };

            $user->roles()->syncWithoutDetaching([$role->id]);
        });
    }
}
