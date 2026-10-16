<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Odp;
use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Active Customers Count
        $activeCustomers = Customer::where('status', 'active')->count();
        $totalCustomers = Customer::count();

        // Calculate expected monthly revenue based on active customers' package prices
        $expectedRevenue = Customer::where('status', 'active')
            ->with('package')
            ->get()
            ->sum(function ($customer) {
                return $customer->package ? $customer->package->price : 0;
            });

        // Actual collected revenue for the current month
        $collectedRevenue = Invoice::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('status', 'paid')
            ->sum('amount');

        // Unpaid invoice count for current month
        $unpaidCount = Invoice::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('status', 'unpaid')
            ->count();

        // Target unpaid amount
        $unpaidAmount = Invoice::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('status', 'unpaid')
            ->sum('amount');

        // Collection progress percentage
        $collectionRate = $expectedRevenue > 0 ? round(($collectedRevenue / $expectedRevenue) * 100) : 0;

        // Recent paid invoices
        $recentPayments = Invoice::with(['customer.package'])
            ->where('status', 'paid')
            ->orderBy('paid_at', 'desc')
            ->take(5)
            ->get();

        $paymentMethodStats = Invoice::query()
            ->where('status', 'paid')
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->get()
            ->groupBy(function ($invoice) {
                return strtolower(trim((string) $invoice->payment_method));
            });

        $paymentTotals = collect([
            'cash' => ['label' => 'Cash', 'count' => 0, 'amount' => 0],
            'qris' => ['label' => 'QRIS', 'count' => 0, 'amount' => 0],
            'transfer' => ['label' => 'Transfer', 'count' => 0, 'amount' => 0],
        ])->map(function ($item, $key) use ($paymentMethodStats) {
            $aliases = match ($key) {
                'cash' => ['cash', 'tunai'],
                'qris' => ['qris', 'qr'],
                'transfer' => ['transfer', 'bank', 'tf'],
                default => [$key],
            };

            $rows = collect($aliases)->flatMap(fn ($alias) => $paymentMethodStats->get($alias, collect()));
            $item['count'] = $rows->count();
            $item['amount'] = $rows->sum('amount');

            return $item;
        })->all();

        // Fetch MikroTik PPPoE Stats
        $pppoeCustomers = Customer::with(['package', 'odp'])->whereNotNull('pppoe_username')->get();
        $mikrotikStats = [
            'total' => $pppoeCustomers->count(),
            'disabled' => $pppoeCustomers->where('status', 'inactive')->count(),
            'isolir' => $pppoeCustomers->filter(function($c) {
                return $c->package && strtolower($c->package->name) === 'isolir';
            })->count(),
            'active' => 0,
            'offline' => 0,
        ];
        $odpOfflineStats = [
            'count' => 0,
            'total' => Odp::has('customers')->count(),
            'names' => [],
            'checked' => false,
        ];

        $router = Router::active();
        if ($router && $mikrotikStats['total'] > 0) {
            $mikrotikService = new MikrotikService($router);
            $usernames = $pppoeCustomers->where('status', 'active')->pluck('pppoe_username')->filter()->values()->toArray();
            try {
                $activeConnections = $mikrotikService->getActiveConnections($usernames);
                $uniqueActive = [];
                foreach ($activeConnections as $conn) {
                    if (isset($conn['name'])) {
                        $uniqueActive[trim($conn['name'])] = true;
                    }
                }
                $mikrotikStats['active'] = count($uniqueActive);
                $odpOfflineStats['checked'] = true;

                $offlineOdps = $pppoeCustomers
                    ->where('status', 'active')
                    ->whereNotNull('odp_id')
                    ->groupBy('odp_id')
                    ->filter(function ($customers) use ($uniqueActive) {
                        return $customers->count() > 0
                            && $customers->every(function ($customer) use ($uniqueActive) {
                                return empty($uniqueActive[trim((string) $customer->pppoe_username)]);
                            });
                    });

                $odpOfflineStats['count'] = $offlineOdps->count();
                $odpOfflineStats['names'] = $offlineOdps
                    ->map(fn ($customers) => optional($customers->first()->odp)->name)
                    ->filter()
                    ->values()
                    ->take(5)
                    ->all();
            } catch (\Exception $e) {
                \Log::error('Dashboard PPPoE Stats Error: ' . $e->getMessage());
            }
        }
        
        $mikrotikStats['offline'] = max(0, $mikrotikStats['total'] - $mikrotikStats['active'] - $mikrotikStats['disabled']);

        // Dashboard Interfaces
        $dashboardInterfaces = explode(',', \App\Models\Setting::get('dashboard_interfaces', ''));
        // Clean empty values
        $dashboardInterfaces = array_filter($dashboardInterfaces);

        return view('dashboard', compact(
            'activeCustomers',
            'totalCustomers',
            'expectedRevenue',
            'collectedRevenue',
            'unpaidCount',
            'unpaidAmount',
            'collectionRate',
            'recentPayments',
            'paymentTotals',
            'currentMonth',
            'currentYear',
            'mikrotikStats',
            'odpOfflineStats',
            'router',
            'dashboardInterfaces'
        ));
    }

    /**
     * Get live traffic via API
     */
    public function traffic()
    {
        $interfacesStr = \App\Models\Setting::get('dashboard_interfaces', '');
        if (empty($interfacesStr)) {
            return response()->json([
                'ok' => false,
                'message' => 'Belum ada interface yang dipilih untuk Trafik Dashboard.',
                'data' => [],
            ]);
        }

        $interfaces = explode(',', $interfacesStr);
        $interfaces = array_values(array_filter(array_map('trim', $interfaces)));

        $router = Router::active() ?? Router::query()->latest('id')->first();

        if (!$router) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada router MikroTik berstatus online. Buka Manajemen Router lalu klik Test Koneksi.',
                'data' => [],
            ], 503);
        }

        $mikrotikService = new MikrotikService($router);
        $traffic = $mikrotikService->getTraffic($interfaces);
        $trafficByName = collect($traffic)->keyBy('name');
        $traffic = collect($interfaces)
            ->map(function ($interfaceName) use ($trafficByName) {
                return $trafficByName->get($interfaceName, [
                    'name' => $interfaceName,
                    'rx-bits-per-second' => 0,
                    'tx-bits-per-second' => 0,
                    'empty' => true,
                ]);
            })
            ->values()
            ->all();
        $summary = [
            'rx_bits_per_second' => collect($traffic)->sum('rx-bits-per-second'),
            'tx_bits_per_second' => collect($traffic)->sum('tx-bits-per-second'),
            'has_traffic' => collect($traffic)->sum('rx-bits-per-second') + collect($traffic)->sum('tx-bits-per-second') > 0,
            'updated_at' => now()->format('H:i:s'),
        ];

        return response()->json([
            'ok' => true,
            'router' => $router->name,
            'interfaces' => $interfaces,
            'summary' => $summary,
            'message' => collect($traffic)->where('empty', true)->count() === count($traffic)
                ? 'Belum ada trafik terbaca pada interface yang dipilih. Pastikan interface uplink/bridge yang benar dicentang.'
                : (collect($traffic)->whereNotNull('error')->isNotEmpty()
                    ? 'Sebagian interface tidak bisa dibaca. Periksa nama interface di Pengaturan Trafik Dashboard.'
                    : 'Trafik berhasil dibaca.'),
            'data' => $traffic,
        ]);
    }
}
