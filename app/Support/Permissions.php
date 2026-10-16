<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

/**
 * Daftar modul hak akses dan aturan bawaannya, di SATU tempat.
 *
 * Dipakai oleh: Gate (AppServiceProvider), User::hasPermission(),
 * halaman Pengaturan → Hak Akses, dan middleware API.
 *
 * Pengaturan per tenant disimpan di setting "permissions_map" milik tenant itu
 * (hanya role admin & tehnisi yang bisa diubah). Role superadmin selalu boleh semua.
 */
final class Permissions
{
    /** Role yang matriks izinnya bisa diubah dari halaman Pengaturan. */
    public const EDITABLE_ROLES = ['admin', 'tehnisi'];

    /** key => [label, grup] */
    public const MODULES = [
        'dashboard' => ['Dashboard', 'dashboard'],
        'dashboard_card_pppoe_active' => ['Dashboard: Kartu PPPoE Aktif', 'dashboard'],
        'dashboard_card_pppoe_offline' => ['Dashboard: Kartu PPPoE Offline', 'dashboard'],
        'dashboard_card_odp_offline' => ['Dashboard: Kartu ODP Offline', 'dashboard'],
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
        'invoices' => ['Tagihan & Invoice', 'billing'],
        'pppoe' => ['PPPoE / Secret MikroTik', 'network'],
        'routers' => ['Router MikroTik', 'network'],
        'odps' => ['Manajemen ODP', 'network'],
        'olts' => ['Manajemen OLT', 'network'],
        'network_map' => ['Peta Jaringan', 'network'],
        'device_stocks' => ['Stok Perangkat', 'inventory'],
        'expenses' => ['Pengeluaran Kas', 'finance'],
        'financial_reports' => ['Laporan Keuangan', 'finance'],
        'wa_templates' => ['Template WA', 'settings'],
        'tickets' => ['Tiket Bantuan', 'support'],
        'wallet' => ['Wallet Pelanggan', 'wallet'],
        'ppob' => ['PPOB', 'ppob'],
        'settings' => ['Pengaturan', 'settings'],
    ];

    /** Modul yang hanya boleh dibuka role selain pelanggan (client), apa pun pengaturannya. */
    public const STAFF_ONLY = [
        'customers', 'installation_requests', 'packages', 'pppoe', 'routers', 'odps', 'olts',
        'network_map', 'device_stocks', 'expenses', 'financial_reports', 'wa_templates', 'settings',
    ];

    /** Modul yang tidak boleh diberikan ke teknisi lewat pengaturan. */
    public const NEVER_FOR_TECHNICIAN = ['settings'];

    /** Cache matriks per tenant selama satu request. */
    private static array $cache = [];

    public static function flush(): void
    {
        self::$cache = [];
    }

    public static function modules(): array
    {
        return array_keys(self::MODULES);
    }

    public static function labels(): array
    {
        return array_map(fn (array $meta) => $meta[0], self::MODULES);
    }

    /**
     * Izin bawaan per role (dipakai jika tenant belum menyimpan pengaturan).
     */
    public static function defaults(): array
    {
        $all = array_fill_keys(self::modules(), true);
        $none = array_fill_keys(self::modules(), false);

        $tehnisi = array_merge($none, [
            'dashboard' => true,
            'customers' => true,
            'installation_requests' => true,
            'pppoe' => true,
            'odps' => true,
            'olts' => true,
            'network_map' => true,
            'device_stocks' => true,
            'tickets' => true,
        ]);

        $client = array_merge($none, [
            'dashboard' => true,
            'invoices' => true,
            'tickets' => true,
            'wallet' => true,
            'ppob' => true,
        ]);

        foreach (self::modules() as $module) {
            if (str_starts_with($module, 'dashboard_card_')) {
                $tehnisi[$module] = true;
            }
        }

        return [
            'admin' => $all,
            'tehnisi' => $tehnisi,
            'client' => $client,
        ];
    }

    /**
     * Matriks izin tenant aktif = bawaan + pengaturan yang disimpan tenant.
     */
    public static function matrix(): array
    {
        $key = (string) (app(\App\Support\Tenancy\TenantContext::class)->id() ?? 'none');

        return self::$cache[$key] ??= (function () {
            $stored = Setting::get('permissions_map');
            $stored = is_string($stored) ? (json_decode($stored, true) ?: []) : [];

            return self::normalize($stored);
        })();
    }

    /**
     * Gabungkan pengaturan tersimpan dengan bawaan dan terapkan batasan tetap.
     */
    public static function normalize(array $stored): array
    {
        $matrix = self::defaults();

        foreach (self::EDITABLE_ROLES as $role) {
            foreach (self::modules() as $module) {
                if (array_key_exists($module, $stored[$role] ?? [])) {
                    $matrix[$role][$module] = (bool) $stored[$role][$module];
                }
            }
        }

        foreach (self::NEVER_FOR_TECHNICIAN as $module) {
            $matrix['tehnisi'][$module] = false;
        }

        foreach (self::STAFF_ONLY as $module) {
            $matrix['client'][$module] = false;
        }

        return $matrix;
    }

    public static function allows(User $user, string $module): bool
    {
        if ($user->role === 'superadmin') {
            return true;
        }

        if (!array_key_exists($module, self::MODULES)) {
            return false;
        }

        return (bool) (self::matrix()[$user->role][$module] ?? false);
    }
}
