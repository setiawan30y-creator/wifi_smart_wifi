<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Olt;
use App\Models\Router;
use Illuminate\Support\Carbon;

class OltMonitoringService
{
    public function syncCustomersAsOnus(Olt $olt): array
    {
        $customers = Customer::with('package')
            ->whereNotNull('pppoe_username')
            ->where('pppoe_username', '!=', '')
            ->get();

        $created = 0;
        $updated = 0;

        foreach ($customers as $customer) {
            $onu = $olt->onus()->firstOrNew([
                'pppoe_username' => $customer->pppoe_username,
            ]);

            $exists = $onu->exists;
            $onu->fill([
                'customer_id' => $customer->id,
                'name' => $onu->name ?: $customer->name,
                'pppoe_username' => $customer->pppoe_username,
                'pppoe_status' => $customer->status === 'active' ? ($onu->pppoe_status ?: 'unknown') : 'disabled',
                'notes' => $onu->notes ?: 'Sinkron dari data pelanggan.',
            ]);
            $onu->save();

            $exists ? $updated++ : $created++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => $customers->count(),
        ];
    }

    public function syncPppoeStatus(Olt $olt): array
    {
        $onus = $olt->onus()
            ->whereNotNull('pppoe_username')
            ->where('pppoe_username', '!=', '')
            ->get();

        if ($onus->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Belum ada ONU dengan username PPPoE untuk dicek.',
                'active' => 0,
                'offline' => 0,
            ];
        }

        $router = Router::active();
        if (!$router) {
            return [
                'success' => false,
                'message' => 'Tidak ada router MikroTik berstatus online.',
                'active' => 0,
                'offline' => 0,
            ];
        }

        $service = new MikrotikService($router);
        $activeConnections = $service->getActiveConnections($onus->pluck('pppoe_username')->filter()->values()->all());
        $activeMap = [];

        foreach ($activeConnections as $connection) {
            if (!empty($connection['name'])) {
                $activeMap[trim($connection['name'])] = $connection;
            }
        }

        $now = Carbon::now();
        $active = 0;
        $offline = 0;

        foreach ($onus as $onu) {
            $username = trim((string) $onu->pppoe_username);
            $isActive = isset($activeMap[$username]);
            $connection = $activeMap[$username] ?? [];

            $onu->forceFill([
                'pppoe_status' => $isActive ? 'active' : ($onu->customer && $onu->customer->status !== 'active' ? 'disabled' : 'offline'),
                'status' => $isActive && $onu->status === 'unknown' ? 'online' : $onu->status,
                'uptime' => $connection['uptime'] ?? $onu->uptime,
                'last_seen_at' => $isActive ? $now : $onu->last_seen_at,
                'last_polled_at' => $now,
                'last_error' => null,
            ])->save();

            $isActive ? $active++ : $offline++;
        }

        return [
            'success' => true,
            'message' => "Status PPPoE diperbarui. Aktif: {$active}, offline/disabled: {$offline}.",
            'active' => $active,
            'offline' => $offline,
        ];
    }
}
