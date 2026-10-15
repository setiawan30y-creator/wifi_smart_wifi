@extends('layouts.app')

@section('content')
<!-- Leaflet.js CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<style>
    /* Styling Custom Leaflet */
    #network-map-canvas {
        height: 620px;
        border-radius: 24px;
        z-index: 10;
        background-color: #0b1329;
    }
    
    /* Tema Gelap Leaflet Pop-up & Controls */
    .leaflet-popup-content-wrapper, .leaflet-popup-tip {
        background-color: rgba(15, 23, 42, 0.95) !important;
        color: #f8fafc !important;
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-blur: 12px;
        border-radius: 14px;
    }
    .leaflet-popup-content {
        margin: 12px 16px;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .leaflet-container a.leaflet-popup-close-button {
        color: #94a3b8 !important;
        padding: 8px 8px 0 0;
    }
    .leaflet-bar {
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: none !important;
        border-radius: 10px !important;
        overflow: hidden;
    }
    .leaflet-bar a {
        background-color: rgba(15, 23, 42, 0.9) !important;
        color: #94a3b8 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        transition: all 0.2s;
    }
    .leaflet-bar a:hover {
        background-color: #4f46e5 !important;
        color: #ffffff !important;
    }

    .leaflet-tooltip.network-floating-label {
        background: rgba(15, 23, 42, 0.88);
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 999px;
        color: #f8fafc;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1;
        max-width: 180px;
        overflow: hidden;
        padding: 5px 8px;
        pointer-events: none;
        text-overflow: ellipsis;
        white-space: nowrap;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.26);
    }
    .leaflet-tooltip.network-floating-label:before {
        display: none;
    }
    .leaflet-tooltip.network-floating-label.server-label {
        background: rgba(8, 145, 178, 0.92);
        border-color: rgba(165, 243, 252, 0.7);
        color: #ecfeff;
    }
    .leaflet-tooltip.network-floating-label.isp-label {
        background: rgba(217, 119, 6, 0.92);
        border-color: rgba(253, 230, 138, 0.7);
        color: #fffbeb;
    }
    .leaflet-tooltip.network-floating-label.odp-label {
        background: rgba(79, 70, 229, 0.9);
        border-color: rgba(199, 210, 254, 0.65);
        color: #eef2ff;
    }
    .leaflet-tooltip.network-floating-label.customer-label {
        background: rgba(5, 150, 105, 0.9);
        border-color: rgba(167, 243, 208, 0.65);
        color: #ecfdf5;
        font-size: 9px;
        max-width: 150px;
        padding: 4px 7px;
    }
    .leaflet-tooltip.network-floating-label.customer-label.inactive-label {
        background: rgba(220, 38, 38, 0.9);
        border-color: rgba(254, 202, 202, 0.65);
        color: #fff1f2;
    }

    /* Animasi Kabel Mengalir */
    .cable-line {
        stroke-dasharray: 8, 10;
        animation: cableFlow 20s linear infinite;
    }
    .cable-line-inactive {
        stroke-dasharray: 4, 6;
        opacity: 0.4;
    }
    @keyframes cableFlow {
        to {
            stroke-dashoffset: -1000;
        }
    }

    /* Pulsing ISP Marker */
    .isp-pulse-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .isp-marker-ring {
        position: absolute;
        width: 52px;
        height: 52px;
        background-color: rgba(245, 158, 11, 0.18);
        border: 2px solid rgba(245, 158, 11, 0.5);
        border-radius: 50%;
        animation: pulseIsp 2s infinite ease-out;
        z-index: 1;
    }
    .isp-marker-badge {
        width: 34px;
        height: 34px;
        background: linear-gradient(135deg, #d97706, #fbbf24);
        border: 2px solid #ffffff;
        border-radius: 50%;
        box-shadow: 0 0 14px rgba(245, 158, 11, 0.65);
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        transition: all 0.3s ease;
    }
    .isp-marker-badge:hover {
        transform: scale(1.15);
        box-shadow: 0 0 20px rgba(245, 158, 11, 0.9);
    }
    @keyframes pulseIsp {
        0% { transform: scale(0.5); opacity: 1; }
        100% { transform: scale(1.35); opacity: 0; }
    }

    /* Pulsing Server Marker */
    .server-pulse-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .server-marker-ring {
        position: absolute;
        width: 52px;
        height: 52px;
        background-color: rgba(6, 182, 212, 0.18);
        border: 2px solid rgba(6, 182, 212, 0.5);
        border-radius: 50%;
        animation: pulseServer 2s infinite ease-out;
        z-index: 1;
    }
    .server-marker-badge {
        width: 34px;
        height: 34px;
        background: linear-gradient(135deg, #0891b2, #22d3ee);
        border: 2px solid #ffffff;
        border-radius: 50%;
        box-shadow: 0 0 14px rgba(6, 182, 212, 0.65);
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        transition: all 0.3s ease;
    }
    .server-marker-badge:hover {
        transform: scale(1.15);
        box-shadow: 0 0 20px rgba(6, 182, 212, 0.9);
    }
    @keyframes pulseServer {
        0% { transform: scale(0.5); opacity: 1; }
        100% { transform: scale(1.35); opacity: 0; }
    }

    /* Pulsing ODP & Customer Markers */
    .odp-pulse-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .odp-marker-ring {
        position: absolute;
        width: 28px;
        height: 28px;
        background-color: rgba(99, 102, 241, 0.3);
        border: 2px solid rgba(99, 102, 241, 0.7);
        border-radius: 50%;
        animation: pulseOdp 2s infinite ease-out;
    }
    .odp-marker-dot {
        width: 12px;
        height: 12px;
        background-color: #6366f1;
        border: 2px solid #ffffff;
        border-radius: 50%;
        box-shadow: 0 0 10px #6366f1;
        z-index: 2;
    }
    
    .customer-pulse-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .customer-marker-ring {
        position: absolute;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        animation: pulseCustomer 2.5s infinite ease-out;
    }
    .customer-marker-ring.active {
        background-color: rgba(16, 185, 129, 0.25);
        border: 1.5px solid rgba(16, 185, 129, 0.6);
    }
    .customer-marker-ring.inactive {
        background-color: rgba(239, 68, 68, 0.25);
        border: 1.5px solid rgba(239, 68, 68, 0.6);
    }
    .customer-marker-dot {
        width: 8px;
        height: 8px;
        border: 1.5px solid #ffffff;
        border-radius: 50%;
        z-index: 2;
    }
    .customer-marker-dot.active {
        background-color: #10b981;
        box-shadow: 0 0 6px #10b981;
    }
    .customer-marker-dot.inactive {
        background-color: #ef4444;
        box-shadow: 0 0 6px #ef4444;
    }

    @keyframes pulseOdp {
        0% { transform: scale(0.6); opacity: 1; }
        100% { transform: scale(1.6); opacity: 0; }
    }
    @keyframes pulseCustomer {
        0% { transform: scale(0.6); opacity: 1; }
        100% { transform: scale(1.4); opacity: 0; }
    }

    .network-client-popup {
        width: 320px;
        color: #e5e7eb;
    }
    .network-client-popup .traffic-sparkline {
        width: 100%;
        height: 78px;
        border-radius: 12px;
        background:
            linear-gradient(rgba(148, 163, 184, 0.11) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
        background-size: 100% 19px, 52px 100%;
        border: 1px solid rgba(148, 163, 184, 0.14);
    }
    .network-client-popup .popup-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 34px;
        border-radius: 9px;
        font-size: 11px;
        font-weight: 800;
        transition: all 0.18s ease;
    }
    .network-client-popup .popup-action:hover {
        transform: translateY(-1px);
        filter: brightness(1.08);
    }
    .add-point-cursor {
        cursor: crosshair !important;
    }
</style>

<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-white">Peta Jaringan Jasa Internet</h1>
                <p class="text-slate-400 mt-1">Pantau rute pusat bandwidth ISP, server NOC, box ODP, hingga ke rumah pelanggan secara dinamis.</p>
            </div>
            
            <!-- Map Theme Switcher is now an overlay inside Leaflet Map -->
        </div>
        <div class="flex flex-wrap items-center gap-3 self-start xl:self-auto">
            <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-350">
                <span class="w-5 h-5 rounded-lg bg-gradient-to-tr from-amber-600 to-amber-400 flex items-center justify-center text-white shrink-0 shadow-sm shadow-amber-500/20">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                        <path d="M2 12h20"></path>
                    </svg>
                </span>
                <span>ISP Gateway</span>
            </span>
            <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-350">
                <span class="w-5 h-5 rounded-lg bg-gradient-to-tr from-cyan-600 to-cyan-400 flex items-center justify-center text-white shrink-0 shadow-sm shadow-cyan-500/20">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect>
                        <rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect>
                        <line x1="6" x2="6.01" y1="6" y2="6"></line>
                        <line x1="6" x2="6.01" y1="18" y2="18"></line>
                    </svg>
                </span>
                <span>Server NOC</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-350">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <span>ODP Node</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-350">
                <span class="w-3 h-1.5 rounded bg-red-500"></span>
                <span>Jalur Distribusi</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-350">
                <span class="w-3 h-1 rounded bg-emerald-500"></span>
                <span>Kabel Drop FO</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-350">
                <span class="w-3 h-1 rounded bg-fuchsia-500"></span>
                <span>Kabel Drop LAN</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-350">
                <span class="w-3 h-1 rounded border border-dashed border-slate-600 bg-slate-800/80"></span>
                <span>Koneksi Nonaktif</span>
            </span>
        </div>
    </div>

    <!-- Tabs Routing Mode -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
        <div class="flex items-center gap-2">
            <button id="tab-mode-udara" onclick="switchRoutingMode('udara')" class="px-5 py-2.5 rounded-t-xl font-bold text-sm transition-all duration-300 flex items-center gap-2 bg-indigo-600 text-white border-b-2 border-indigo-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                </svg>
                Mode Udara (Garis Lurus)
            </button>
            <button id="tab-mode-darat" onclick="switchRoutingMode('darat')" class="px-5 py-2.5 rounded-t-xl font-bold text-sm transition-all duration-300 flex items-center gap-2 bg-transparent text-slate-400 hover:text-slate-200 border-b-2 border-transparent hover:border-slate-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                Mode Darat (Rute OSRM)
            </button>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <div id="add-point-status" class="hidden text-xs font-bold text-emerald-300 items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Klik titik di peta
            </div>
            <button type="button" onclick="startAddMapPoint('odp')" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-extrabold inline-flex items-center gap-2 transition-all">
                <span class="w-2.5 h-2.5 rounded-full bg-white/90"></span>
                Tambah ODP
            </button>
            <button type="button" onclick="startAddMapPoint('customer')" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold inline-flex items-center gap-2 transition-all">
                <span class="w-2.5 h-2.5 rounded-full bg-white/90"></span>
                Tambah Client
            </button>
            <div id="routing-status-indicator" class="text-xs font-bold text-slate-500 hidden items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Memproses Rute...
            </div>
        </div>
    </div>

    <!-- Peta Canvas Card -->
    <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-b-3xl rounded-tr-3xl p-5 relative overflow-hidden group shadow-xl">
        <div id="network-map-canvas" class="w-full h-[620px] rounded-[24px] z-10 relative"></div>
    </div>
</div>

</div>

<!-- Modal Tambah Titik Perangkat -->
<div id="addMapPointModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-slate-950/75 backdrop-blur-sm px-4">
    <div class="w-full max-w-2xl rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl shadow-black/50 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
            <div>
                <h3 id="addPointModalTitle" class="text-base font-extrabold text-white">Tambah Titik</h3>
                <p id="addPointModalCoords" class="text-xs text-slate-400 mt-1 font-mono">Koordinat: -</p>
            </div>
            <button type="button" onclick="closeAddMapPointModal()" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold transition-colors">x</button>
        </div>

        <form id="addOdpPointForm" method="POST" action="{{ route('odps.store') }}" class="hidden p-5 space-y-4">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ route('network-map') }}">
            <input type="hidden" name="latitude" id="addOdpLatitude">
            <input type="hidden" name="longitude" id="addOdpLongitude">
            <input type="hidden" name="maps_link" id="addOdpMapsLink">
            <input type="hidden" name="devices[0][device_type]" value="FO">
            <input type="hidden" name="devices[0][device_spec]" value="1/8">

            <div class="grid md:grid-cols-2 gap-4">
                <label class="space-y-1.5">
                    <span class="text-xs font-bold text-slate-300">Nama ODP</span>
                    <input name="name" required placeholder="ODP Amaliah 01" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold text-slate-300">ODP Induk</span>
                    <select name="parent_odp_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Dari server / tanpa induk</option>
                        @foreach($odps as $odp)
                            <option value="{{ $odp->id }}">{{ $odp->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="space-y-1.5 block">
                <span class="text-xs font-bold text-slate-300">Alamat / Patokan</span>
                <textarea name="address" id="addOdpAddress" required rows="3" placeholder="Contoh: Depan rumah Pak RT, tiang listrik dekat gang" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </label>

            <div class="rounded-xl border border-indigo-500/20 bg-indigo-500/10 px-3 py-2 text-xs text-indigo-100">
                Perangkat awal otomatis dibuat sebagai splitter FO 1/8. Detail perangkat bisa dirapikan lagi dari halaman Manajemen ODP.
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeAddMapPointModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-bold">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-extrabold">Simpan ODP</button>
            </div>
        </form>

        <form id="addCustomerPointForm" method="POST" action="{{ route('customers.store') }}" class="hidden p-5 space-y-4">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ route('network-map') }}">
            <input type="hidden" name="latitude" id="addCustomerLatitude">
            <input type="hidden" name="longitude" id="addCustomerLongitude">
            <input type="hidden" name="maps_link" id="addCustomerMapsLink">
            <input type="hidden" name="billing_day" value="10">
            <input type="hidden" name="status_rumah" value="milik_sendiri">
            <input type="hidden" name="jalur" value="FO">

            <div class="grid md:grid-cols-2 gap-4">
                <label class="space-y-1.5">
                    <span class="text-xs font-bold text-slate-300">Nama Client</span>
                    <input name="name" required placeholder="Nama pelanggan" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold text-slate-300">No. WhatsApp</span>
                    <input name="phone" required placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold text-slate-300">Paket Internet</span>
                    <select name="package_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Pilih paket</option>
                        @foreach($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold text-slate-300">ODP Terdekat</span>
                    <select name="odp_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Belum ditentukan</option>
                        @foreach($odps as $odp)
                            <option value="{{ $odp->id }}">{{ $odp->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="space-y-1.5 block">
                <span class="text-xs font-bold text-slate-300">Alamat</span>
                <textarea name="address" id="addCustomerAddress" required rows="3" placeholder="Alamat lengkap / patokan rumah" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:ring-emerald-500"></textarea>
            </label>

            @if($packages->isEmpty())
                <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-xs text-rose-100">
                    Belum ada paket internet. Tambahkan paket dulu agar client bisa disimpan.
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeAddMapPointModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-bold">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-extrabold" @if($packages->isEmpty()) disabled @endif>Simpan Client</button>
            </div>
        </form>
    </div>
</div>

<!-- Floating Panel Editor Rute (Mode In-Place) -->
<div id="inlineRouteEditorPanel" class="absolute top-16 right-16 z-[9999] hidden bg-slate-900 border border-amber-500/80 rounded-2xl p-5 shadow-2xl shadow-black w-80 transition-all duration-300 transform" style="pointer-events: auto;">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
        </div>
        <div>
            <h3 class="text-sm font-bold text-white leading-tight">Mode Edit Rute</h3>
            <p id="inlineEditTargetName" class="text-[11px] text-slate-400 mt-0.5 truncate max-w-[180px]">Memuat...</p>
        </div>
    </div>
    
    <div class="bg-slate-950/50 border border-slate-800 rounded-xl p-3 mb-5">
        <p class="text-[11px] text-slate-300 leading-relaxed">
            <strong class="text-indigo-400">Instruksi:</strong> Tarik (drag) titik putih ujung atau <b>tarik bagian mana saja dari garis putus-putus</b> ke jalan/belokan yang diinginkan. Setiap tarikan garis akan <b>otomatis menciptakan titik belok (biru) baru</b> tanpa batas!
        </p>
    </div>

    <div class="flex gap-2">
        <button onclick="closeRouteEditor()" class="flex-1 px-3 py-2 rounded-xl font-bold text-[11px] transition-all duration-300 text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700">
            Batal
        </button>
        <button onclick="saveRouteWaypoints()" class="flex-1 px-3 py-2 rounded-xl font-bold text-[11px] transition-all duration-300 bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
            Simpan Rute
        </button>
    </div>
</div>

<!-- Leaflet.js JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- Leaflet Routing Machine -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<!-- CSS tambahan untuk Routing Machine Modal -->
<style>
    /* Sembunyikan instruksi rute (teks turn-by-turn) karena kita cuma butuh garisnya */
    .leaflet-routing-container {
        display: none !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data ODP, pelanggan, ISP, & Server dari server
        const odps = @json($odps);
        const customers = @json($customers);
        const isps = @json($isps);
        const server = @json($server);

        // Cari koordinat rata-rata untuk fokus peta
        let totalLat = 0;
        let totalLng = 0;
        let count = 0;

        isps.forEach(isp => {
            if (isp && isp.latitude && isp.longitude) {
                let lat = parseFloat(isp.latitude);
                let lng = parseFloat(isp.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    totalLat += lat; totalLng += lng; count++;
                }
            }
        });

        if (server && server.latitude && server.longitude) {
            let lat = parseFloat(server.latitude);
            let lng = parseFloat(server.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                totalLat += lat; totalLng += lng; count++;
            }
        }

        odps.forEach(odp => {
            if (odp.latitude && odp.longitude) {
                let lat = parseFloat(odp.latitude);
                let lng = parseFloat(odp.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    totalLat += lat; totalLng += lng; count++;
                }
            }
        });

        customers.forEach(customer => {
            if (customer.latitude && customer.longitude) {
                let lat = parseFloat(customer.latitude);
                let lng = parseFloat(customer.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    totalLat += lat; totalLng += lng; count++;
                }
            }
        });

        // Koordinat default
        const defaultCenter = count > 0 ? [totalLat / count, totalLng / count] : [-6.2088, 106.8456];
        const defaultZoom = count > 0 ? 18 : 13;
        
        // Inisialisasi Peta
        const map = L.map('network-map-canvas', {
            maxZoom: 22,
            zoomSnap: 0.25,
            zoomDelta: 0.5,
            wheelPxPerZoomLevel: 80
        }).setView(defaultCenter, defaultZoom);
        
        // Ekspor ke window agar bisa diakses oleh fungsi Inline Route Editor
        window.networkMap = map;

        // Pindahkan panel editor ke dalam map canvas agar tampil di fullscreen
        const editorPanel = document.getElementById('inlineRouteEditorPanel');
        if (editorPanel) {
            L.DomEvent.disableClickPropagation(editorPanel);
            L.DomEvent.disableScrollPropagation(editorPanel);
            
            // Masukkan ke dalam control container agar pasti di atas layer peta Leaflet
            const controlContainer = document.querySelector('.leaflet-control-container');
            if (controlContainer) {
                controlContainer.appendChild(editorPanel);
            } else {
                document.getElementById('network-map-canvas').appendChild(editorPanel);
            }
        }

        // Custom Fullscreen Control
        const FullscreenControl = L.Control.extend({
            options: {
                position: 'topright'
            },
            onAdd: function(map) {
                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                container.innerHTML = `<a href="#" title="Full Screen" style="display:flex; justify-content:center; align-items:center; width:34px; height:34px;"><svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg></a>`;
                
                container.onclick = function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    const mapEl = document.getElementById('network-map-canvas');
                    
                    if (!document.fullscreenElement) {
                        // Tambahkan style sementara saat fullscreen
                        mapEl.style.height = '100vh';
                        mapEl.style.borderRadius = '0';
                        if (mapEl.requestFullscreen) {
                            mapEl.requestFullscreen().catch(err => {
                                console.error(`Error attempting to enable fullscreen: ${err.message}`);
                            });
                        }
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        }
                    }
                }
                return container;
            }
        });
        map.addControl(new FullscreenControl());

        // Event listener saat keluar dari fullscreen menggunakan tombol ESC
        document.addEventListener('fullscreenchange', (event) => {
            const mapEl = document.getElementById('network-map-canvas');
            if (!document.fullscreenElement) {
                // Kembalikan style seperti semula
                mapEl.style.height = '620px';
                mapEl.style.borderRadius = '24px';
                setTimeout(() => { map.invalidateSize(); }, 200);
            } else {
                setTimeout(() => { map.invalidateSize(); }, 200);
            }
        });
        
        // Custom Theme Switcher Control (Floating / Overlay)
        const ThemeSwitcherControl = L.Control.extend({
            options: {
                position: 'topleft'
            },
            onAdd: function(map) {
                const container = L.DomUtil.create('div');
                L.DomEvent.disableClickPropagation(container);
                
                container.innerHTML = `
                <div class="flex bg-slate-900/90 backdrop-blur-md rounded-xl p-1.5 border border-slate-700/50 shadow-lg shadow-black/50" style="pointer-events: auto; transform: translate(45px, 0);">
                    <button id="btn-theme-dark" onclick="switchMapTheme('dark')" class="px-3 py-1.5 rounded-lg text-[11px] font-bold bg-indigo-600 text-white shadow-md transition-all flex items-center gap-1 whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                        Gelap
                    </button>
                    <button id="btn-theme-light" onclick="switchMapTheme('light')" class="px-3 py-1.5 rounded-lg text-[11px] font-bold text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center gap-1 whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Terang
                    </button>
                    <button id="btn-theme-satellite" onclick="switchMapTheme('satellite')" class="px-3 py-1.5 rounded-lg text-[11px] font-bold text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center gap-1 whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Satelit
                    </button>
                    <button id="btn-theme-street" onclick="switchMapTheme('street')" class="px-3 py-1.5 rounded-lg text-[11px] font-bold text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center gap-1 whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                        Jalan
                    </button>
                </div>`;
                return container;
            }
        });
        map.addControl(new ThemeSwitcherControl());
        
        let currentTileLayer = null;

        window.switchMapTheme = function(theme) {
            if (currentTileLayer) {
                map.removeLayer(currentTileLayer);
            }
            
            // Reset semua tombol ke tampilan pasif
            const buttons = ['dark', 'light', 'satellite', 'street'];
            buttons.forEach(btn => {
                const el = document.getElementById('btn-theme-' + btn);
                if(el) {
                    el.className = "px-3 py-1.5 rounded-lg text-[11px] font-bold text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center gap-1 whitespace-nowrap";
                }
            });
            
            // Set tombol aktif
            const activeBtn = document.getElementById('btn-theme-' + theme);
            if(activeBtn) {
                activeBtn.className = "px-3 py-1.5 rounded-lg text-[11px] font-bold bg-indigo-600 text-white shadow-md transition-all flex items-center gap-1 whitespace-nowrap";
            }
            
            let url = '';
            let options = { maxZoom: 22, maxNativeZoom: 20 };
            
            if (theme === 'dark') {
                url = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
                options.subdomains = 'abcd';
                options.attribution = '&copy; OpenStreetMap &copy; CARTO';
            } else if (theme === 'light') {
                url = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
                options.subdomains = 'abcd';
                options.attribution = '&copy; OpenStreetMap &copy; CARTO';
            } else if (theme === 'satellite') {
                url = 'https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'; // Google Hybrid (Satelit + Label)
                options.attribution = '&copy; Google';
                options.maxNativeZoom = 21;
            } else if (theme === 'street') {
                url = 'https://mt0.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'; // Google Street (Default)
                options.attribution = '&copy; Google';
                options.maxNativeZoom = 21;
            }
            
            currentTileLayer = L.tileLayer(url, options).addTo(map);
            currentTileLayer.setZIndex(0);
        };

        // Inisialisasi tema awal
        switchMapTheme('dark');

        function escapeMapLabel(value) {
            return String(value || '-').replace(/[&<>"']/g, function(char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        }

        function bindFloatingName(marker, label, className, offset) {
            return marker.bindTooltip(escapeMapLabel(label), {
                permanent: true,
                direction: 'top',
                offset: offset || [0, -14],
                opacity: 1,
                className: `network-floating-label ${className}`
            });
        }

        function normalizePhoneForWa(phone) {
            const digits = String(phone || '').replace(/\D/g, '');
            if (!digits) return '';
            if (digits.startsWith('62')) return digits;
            if (digits.startsWith('0')) return `62${digits.substring(1)}`;
            return digits;
        }

        window.copyMapText = function(text) {
            const value = String(text || '');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value);
                return;
            }

            const temp = document.createElement('textarea');
            temp.value = value;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
        };

        window.copyMapEncodedText = function(encodedText) {
            copyMapText(decodeURIComponent(encodedText || ''));
        };

        function buildMapsUrl(lat, lng) {
            return `https://www.google.com/maps?q=${encodeURIComponent(`${lat},${lng}`)}`;
        }

        let addPointMode = null;

        window.startAddMapPoint = function(type) {
            addPointMode = type;
            map.closePopup();
            map.getContainer().classList.add('add-point-cursor');

            const status = document.getElementById('add-point-status');
            if (status) {
                status.classList.remove('hidden');
                status.classList.add('inline-flex');
                status.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>${type === 'odp' ? 'Klik lokasi ODP' : 'Klik lokasi client'}`;
            }
        };

        window.closeAddMapPointModal = function() {
            const modal = document.getElementById('addMapPointModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        };

        function openAddMapPointModal(type, latlng) {
            const lat = latlng.lat.toFixed(7);
            const lng = latlng.lng.toFixed(7);
            const mapsUrl = buildMapsUrl(lat, lng);
            const modal = document.getElementById('addMapPointModal');
            const odpForm = document.getElementById('addOdpPointForm');
            const customerForm = document.getElementById('addCustomerPointForm');
            const title = document.getElementById('addPointModalTitle');
            const coords = document.getElementById('addPointModalCoords');

            if (!modal || !odpForm || !customerForm) return;

            coords.textContent = `Koordinat: ${lat}, ${lng}`;
            odpForm.classList.toggle('hidden', type !== 'odp');
            customerForm.classList.toggle('hidden', type !== 'customer');

            if (type === 'odp') {
                title.textContent = 'Tambah Titik ODP';
                document.getElementById('addOdpLatitude').value = lat;
                document.getElementById('addOdpLongitude').value = lng;
                document.getElementById('addOdpMapsLink').value = mapsUrl;
                document.getElementById('addOdpAddress').value = `Titik ODP baru (${lat}, ${lng})`;
            } else {
                title.textContent = 'Tambah Titik Client';
                document.getElementById('addCustomerLatitude').value = lat;
                document.getElementById('addCustomerLongitude').value = lng;
                document.getElementById('addCustomerMapsLink').value = mapsUrl;
                document.getElementById('addCustomerAddress').value = `Lokasi client (${lat}, ${lng})`;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        map.on('click', function(event) {
            if (!addPointMode) return;

            openAddMapPointModal(addPointMode, event.latlng);
            addPointMode = null;
            map.getContainer().classList.remove('add-point-cursor');

            const status = document.getElementById('add-point-status');
            if (status) {
                status.classList.add('hidden');
                status.classList.remove('inline-flex');
            }
        });

        // Marker ISP Gateways
        isps.forEach((isp, index) => {
            if (isp && isp.latitude && isp.longitude) {
                const ispIcon = L.divIcon({
                    className: 'isp-pulse-icon',
                    html: `
                        <div class="isp-marker-ring"></div>
                        <div class="isp-marker-badge">
                            <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width: 17px; height: 17px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                                <path d="M2 12h20"></path>
                            </svg>
                        </div>
                    `,
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
                });

                const popupContent = `
                    <div class="space-y-2">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-1.5">
                            <span class="text-xs font-bold text-amber-400 font-mono tracking-wider">${isp.name}</span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold">ISP Gateway ${index + 1}</span>
                        </div>
                        <p class="text-[11px] text-slate-350">${isp.description || '-'}</p>
                        <p class="text-[10px] text-slate-500 font-mono">Koordinat: ${isp.latitude}, ${isp.longitude}</p>
                    </div>
                `;
                
                let lat = parseFloat(isp.latitude);
                let lng = parseFloat(isp.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const marker = L.marker([lat, lng], { icon: ispIcon })
                        .addTo(map)
                        .bindPopup(popupContent);
                    bindFloatingName(marker, isp.name || `ISP Gateway ${index + 1}`, 'isp-label', [0, -24]);
                }
            }
        });

        // Marker Server NOC
        if (server && server.latitude && server.longitude) {
            const serverIcon = L.divIcon({
                className: 'server-pulse-icon',
                html: `
                    <div class="server-marker-ring"></div>
                    <div class="server-marker-badge">
                        <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width: 17px; height: 17px;">
                            <rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect>
                            <rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect>
                            <line x1="6" x2="6.01" y1="6" y2="6" stroke-width="3"></line>
                            <line x1="6" x2="6.01" y1="18" y2="18" stroke-width="3"></line>
                        </svg>
                    </div>
                `,
                iconSize: [36, 36],
                iconAnchor: [18, 18]
            });

            const popupContent = `
                <div class="space-y-2">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-1.5">
                        <span class="text-xs font-bold text-cyan-400 font-mono tracking-wider">${server.name}</span>
                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 font-bold">Server NOC</span>
                    </div>
                    <p class="text-[11px] text-slate-350">${server.description}</p>
                    <p class="text-[10px] text-slate-500 font-mono">Koordinat: ${server.latitude}, ${server.longitude}</p>
                </div>
            `;
            
            let lat = parseFloat(server.latitude);
            let lng = parseFloat(server.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                const marker = L.marker([lat, lng], { icon: serverIcon })
                    .addTo(map)
                    .bindPopup(popupContent);
                bindFloatingName(marker, server.name || 'Server NOC', 'server-label', [0, -24]);
            }
        }

        // Marker ODP
        odps.forEach(odp => {
            if (odp.latitude && odp.longitude) {
                const odpIcon = L.divIcon({
                    className: 'odp-pulse-icon',
                    html: `
                        <div class="odp-marker-ring"></div>
                        <div class="odp-marker-dot"></div>
                    `,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                let deviceListHtml = '';
                if (odp.devices && odp.devices.length > 0) {
                    odp.devices.forEach(dev => {
                        deviceListHtml += `
                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold ${dev.device_type === 'FO' ? 'bg-indigo-500/20 text-indigo-300' : 'bg-purple-500/20 text-purple-300'} border border-slate-700 mt-1 mr-1">
                                ${dev.device_type} (${dev.device_spec})
                            </span>
                        `;
                    });
                } else {
                    deviceListHtml = '<span class="text-xs text-slate-500 italic">Belum ada perangkat</span>';
                }

                const popupContent = `
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-1.5">
                            <span class="text-xs font-bold text-indigo-400 font-mono tracking-wider">${odp.name}</span>
                            <span class="text-[10px] text-slate-400">Box ODP</span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[11px] text-slate-300"><strong class="text-slate-400">Alamat:</strong> ${odp.address}</p>
                            <p class="text-[11px] text-slate-300"><strong class="text-slate-400">Total Port FO:</strong> ${odp.fo_ports} Port</p>
                            <p class="text-[11px] text-slate-300"><strong class="text-slate-400">Total Port LAN:</strong> ${odp.lan_ports} Port</p>
                            <p class="text-[11px] text-slate-300"><strong class="text-slate-400">Tersambung:</strong> ${odp.customers ? odp.customers.length : 0} Rumah</p>
                        </div>
                        <div class="pt-1.5 border-t border-slate-800">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Perangkat Terpasang:</p>
                            <div class="flex flex-wrap">${deviceListHtml}</div>
                        </div>
                        <div class="pt-2 border-t border-slate-800 flex justify-end">
                            <button onclick="openRouteEditor(${odp.id}, 'odp')" class="text-[10px] flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 rounded transition-colors border border-indigo-500/20">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Sesuaikan Jalur Rute
                            </button>
                        </div>
                    </div>
                `;

                let lat = parseFloat(odp.latitude);
                let lng = parseFloat(odp.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const marker = L.marker([lat, lng], { icon: odpIcon })
                        .addTo(map)
                        .bindPopup(popupContent);
                    bindFloatingName(marker, odp.name || 'ODP', 'odp-label', [0, -18]);
                }
            }
        });

        // Marker Pelanggan
        const customerMarkers = {};
        
        customers.forEach(customer => {
            if (customer.latitude && customer.longitude) {
                const isActive = customer.status === 'active';
                const statusLabel = isActive ? 'ONLINE' : 'OFFLINE';
                const statusTextClass = isActive ? 'text-emerald-300' : 'text-rose-300';
                const statusBadgeClass = isActive ? 'bg-emerald-500/15 text-emerald-200 border-emerald-400/25' : 'bg-rose-500/15 text-rose-200 border-rose-400/25';
                const mapsUrl = buildMapsUrl(customer.latitude, customer.longitude);
                const waNumber = normalizePhoneForWa(customer.phone);
                const waUrl = waNumber ? `https://wa.me/${waNumber}` : '#';
                const customerSearchUrl = `{{ route('customers.index') }}?search=${encodeURIComponent(customer.name || '')}`;
                const copyIdentity = encodeURIComponent(customer.pppoe_username || customer.name || '');
                const copyFullInfo = encodeURIComponent(`${customer.name || '-'} - ${customer.phone || '-'} - ${customer.address || '-'}`);
                const customerDistance = customer.odp && customer.odp.latitude && customer.odp.longitude
                    ? getDistanceText(customer.odp.latitude, customer.odp.longitude, customer.latitude, customer.longitude)
                    : '-';
                
                const customerIcon = L.divIcon({
                    className: 'customer-pulse-icon',
                    html: `
                        <div class="customer-marker-ring ${isActive ? 'active' : 'inactive'}"></div>
                        <div class="customer-marker-dot ${isActive ? 'active' : 'inactive'}"></div>
                    `,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });

                const popupContent = `
                    <div class="network-client-popup space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-1 h-8 rounded-full ${isActive ? 'bg-emerald-400' : 'bg-rose-400'} shrink-0"></span>
                                <span class="text-sm font-extrabold text-slate-50 truncate">${escapeMapLabel(customer.name)}</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-extrabold border ${statusBadgeClass}">
                                CLIENT
                            </span>
                        </div>

                        <div class="grid grid-cols-[1fr_auto] gap-3 items-center">
                            <div class="rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2">
                                <div class="flex items-center justify-between gap-2 text-[11px]">
                                    <span class="text-slate-400">PPPoE</span>
                                    <span class="font-mono font-bold text-slate-200 truncate">${escapeMapLabel(customer.pppoe_username || '-')}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2 text-[11px] mt-1">
                                    <span class="text-slate-400">Paket</span>
                                    <span class="font-bold text-slate-200 truncate">${escapeMapLabel(customer.package ? customer.package.name : '-')}</span>
                                </div>
                            </div>
                            <button onclick="copyMapEncodedText('${copyIdentity}')" class="px-3 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-[11px] font-extrabold">Salin</button>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Status</span>
                            <span class="${statusTextClass} font-extrabold">${statusLabel}</span>
                        </div>

                        <div class="rounded-2xl border border-slate-800 bg-slate-950/55 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-extrabold text-white">Live Traffic</span>
                                <span class="inline-flex items-center gap-2 text-[10px] font-bold text-slate-400"><span class="w-2 h-2 rounded-full bg-sky-400"></span>TX <span class="w-2 h-2 rounded-full bg-emerald-400"></span>RX</span>
                            </div>
                            <svg class="traffic-sparkline" viewBox="0 0 300 78" preserveAspectRatio="none">
                                <polyline points="0,61 70,61 130,60 190,61 235,59 262,58 278,26 289,14 300,54" fill="none" stroke="${isActive ? '#60a5fa' : '#64748b'}" stroke-width="4" stroke-linecap="round"></polyline>
                                <polyline points="0,64 80,64 150,63 220,62 258,61 282,55 300,49" fill="none" stroke="${isActive ? '#34d399' : '#475569'}" stroke-width="3" stroke-linecap="round"></polyline>
                            </svg>
                            <div class="grid grid-cols-2 gap-2 mt-2 text-[11px] font-bold">
                                <span>TX: <b class="text-slate-100">${isActive ? 'sinkron PPPoE' : '-'}</b></span>
                                <span>RX: <b class="text-slate-100">${isActive ? 'sinkron PPPoE' : '-'}</b></span>
                            </div>
                        </div>

                        <div class="text-center text-cyan-300 text-xs font-extrabold">${customerDistance}</div>

                        <div class="space-y-1.5 text-[11px] text-slate-300 border-t border-slate-800 pt-2">
                            <p><strong class="text-slate-500">ODP:</strong> ${customer.odp ? escapeMapLabel(customer.odp.name) : '<span class="text-rose-300">Belum dipilih</span>'}</p>
                            <p><strong class="text-slate-500">WA:</strong> ${escapeMapLabel(customer.phone || '-')}</p>
                            <p><strong class="text-slate-500">Alamat:</strong> ${escapeMapLabel(customer.address || '-')}</p>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <button onclick="copyMapEncodedText('${copyFullInfo}')" class="popup-action bg-sky-600 text-white">Salin</button>
                            <a href="${mapsUrl}" target="_blank" class="popup-action bg-emerald-600 text-white">Maps</a>
                            <a href="${waUrl}" target="_blank" class="popup-action bg-green-600 text-white ${waNumber ? '' : 'pointer-events-none opacity-50'}">WA</a>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <a href="${customerSearchUrl}" class="popup-action bg-blue-600 text-white">EDIT</a>
                            <button onclick="openRouteEditor(${customer.id}, 'customer')" class="popup-action bg-cyan-600 text-white">RUTE</button>
                        </div>
                    </div>
                `;

                let lat = parseFloat(customer.latitude);
                let lng = parseFloat(customer.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const marker = L.marker([lat, lng], { icon: customerIcon })
                        .addTo(map)
                        .bindPopup(popupContent);
                    bindFloatingName(
                        marker,
                        customer.name || 'Pelanggan',
                        isActive ? 'customer-label' : 'customer-label inactive-label',
                        [0, -14]
                    );
                    
                    customerMarkers[customer.id] = marker;
                }
            }
        });

        // ================= KABEL / POLYLINES ROUTING =================

        let currentRoutingMode = 'udara'; // Default mode: udara (garis lurus)
        let routeLayers = []; // Array untuk melacak garis yang sudah digambar

        // Tab Switching Function (Attached to window so HTML buttons can call it)
        window.switchRoutingMode = function(mode) {
            if (currentRoutingMode === mode) return; // Ignore if already on this mode
            
            currentRoutingMode = mode;
            
            // Update UI Tab Styles
            const btnUdara = document.getElementById('tab-mode-udara');
            const btnDarat = document.getElementById('tab-mode-darat');
            
            if (mode === 'udara') {
                btnUdara.className = "px-5 py-2.5 rounded-t-xl font-bold text-sm transition-all duration-300 flex items-center gap-2 bg-indigo-600 text-white border-b-2 border-indigo-400";
                btnDarat.className = "px-5 py-2.5 rounded-t-xl font-bold text-sm transition-all duration-300 flex items-center gap-2 bg-transparent text-slate-400 hover:text-slate-200 border-b-2 border-transparent hover:border-slate-600";
            } else {
                btnDarat.className = "px-5 py-2.5 rounded-t-xl font-bold text-sm transition-all duration-300 flex items-center gap-2 bg-indigo-600 text-white border-b-2 border-indigo-400";
                btnUdara.className = "px-5 py-2.5 rounded-t-xl font-bold text-sm transition-all duration-300 flex items-center gap-2 bg-transparent text-slate-400 hover:text-slate-200 border-b-2 border-transparent hover:border-slate-600";
            }
            
            // Hapus semua garis rute yang ada di peta
            routeLayers.forEach(layer => map.removeLayer(layer));
            routeLayers = [];
            
            // Kosongkan antrean agar rute dari mode sebelumnya yang belum tergambar tidak muncul
            routeQueue.length = 0; 
            
            // Gambar ulang rute sesuai mode baru
            drawAllRoutes();
        };

        // Helper fungsi untuk menghitung jarak lurus
        function getDistanceText(lat1, lng1, lat2, lng2) {
            const p1 = L.latLng(parseFloat(lat1), parseFloat(lng1));
            const p2 = L.latLng(parseFloat(lat2), parseFloat(lng2));
            const meters = p1.distanceTo(p2);
            if (meters >= 1000) {
                return (meters / 1000).toFixed(2) + ' km';
            } else {
                return Math.round(meters) + ' meter';
            }
        }

        // Sistem Antrian Routing
        const routeQueue = [];
        let isProcessingQueue = false;

        function formatDistance(meters) {
            if (meters >= 1000) {
                return (meters / 1000).toFixed(2) + ' km';
            } else {
                return Math.round(meters) + ' meter';
            }
        }

        async function processRouteQueue() {
            if (isProcessingQueue || routeQueue.length === 0) {
                if (routeQueue.length === 0) {
                    const statusInd = document.getElementById('routing-status-indicator');
                    if (statusInd) statusInd.classList.replace('flex', 'hidden');
                }
                return;
            }
            
            isProcessingQueue = true;
            const statusInd = document.getElementById('routing-status-indicator');
            if (statusInd) statusInd.classList.replace('hidden', 'flex');

            while (routeQueue.length > 0) {
                const task = routeQueue.shift();
                
                try {
                    let pLat1 = parseFloat(task.lat1);
                    let pLng1 = parseFloat(task.lng1);
                    let pLat2 = parseFloat(task.lat2);
                    let pLng2 = parseFloat(task.lng2);

                    // Validasi koordinat agar tidak menyebabkan Leaflet crash
                    if (isNaN(pLat1) || isNaN(pLng1) || isNaN(pLat2) || isNaN(pLng2)) {
                        console.warn("Skipping route, invalid coordinates (NaN):", task);
                        continue;
                    }

                    // Update task dengan nilai float yang valid
                    task.lat1 = pLat1;
                    task.lng1 = pLng1;
                    task.lat2 = pLat2;
                    task.lng2 = pLng2;

                    let routeDrawn = false;
                    
                    if (currentRoutingMode === 'darat') {
                        try {
                            // Gabungkan koordinat untuk OSRM: lon,lat;lon,lat;...
                            let coordinates = `${task.lng1},${task.lat1}`;
                            
                            if (task.waypoints && Array.isArray(task.waypoints) && task.waypoints.length > 0) {
                                task.waypoints.forEach(wp => {
                                    coordinates += `;${wp.lng},${wp.lat}`;
                                });
                            }
                            
                            coordinates += `;${task.lng2},${task.lat2}`;

                            // Fetch route dari OSRM public API
                            const url = `https://router.project-osrm.org/route/v1/foot/${coordinates}?overview=full&geometries=geojson`;
                            const response = await fetch(url);
                            
                            if (response.ok) {
                                const data = await response.json();
                                if (data && data.code === 'Ok' && data.routes && data.routes.length > 0) {
                                    const route = data.routes[0];
                                    const geom = route.geometry;
                                    
                                    if (geom && geom.type === 'LineString' && geom.coordinates) {
                                        geom.coordinates.unshift([task.lng1, task.lat1]);
                                        geom.coordinates.push([task.lng2, task.lat2]);
                                    }

                                    const distanceText = formatDistance(route.distance);
                                    const tooltipText = `${task.tooltipLabel} (Darat: ${distanceText})`;

                                    const layer = L.geoJSON(geom, {
                                        style: function() { return task.styleOptions; }
                                    }).addTo(map).bindTooltip(tooltipText, { sticky: true });
                                    
                                    routeLayers.push(layer);
                                    routeDrawn = true;
                                }
                            }
                        } catch (fetchErr) {
                            console.warn("OSRM Routing API Error (fallback ke garis lurus):", fetchErr);
                        }
                    }

                    // Jika mode udara ATAU OSRM gagal, gambar garis putus-putus antar waypoints atau garis lurus
                    if (!routeDrawn) {
                        const fallbackDistText = getDistanceText(task.lat1, task.lng1, task.lat2, task.lng2);
                        const tooltipText = `${task.tooltipLabel} (Udara: ${fallbackDistText})`;
                        
                        let latLngs = [[task.lat1, task.lng1]];
                        if (task.waypoints && Array.isArray(task.waypoints) && task.waypoints.length > 0) {
                            task.waypoints.forEach(wp => {
                                latLngs.push([wp.lat, wp.lng]);
                            });
                        }
                        latLngs.push([task.lat2, task.lng2]);
                        
                        const layer = L.polyline(latLngs, task.styleOptions).addTo(map).bindTooltip(tooltipText, { sticky: true });
                        
                        routeLayers.push(layer);
                    }
                    
                } catch (taskErr) {
                    console.error("Gagal menggambar rute:", taskErr);
                }

                // Jeda 250ms hanya jika menggunakan OSRM untuk menghindari rate limit
                if (currentRoutingMode === 'darat') {
                    await new Promise(resolve => setTimeout(resolve, 250));
                }
            }

            isProcessingQueue = false;
            const statusIndFinal = document.getElementById('routing-status-indicator');
            if (statusIndFinal) statusIndFinal.classList.replace('flex', 'hidden');
        }

        function enqueueRoute(lat1, lng1, lat2, lng2, styleOptions, tooltipLabel, waypoints = null) {
            routeQueue.push({
                lat1: parseFloat(lat1),
                lng1: parseFloat(lng1),
                lat2: parseFloat(lat2),
                lng2: parseFloat(lng2),
                styleOptions,
                tooltipLabel,
                waypoints
            });
            processRouteQueue();
        }

        function drawAllRoutes() {
            // 1. Kabel Utama: ISPs -> Server Core (Trunk Fiber - Emas)
            isps.forEach((isp, index) => {
                if (isp && isp.latitude && isp.longitude && server && server.latitude) {
                    enqueueRoute(
                        isp.latitude, isp.longitude,
                        server.latitude, server.longitude,
                        { color: '#f59e0b', weight: 4.5, opacity: 0.95, className: 'cable-line' },
                        `Kabel Backhaul ISP ${index + 1}: ${isp.name}`
                    );
                }
            });

            // 2. Kabel Distribusi: Server Core/ODP Induk -> ODPs (Kabel Utama - Merah)
            odps.forEach(odp => {
                if (odp.latitude && odp.longitude) {
                    let startLat = server ? server.latitude : null;
                    let startLng = server ? server.longitude : null;
                    let startName = "Server Pusat";

                    if (odp.parent_odp_id) {
                        const parent = odps.find(o => o.id === odp.parent_odp_id);
                        if (parent && parent.latitude && parent.longitude) {
                            startLat = parent.latitude;
                            startLng = parent.longitude;
                            startName = parent.name;
                        }
                    }

                    if (startLat && startLng) {
                        enqueueRoute(
                            startLat, startLng,
                            odp.latitude, odp.longitude,
                            { color: '#ef4444', weight: 5.5, opacity: 0.90, className: 'cable-line' },
                            `Kabel Distribusi: ${startName} ➔ ODP ${odp.name}`,
                            odp.route_waypoints
                        );
                    }
                }
            });

            // 3. Kabel Drop: ODP -> Rumah Pelanggan
            customers.forEach(customer => {
                if (customer.latitude && customer.longitude && customer.odp && customer.odp.latitude && customer.odp.longitude) {
                    const isActive = customer.status === 'active';
                    const isFo = (customer.jalur || 'FO') === 'FO';
                    
                    const styleOptions = {
                        color: isFo ? '#10b981' : '#d946ef',
                        weight: isActive ? 2.2 : 1.2,
                        opacity: isActive ? 0.85 : 0.35,
                        className: isActive ? 'cable-line' : 'cable-line-inactive'
                    };
                    
                    const mediaLabel = isFo ? 'FO' : 'LAN';

                    enqueueRoute(
                        customer.odp.latitude, customer.odp.longitude,
                        customer.latitude, customer.longitude,
                        styleOptions,
                        `Kabel Drop Pelanggan (${mediaLabel}): ${customer.name}`,
                        customer.route_waypoints
                    );
                }
            });
        }

        // Panggil saat pertama kali load peta
        drawAllRoutes();

        // 4. Deteksi Parameter Query customer_id di URL untuk Fokus Otomatis
        const urlParams = new URLSearchParams(window.location.search);
        const focusCustomerId = urlParams.get('customer_id');
        if (focusCustomerId && customerMarkers[focusCustomerId]) {
            const targetMarker = customerMarkers[focusCustomerId];
            const targetCoords = targetMarker.getLatLng();
            
            // Pusatkan peta ke koordinat rumah pelanggan dengan zoom tinggi
            map.setView(targetCoords, 22);
            
            // Buka popup info pelanggan secara otomatis setelah delay kecil
            setTimeout(() => {
                targetMarker.openPopup();
            }, 600);
        }
    });

    // ================= LOGIKA EDITOR RUTE (WAYPOINTS) =================
    let routingControl = null;
    let currentEditId = null;
    let currentEditType = null; // 'odp' or 'customer'

    window.openRouteEditor = function(id, type) {
        if (!window.networkMap) return;
        
        currentEditId = id;
        currentEditType = type;
        
        const map = window.networkMap;
        
        // Tutup semua popup yang terbuka agar layar bersih
        map.closePopup();
        
        // Tampilkan Panel Melayang
        document.getElementById('inlineRouteEditorPanel').classList.remove('hidden');
        
        // Cari titik awal dan akhir berdasarkan tipe
        let startLat, startLng, endLat, endLng, targetName;
        let savedWaypoints = null;
        
        const odps = @json($odps);
        const customers = @json($customers);
        const server = @json($server);
        
        if (type === 'odp') {
            const odp = odps.find(o => o.id == id);
            endLat = odp.latitude;
            endLng = odp.longitude;
            savedWaypoints = odp.route_waypoints;
            targetName = "ODP " + odp.name;
            
            if (odp.parent_odp_id) {
                const parent = odps.find(o => o.id == odp.parent_odp_id);
                startLat = parent.latitude;
                startLng = parent.longitude;
            } else if (server) {
                startLat = server.latitude;
                startLng = server.longitude;
            }
        } else if (type === 'customer') {
            const customer = customers.find(c => c.id == id);
            endLat = customer.latitude;
            endLng = customer.longitude;
            savedWaypoints = customer.route_waypoints;
            targetName = "Pelanggan " + customer.name;
            
            if (customer.odp) {
                startLat = customer.odp.latitude;
                startLng = customer.odp.longitude;
            }
        }
        
        document.getElementById('inlineEditTargetName').innerText = targetName;
        
        if (!startLat || !startLng || !endLat || !endLng) {
            alert("Gagal memuat koordinat rute.");
            closeRouteEditor();
            return;
        }

        // Siapkan array waypoints untuk Routing Machine
        let routePlan = [L.latLng(startLat, startLng)];
        
        // Jika ada waypoint tersimpan, masukkan ke tengah-tengah rute
        if (savedWaypoints && Array.isArray(savedWaypoints)) {
            savedWaypoints.forEach(wp => {
                routePlan.push(L.latLng(wp.lat, wp.lng));
            });
        }
        
        routePlan.push(L.latLng(endLat, endLng));
        
        // Bersihkan control routing sebelumnya jika ada
        if (routingControl) {
            map.removeControl(routingControl);
        }
        
        // Tambahkan Routing Machine Control langsung ke PETA UTAMA
        routingControl = L.Routing.control({
            waypoints: routePlan,
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1',
                profile: 'foot' // Gunakan profil pejalan kaki agar bisa lewat gang kecil
            }),
            lineOptions: {
                styles: [{ color: '#facc15', weight: 6, opacity: 1, className: 'pulse-route' }] // Warna kuning solid agar beda
            },
            createMarker: function(i, wp, nWps) {
                // Sembunyikan marker OSRM default ujung, cuma biarkan tengah bisa di-drag
                if (i === 0 || i === nWps - 1) {
                    return L.circleMarker(wp.latLng, {
                        radius: 5,
                        color: '#fff',
                        fillColor: i === 0 ? '#ef4444' : '#3b82f6',
                        fillOpacity: 0.8,
                        weight: 2
                    });
                }
                return L.marker(wp.latLng, { draggable: true }); 
            },
            routeWhileDragging: false,
            addWaypoints: true,
            fitSelectedRoutes: false,
            showAlternatives: false,
            show: false // Sembunyikan panel instruksi teks
        }).addTo(map);
        
    };

    window.closeRouteEditor = function() {
        document.getElementById('inlineRouteEditorPanel').classList.add('hidden');
        if (routingControl && window.networkMap) {
            window.networkMap.removeControl(routingControl);
            routingControl = null;
        }
        // Muat ulang rute agar garis edit menghilang dan kembali normal
        window.location.reload();
    };

    window.saveRouteWaypoints = function() {
        if (!routingControl) return;
        
        const wps = routingControl.getWaypoints();
        // Extract waypoints bagian tengah saja (buang start dan end)
        const middleWaypoints = [];
        for (let i = 1; i < wps.length - 1; i++) {
            if (wps[i].latLng) {
                middleWaypoints.push({
                    lat: wps[i].latLng.lat,
                    lng: wps[i].latLng.lng
                });
            }
        }
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const url = `/api/route-waypoints/${currentEditType}/${currentEditId}`;
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ waypoints: middleWaypoints })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Sembunyikan editor dan reload
                document.getElementById('inlineRouteEditorPanel').classList.add('hidden');
                window.location.reload();
            } else {
                alert("Gagal menyimpan rute.");
            }
        })
        .catch(err => {
            console.error("Save error:", err);
            alert("Terjadi kesalahan saat menyimpan rute.");
        });
    };
</script>
@endsection
