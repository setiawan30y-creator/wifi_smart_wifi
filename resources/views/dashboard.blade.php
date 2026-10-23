@extends('layouts.app')

@section('content')
<style>
    .dashboard-page {
        --dash-card-bg: color-mix(in oklab, var(--neutral-900) 74%, transparent);
        --dash-card-border: color-mix(in oklab, var(--neutral-600) 38%, transparent);
    }

    .dashboard-page > div {
        margin-bottom: 0 !important;
    }

    .dashboard-page .dashboard-section-title {
        display: flex;
        align-items: center;
        gap: .6rem;
        margin-bottom: .75rem;
    }

    .dashboard-page .dashboard-section-title > div {
        width: 1.85rem !important;
        height: 1.85rem !important;
        border-radius: .65rem !important;
    }

    .dashboard-page .dashboard-section-title h2 {
        font-size: .95rem !important;
        line-height: 1.25rem !important;
    }

    .dashboard-page .dashboard-kpi-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: .75rem !important;
    }

    .dashboard-page .dashboard-kpi-card {
        min-height: 116px;
        border: 1px solid var(--dash-card-border) !important;
        background: var(--dash-card-bg) !important;
        border-radius: .85rem !important;
        padding: 1rem !important;
        box-shadow: 0 16px 34px -30px color-mix(in oklab, var(--neutral-950) 95%, transparent) !important;
    }

    .dashboard-page .dashboard-kpi-card .absolute[class*="blur"] {
        display: none !important;
    }

    .dashboard-page .dashboard-kpi-card span[class*="text-3xl"],
    .dashboard-page .dashboard-kpi-card span[class*="text-4xl"],
    .dashboard-page .dashboard-kpi-card span[class*="md:text-3xl"],
    .dashboard-page .dashboard-kpi-card .text-2xl {
        font-size: 1.45rem !important;
        line-height: 1.85rem !important;
    }

    .dashboard-page .dashboard-kpi-card p {
        margin-top: .45rem !important;
        line-height: 1rem !important;
    }

    .dashboard-page .dashboard-panel {
        border: 1px solid var(--dash-card-border) !important;
        background: var(--dash-card-bg) !important;
        border-radius: .85rem !important;
        padding: 1rem !important;
        box-shadow: 0 16px 34px -30px color-mix(in oklab, var(--neutral-950) 95%, transparent) !important;
    }

    .dashboard-page #traffic-container {
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)) !important;
        gap: .75rem !important;
    }

    .dashboard-page #traffic-container > div {
        border-radius: .85rem !important;
        padding: 1rem !important;
    }

    .dashboard-page table th,
    .dashboard-page table td {
        padding: .55rem .7rem !important;
        font-size: 12px !important;
        line-height: 1.15rem !important;
    }

    .dashboard-page table thead tr {
        border-bottom-color: color-mix(in oklab, var(--neutral-600) 52%, transparent) !important;
    }

    @media (max-width: 767px) {
        .dashboard-page {
            gap: .9rem !important;
        }

        .dashboard-page .dashboard-kpi-grid {
            grid-template-columns: 1fr 1fr;
        }

        .dashboard-page .dashboard-kpi-card {
            min-height: 104px;
            padding: .85rem !important;
        }

        .dashboard-page .dashboard-kpi-card span[class*="text-3xl"],
        .dashboard-page .dashboard-kpi-card span[class*="text-4xl"],
        .dashboard-page .dashboard-kpi-card .text-2xl {
            font-size: 1.18rem !important;
            line-height: 1.5rem !important;
        }
    }
</style>

<div class="dashboard-page space-y-5">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Dashboard Ringkasan</h1>
            <p class="text-xs text-slate-400 mt-1">Pantau pembayaran, pelanggan, jaringan, dan keuangan bulanan RT/RW Anda.</p>
        </div>
    </div>

    @php
        $dashboardUser = auth()->user();
        $canPppoeActive = $dashboardUser?->can('dashboard_card_pppoe_active');
        $canPppoeOffline = $dashboardUser?->can('dashboard_card_pppoe_offline');
        $canOdpOffline = $dashboardUser?->can('dashboard_card_odp_offline');
        $canPppoeDisabled = $dashboardUser?->can('dashboard_card_pppoe_disabled');
        $canPppoeIsolated = $dashboardUser?->can('dashboard_card_pppoe_isolated');
        $canAnyPppoeCard = $canPppoeActive || $canPppoeOffline || $canOdpOffline || $canPppoeDisabled || $canPppoeIsolated;
        $canTrafficLive = $dashboardUser?->can('dashboard_card_traffic_live');
        $canActiveCustomersCard = $dashboardUser?->can('dashboard_card_active_customers');
        $canMonthlyTargetCard = $dashboardUser?->can('dashboard_card_monthly_target');
        $canCollectedRevenueCard = $dashboardUser?->can('dashboard_card_collected_revenue');
        $canUnpaidAmountCard = $dashboardUser?->can('dashboard_card_unpaid_amount');
        $canAnyMainStatCard = $canActiveCustomersCard || $canMonthlyTargetCard || $canCollectedRevenueCard || $canUnpaidAmountCard;
        $canCollectionProgressCard = $dashboardUser?->can('dashboard_card_collection_progress');
        $canInvoiceGeneratorCard = $dashboardUser?->can('dashboard_card_invoice_generator');
        $canRecentPaymentsCard = $dashboardUser?->can('dashboard_card_recent_payments');
    @endphp

    @if(isset($mikrotikStats) && isset($router) && $router && $canAnyPppoeCard)
    <!-- MikroTik PPPoE Stats Grid -->
    <div>
        <div class="dashboard-section-title">
            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
            </div>
            <h2 class="text-lg font-bold text-white tracking-tight">Status MikroTik: <span class="text-indigo-400">{{ $router->name }}</span></h2>
        </div>
        
        <style>
            .dashboard-compact-cards > div { padding: 1rem !important; border-radius: 1rem !important; }
            .dashboard-compact-cards .text-3xl { font-size: 1.5rem !important; line-height: 2rem !important; }
            .dashboard-compact-cards .mt-4 { margin-top: .65rem !important; }
            .dashboard-compact-cards .p-2 { padding: .4rem !important; }
            .dashboard-compact-cards .w-5 { width: 1rem !important; }
            .dashboard-compact-cards .h-5 { height: 1rem !important; }
        </style>
        <div class="dashboard-compact-cards dashboard-kpi-grid">
            @if($canPppoeActive)
            <!-- PPPoE Aktif -->
            <div class="dashboard-kpi-card bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-6 relative overflow-hidden group hover:border-slate-700/60 transition-all duration-300">
                <div class="absolute right-0 bottom-0 w-24 h-24 bg-emerald-500/5 rounded-full blur-xl group-hover:bg-emerald-500/10 transition-all duration-300"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">PPPoE Aktif</span>
                    <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-white">{{ $mikrotikStats['active'] }}</span>
                    <span class="text-xs text-slate-400">/ {{ $mikrotikStats['total'] }} total</span>
                </div>
                <p class="text-xs text-emerald-400 mt-2 font-medium">Pelanggan sedang terhubung</p>
            </div>
            @endif

            @if($canPppoeOffline)
            <!-- PPPoE Offline -->
            <div class="dashboard-kpi-card bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-6 relative overflow-hidden group hover:border-slate-700/60 transition-all duration-300">
                <div class="absolute right-0 bottom-0 w-24 h-24 bg-slate-500/5 rounded-full blur-xl group-hover:bg-slate-500/10 transition-all duration-300"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">PPPoE Offline</span>
                    <span class="p-2 rounded-xl bg-slate-800 text-slate-400 border border-slate-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path></svg>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-3xl font-bold tracking-tight text-white">{{ $mikrotikStats['offline'] }}</span>
                </div>
                <p class="text-xs text-slate-400 mt-2 font-medium">Router pelanggan mati/putus</p>
            </div>
            @endif

            @if($canOdpOffline)
            <!-- ODP Offline -->
            <div class="dashboard-kpi-card bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-6 relative overflow-hidden group hover:border-slate-700/60 transition-all duration-300">
                <div class="absolute right-0 bottom-0 w-24 h-24 bg-orange-500/5 rounded-full blur-xl group-hover:bg-orange-500/10 transition-all duration-300"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">ODP Offline</span>
                    <span class="p-2 rounded-xl bg-orange-500/10 text-orange-400 border border-orange-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8m-8 4h8m-9 8h10a2 2 0 002-2V7l-4-4H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-white">{{ $odpOfflineStats['count'] ?? 0 }}</span>
                    <span class="text-xs text-slate-400">/ {{ $odpOfflineStats['total'] ?? 0 }} ODP</span>
                </div>
                <p class="text-xs text-orange-400 mt-2 font-medium">
                    @if(!($odpOfflineStats['checked'] ?? false))
                        Menunggu data MikroTik
                    @elseif(!empty($odpOfflineStats['names']))
                        {{ implode(', ', $odpOfflineStats['names']) }}
                    @else
                        Semua ODP terdeteksi normal
                    @endif
                </p>
            </div>
            @endif

            @if($canPppoeDisabled)
            <!-- PPPoE Disable -->
            <div class="dashboard-kpi-card bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-6 relative overflow-hidden group hover:border-slate-700/60 transition-all duration-300">
                <div class="absolute right-0 bottom-0 w-24 h-24 bg-rose-500/5 rounded-full blur-xl group-hover:bg-rose-500/10 transition-all duration-300"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">PPPoE Disable</span>
                    <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-3xl font-bold tracking-tight text-white">{{ $mikrotikStats['disabled'] }}</span>
                </div>
                <p class="text-xs text-rose-400 mt-2 font-medium">Akun dinonaktifkan manual</p>
            </div>
            @endif

            @if($canPppoeIsolated)
            <!-- PPPoE Isolir -->
            <div class="dashboard-kpi-card bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-6 relative overflow-hidden group hover:border-slate-700/60 transition-all duration-300">
                <div class="absolute right-0 bottom-0 w-24 h-24 bg-amber-500/5 rounded-full blur-xl group-hover:bg-amber-500/10 transition-all duration-300"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">PPPoE Isolir</span>
                    <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-3xl font-bold tracking-tight text-white">{{ $mikrotikStats['isolir'] }}</span>
                </div>
                <p class="text-xs text-amber-400 mt-2 font-medium">Pelanggan menunggak</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($canTrafficLive && isset($dashboardInterfaces) && count($dashboardInterfaces) > 0)
    <!-- Live Traffic Monitor Grid -->
    <div>
        <div class="dashboard-section-title">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            </div>
            <h2 class="text-lg font-bold text-white tracking-tight">Trafik Jaringan (Live)</h2>
        </div>
        <div id="traffic-status" class="mb-4 hidden rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-3 text-xs font-medium text-slate-300"></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <div class="rounded-xl border border-indigo-500/20 bg-indigo-500/10 px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-300">Total Download</div>
                <div id="traffic-total-rx" class="mt-1 text-xl font-extrabold text-white font-mono">0.00 Mbps</div>
            </div>
            <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wider text-rose-300">Total Upload</div>
                <div id="traffic-total-tx" class="mt-1 text-xl font-extrabold text-white font-mono">0.00 Mbps</div>
            </div>
            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-300">Update Realtime</div>
                <div id="traffic-updated-at" class="mt-1 text-xl font-extrabold text-white font-mono">--:--:--</div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6" id="traffic-container">
            @foreach($dashboardInterfaces as $iface)
            <!-- Traffic Card -->
            <div class="dashboard-panel bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-6 relative overflow-hidden group hover:border-slate-700/60 transition-all duration-300" id="traffic-card-{{ Str::slug($iface) }}">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-bold text-white tracking-wider">{{ $iface }}</span>
                    <span class="relative flex h-3 w-3" id="traffic-dot-{{ Str::slug($iface) }}">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                </div>
                
                <div class="space-y-4">
                    <!-- Download (RX) -->
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-indigo-400 font-semibold flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg> Download
                            </span>
                            <span class="text-white font-mono font-bold" id="rx-text-{{ Str::slug($iface) }}">0.00 Mbps</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-500 h-1.5 rounded-full transition-all duration-500 ease-out" id="rx-bar-{{ Str::slug($iface) }}" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Upload (TX) -->
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-rose-400 font-semibold flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg> Upload
                            </span>
                            <span class="text-white font-mono font-bold" id="tx-text-{{ Str::slug($iface) }}">0.00 Mbps</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-rose-500 h-1.5 rounded-full transition-all duration-500 ease-out" id="tx-bar-{{ Str::slug($iface) }}" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-800 pt-3 text-[11px] text-slate-500">
                    <span>Status</span>
                    <span id="traffic-state-{{ Str::slug($iface) }}" class="font-bold text-slate-400">Menunggu</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Statistik Utama (Keuangan & Pelanggan) -->
    @if($canAnyMainStatCard)
    <div>
        <div class="dashboard-kpi-grid">
            
            @if($canActiveCustomersCard)
            <!-- Pelanggan Aktif -->
            <div class="dashboard-kpi-card flex-1 bg-gradient-to-br from-indigo-900/40 to-slate-900/80 border border-indigo-500/20 backdrop-blur-md rounded-3xl p-6 relative overflow-hidden group hover:border-indigo-500/40 transition-all duration-300 shadow-lg shadow-indigo-900/20">
                <div class="absolute right-0 bottom-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-all duration-500"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Pelanggan Aktif</span>
                    <span class="p-2.5 rounded-xl bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-5 flex items-end gap-2 relative z-10">
                    <span class="text-4xl font-extrabold tracking-tight text-white">{{ $activeCustomers }}</span>
                    <span class="text-sm font-medium text-indigo-300 pb-1">/ {{ $totalCustomers }} total</span>
                </div>
                <p class="text-xs text-indigo-200/70 mt-3 font-medium relative z-10">Koneksi aktif di RT/RW</p>
            </div>
            @endif

            @if($canMonthlyTargetCard)
            <!-- Target Pendapatan -->
            <div class="dashboard-kpi-card flex-1 bg-slate-900/60 border border-slate-700/50 backdrop-blur-md rounded-3xl p-6 relative overflow-hidden group hover:border-slate-600 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Target Bulanan</span>
                    <span class="p-2 rounded-xl bg-slate-800 text-slate-400 border border-slate-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-5">
                    <span class="text-2xl md:text-3xl font-extrabold tracking-tight text-white">Rp {{ number_format($expectedRevenue, 0, ',', '.') }}</span>
                </div>
                <p class="text-xs text-slate-500 mt-3 font-medium">Estimasi jika lunas semua</p>
            </div>
            @endif

            @if($canCollectedRevenueCard)
            <!-- Terkumpul -->
            <div class="dashboard-kpi-card flex-1 bg-gradient-to-br from-emerald-900/40 to-slate-900/80 border border-emerald-500/20 backdrop-blur-md rounded-3xl p-6 relative overflow-hidden group hover:border-emerald-500/40 transition-all duration-300 shadow-lg shadow-emerald-900/10">
                <div class="absolute right-0 bottom-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all duration-500"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-xs font-bold text-emerald-200 uppercase tracking-wider">Dana Terkumpul</span>
                    <span class="p-2.5 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-5 relative z-10">
                    <span class="text-2xl md:text-3xl font-extrabold tracking-tight text-white">Rp {{ number_format($collectedRevenue, 0, ',', '.') }}</span>
                </div>
                <p class="text-xs text-emerald-300/70 mt-3 font-medium relative z-10">Pendapatan lunas bulan ini</p>
            </div>
            @endif

            @if($canUnpaidAmountCard)
            <!-- Tunggakan -->
            <div class="dashboard-kpi-card flex-1 bg-gradient-to-br from-rose-900/40 to-slate-900/80 border border-rose-500/20 backdrop-blur-md rounded-3xl p-6 relative overflow-hidden group hover:border-rose-500/40 transition-all duration-300 shadow-lg shadow-rose-900/10">
                <div class="absolute right-0 bottom-0 w-32 h-32 bg-rose-500/10 rounded-full blur-2xl group-hover:bg-rose-500/20 transition-all duration-500"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-xs font-bold text-rose-200 uppercase tracking-wider">Tertunggak</span>
                    <span class="p-2.5 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-5 flex items-baseline gap-2 relative z-10">
                    <span class="text-2xl md:text-3xl font-extrabold tracking-tight text-white">Rp {{ number_format($unpaidAmount, 0, ',', '.') }}</span>
                </div>
                <p class="text-xs text-rose-300/70 mt-3 font-medium relative z-10">{{ $unpaidCount }} pelanggan belum lunas</p>
            </div>
            @endif

        </div>
    </div>
    @endif

    @if($canCollectedRevenueCard)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($paymentTotals as $key => $paymentTotal)
            @php
                $paymentTone = [
                    'cash' => 'border-amber-500/20 bg-amber-500/10 text-amber-300',
                    'qris' => 'border-cyan-500/20 bg-cyan-500/10 text-cyan-300',
                    'transfer' => 'border-indigo-500/20 bg-indigo-500/10 text-indigo-300',
                ][$key] ?? 'border-slate-700 bg-slate-900 text-slate-300';
            @endphp
            <div class="dashboard-kpi-card rounded-2xl border {{ $paymentTone }} px-5 py-4">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider">{{ $paymentTotal['label'] }}</span>
                    <span class="text-[11px] font-bold">{{ $paymentTotal['count'] }} trx</span>
                </div>
                <div class="mt-2 text-xl font-extrabold text-white">Rp {{ number_format($paymentTotal['amount'], 0, ',', '.') }}</div>
                <p class="mt-1 text-[11px] opacity-80">Pembayaran lunas bulan ini</p>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Progress Card & Quick Actions -->
    @if($canCollectionProgressCard || $canInvoiceGeneratorCard)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        @if($canCollectionProgressCard)
        <!-- Progress Bar Card -->
        <div class="dashboard-panel {{ $canInvoiceGeneratorCard ? 'lg:col-span-2' : 'lg:col-span-3' }} bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl p-8 flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-100">Progress Penagihan Bulan Ini</h3>
                <p class="text-xs text-slate-400 mt-1">Persentase uang iuran terkumpul dari total seluruh target aktif.</p>
                
                <div class="mt-8 relative pt-1">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span class="text-xs font-bold inline-block py-1 px-2.5 uppercase rounded-full text-indigo-400 bg-indigo-500/10 border border-indigo-500/20">
                                Tingkat Kolektibilitas
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-indigo-400">
                                {{ $collectionRate }}%
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-3.5 mb-4 text-xs flex rounded-full bg-slate-800/60 border border-slate-800">
                        <div style="width: {{ $collectionRate }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-600 rounded-full transition-all duration-1000"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t border-slate-800/60">
                <div>
                    <span class="text-xs text-slate-400">Uang Terkumpul</span>
                    <h5 class="text-lg font-bold text-emerald-400 mt-0.5">Rp {{ number_format($collectedRevenue, 0, ',', '.') }}</h5>
                </div>
                <div>
                    <span class="text-xs text-slate-400">Sisa Tagihan</span>
                    <h5 class="text-lg font-bold text-indigo-400 mt-0.5">Rp {{ number_format($expectedRevenue - $collectedRevenue > 0 ? $expectedRevenue - $collectedRevenue : 0, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        @endif

        @if($canInvoiceGeneratorCard)
        <!-- Quick Action: Generate Invoices -->
        <div class="dashboard-panel bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl p-8 flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-100">Pembuatan Tagihan Bulanan</h3>
                <p class="text-xs text-slate-400 mt-1">Buat tagihan massal otomatis untuk semua pelanggan dengan status Aktif.</p>

                <form action="{{ route('invoices.generate') }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Bulan Tagihan</label>
                        <select name="month" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Tahun</label>
                        <select name="year" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                            <option value="2025" {{ $currentYear == 2025 ? 'selected' : '' }}>2025</option>
                            <option value="2026" {{ $currentYear == 2026 ? 'selected' : '' }}>2026</option>
                            <option value="2027" {{ $currentYear == 2027 ? 'selected' : '' }}>2027</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-650 hover:to-purple-700 text-white font-medium px-4 py-3 rounded-xl shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-300 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span>Buat Tagihan Sekarang</span>
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>
    @endif

    <!-- Recent Payments Section -->
    @if($canRecentPaymentsCard)
    <div class="dashboard-panel bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-100">Riwayat Pembayaran Terbaru</h3>
                <p class="text-xs text-slate-400 mt-1">Daftar 5 pembayaran terakhir yang berhasil masuk.</p>
            </div>
            <a href="{{ route('invoices.index', ['status' => 'paid']) }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1">
                <span>Semua Pembayaran</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs uppercase font-bold">
                        <th class="py-4 px-4">No. Invoice</th>
                        <th class="py-4 px-4">Nama Pelanggan</th>
                        <th class="py-4 px-4">Paket</th>
                        <th class="py-4 px-4">Jumlah Bayar</th>
                        <th class="py-4 px-4">Metode</th>
                        <th class="py-4 px-4">Tanggal Bayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 text-sm text-slate-200">
                    @forelse ($recentPayments as $payment)
                        <tr class="hover:bg-slate-800/10 transition-colors">
                            <td class="py-4 px-4 font-mono text-indigo-400 font-semibold">{{ $payment->invoice_number }}</td>
                            <td class="py-4 px-4 font-semibold">{{ $payment->customer?->name ?? 'Pelanggan terhapus' }}</td>
                            <td class="py-4 px-4 text-slate-400">{{ $payment->customer?->package?->name ?? '-' }}</td>
                            <td class="py-4 px-4 font-bold text-emerald-400">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $payment->payment_method === 'Transfer' ? 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20' : 'bg-amber-500/10 text-amber-300 border border-amber-500/20' }}">
                                    {{ $payment->payment_method }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-slate-400 text-xs">{{ $payment->paid_at->translatedFormat('d M Y - H:i') }} WIB</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 font-medium">Belum ada riwayat pembayaran masuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@if($canTrafficLive && isset($dashboardInterfaces) && count($dashboardInterfaces) > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const trafficStatusEl = document.getElementById('traffic-status');
    const totalRxEl = document.getElementById('traffic-total-rx');
    const totalTxEl = document.getElementById('traffic-total-tx');
    const updatedAtEl = document.getElementById('traffic-updated-at');

    function setTrafficStatus(message, type = 'info') {
        if (!trafficStatusEl) return;

        if (!message) {
            trafficStatusEl.classList.add('hidden');
            trafficStatusEl.innerText = '';
            return;
        }

        trafficStatusEl.classList.remove('hidden', 'border-red-500/40', 'text-red-300', 'border-emerald-500/30', 'text-emerald-300', 'border-slate-800', 'text-slate-300');
        trafficStatusEl.classList.add(type === 'error' ? 'border-red-500/40' : type === 'success' ? 'border-emerald-500/30' : 'border-slate-800');
        trafficStatusEl.classList.add(type === 'error' ? 'text-red-300' : type === 'success' ? 'text-emerald-300' : 'text-slate-300');
        trafficStatusEl.innerText = message;
    }

    function formatBits(bits) {
        if (!bits) return '0.00 Mbps';
        const mbps = (bits / 1000000).toFixed(2);
        return mbps + ' Mbps';
    }

    function calculatePercent(bps, maxBps = 100000000) { // Default 100 Mbps max
        let percent = (bps / maxBps) * 100;
        if (percent > 100) percent = 100;
        return percent;
    }

    // Helper to generate slug similar to Laravel's Str::slug
    function slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '-')      // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
    }

    function fetchTraffic() {
        fetch('{{ route('api.mikrotik.traffic') }}', {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().catch(() => ({})).then(payload => {
                        throw new Error(payload.message || 'Gagal mengambil data trafik.');
                    });
                }

                return response.json();
            })
            .then(res => {
                if (res.ok && res.summary && res.summary.has_traffic) {
                    setTrafficStatus('');
                } else if (res.ok && res.message) {
                    setTrafficStatus(res.message, 'info');
                } else if (res.message) {
                    setTrafficStatus(res.message, 'error');
                }

                if (res.summary) {
                    if (totalRxEl) totalRxEl.innerText = formatBits(parseInt(res.summary.rx_bits_per_second || 0));
                    if (totalTxEl) totalTxEl.innerText = formatBits(parseInt(res.summary.tx_bits_per_second || 0));
                    if (updatedAtEl) updatedAtEl.innerText = res.summary.updated_at || '--:--:--';
                }

                if(res.data && Array.isArray(res.data)) {
                    res.data.forEach(iface => {
                        if(iface.name) {
                            const slug = slugify(iface.name);
                            const rxBps = parseInt(iface['rx-bits-per-second'] || 0);
                            const txBps = parseInt(iface['tx-bits-per-second'] || 0);
                            const hasError = Boolean(iface.error);
                            
                            const rxTextEl = document.getElementById('rx-text-' + slug);
                            const txTextEl = document.getElementById('tx-text-' + slug);
                            const rxBarEl = document.getElementById('rx-bar-' + slug);
                            const txBarEl = document.getElementById('tx-bar-' + slug);
                            const stateEl = document.getElementById('traffic-state-' + slug);
                            const dotEl = document.getElementById('traffic-dot-' + slug);

                            if(rxTextEl && txTextEl) {
                                rxTextEl.innerText = formatBits(rxBps);
                                txTextEl.innerText = formatBits(txBps);
                                
                                // Update bars (assuming max 100Mbps for visual scale, adjust as needed)
                                if (rxBarEl) rxBarEl.style.width = calculatePercent(rxBps) + '%';
                                if (txBarEl) txBarEl.style.width = calculatePercent(txBps) + '%';

                                const isActive = rxBps + txBps > 0;
                                if (stateEl) {
                                    stateEl.innerText = hasError ? 'Tidak terbaca' : (isActive ? 'Aktif' : 'Idle');
                                    stateEl.className = hasError ? 'font-bold text-red-400' : (isActive ? 'font-bold text-emerald-400' : 'font-bold text-slate-500');
                                }
                                if (dotEl) {
                                    dotEl.className = hasError ? 'relative flex h-3 w-3 opacity-30 grayscale' : (isActive ? 'relative flex h-3 w-3' : 'relative flex h-3 w-3 opacity-40');
                                }
                            }
                        }
                    });
                }
            })
            .catch(error => {
                setTrafficStatus(error.message || 'Gagal mengambil data trafik.', 'error');
                console.error('Error fetching traffic:', error);
            });
    }

    // Fetch immediately, then every 2 seconds
    fetchTraffic();
    setInterval(fetchTraffic, 2000);
});
</script>
@endif
@endsection
