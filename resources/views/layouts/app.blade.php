<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SmartBilling">
    <script>
        (function () {
            const savedTheme = localStorage.getItem('wifi_smartbilling_theme');
            const theme = savedTheme || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            document.documentElement.dataset.theme = theme;
        }());
    </script>
    <title>{{ $title ?? 'Billing RT/RW' }} - Panel Admin</title>
    @php
        $layoutCompanyFavicon = \App\Models\Setting::get('company_favicon');
    @endphp
    <link rel="icon" href="{{ $layoutCompanyFavicon ? route('settings.company-favicon') . '?v=' . md5($layoutCompanyFavicon) : asset('favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ $layoutCompanyFavicon ? route('settings.company-favicon') . '?v=' . md5($layoutCompanyFavicon) : asset('favicon.ico') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
        }

        @media (min-width: 768px) {
            body.sidebar-hidden .app-sidebar {
                width: 0 !important;
                min-width: 0;
                border-right-width: 0;
                overflow: hidden;
                box-shadow: none !important;
            }

            body.sidebar-hidden .app-sidebar > * {
                opacity: 0;
                pointer-events: none;
            }

            .app-sidebar {
                width: 15rem;
                transition: width 220ms ease, box-shadow 220ms ease;
            }

            .app-sidebar .sidebar-reveal,
            .app-sidebar nav a > span {
                opacity: 1;
                max-width: 11rem;
                overflow: hidden;
                white-space: nowrap;
                transition: opacity 160ms ease, max-width 180ms ease;
            }

            .app-sidebar .sidebar-section-title,
            .app-sidebar .sidebar-expanded-only,
            .app-sidebar nav > div,
            .app-sidebar > div.mt-auto {
                opacity: 1;
                max-height: 500px;
                overflow: hidden;
                transition: opacity 160ms ease, max-height 180ms ease, margin 180ms ease, padding 180ms ease;
            }

            .app-sidebar nav > div {
                padding-top: .5rem !important;
                padding-bottom: .5rem !important;
                margin-top: .5rem !important;
                margin-bottom: .25rem !important;
            }

            .app-sidebar > div.mt-auto {
                padding: 1rem !important;
            }

            .app-sidebar nav a {
                justify-content: flex-start;
                position: relative;
                overflow: hidden;
            }

            .app-sidebar nav a svg {
                flex-shrink: 0;
                min-width: 1.25rem;
            }

            .app-sidebar nav a[class*="bg-indigo-600"] {
                box-shadow: inset 3px 0 0 #a5b4fc, 0 10px 22px rgba(79, 70, 229, 0.18);
            }

            .app-sidebar nav a[class*="bg-indigo-600"] svg {
                color: #ffffff !important;
                filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.22));
            }

            .app-sidebar .sidebar-logo-link {
                justify-content: flex-start;
            }
        }

        .android-bottom-nav {
            display: none;
        }

        @media (max-width: 767px) {
            body {
                display: block !important;
                padding-top: 4.25rem;
                padding-bottom: calc(5.75rem + env(safe-area-inset-bottom));
                background:
                    radial-gradient(circle at top left, rgba(79, 70, 229, .16), transparent 34rem),
                    #020617;
            }

            .app-sidebar {
                display: none !important;
            }

            main {
                min-height: 100vh;
                overflow: visible !important;
            }

            main > header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 80;
                height: 4.25rem !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
                background: rgba(2, 6, 23, .92) !important;
                border-bottom: 1px solid rgba(30, 41, 59, .9);
                box-shadow: 0 16px 34px -28px rgba(15, 23, 42, .95);
            }

            main > header .uppercase {
                font-size: 9px;
                letter-spacing: .08em;
            }

            main > header h2 {
                font-size: .95rem;
            }

            main > header > div:last-child {
                gap: .5rem;
            }

            main > header > div:last-child .text-xs {
                max-width: 8.5rem;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            main > div.flex-1 {
                padding: 1rem !important;
                max-width: none !important;
                width: 100% !important;
                margin: 0 !important;
            }

            .android-bottom-nav {
                position: fixed;
                left: .75rem;
                right: .75rem;
                bottom: calc(.75rem + env(safe-area-inset-bottom));
                z-index: 90;
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: .25rem;
                padding: .45rem;
                border: 1px solid rgba(51, 65, 85, .75);
                border-radius: 1.5rem;
                background: rgba(15, 23, 42, .94);
                box-shadow: 0 18px 45px rgba(2, 6, 23, .72);
                backdrop-filter: blur(18px);
            }

            .android-bottom-nav a,
            .android-bottom-nav button {
                min-width: 0;
                height: 3.75rem;
                border-radius: 1.1rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: .25rem;
                color: #94a3b8;
                font-size: 10px;
                font-weight: 700;
                line-height: 1;
                transition: background 160ms ease, color 160ms ease, transform 160ms ease;
            }

            .android-bottom-nav a svg,
            .android-bottom-nav button svg {
                width: 1.15rem;
                height: 1.15rem;
            }

            .android-bottom-nav a.is-active {
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
                color: #fff;
                box-shadow: 0 10px 22px rgba(79, 70, 229, .28);
            }
        }
    </style>

    <!-- Vite Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col md:flex-row antialiased selection:bg-indigo-500 selection:text-white">
    @php
        $route = Route::currentRouteName();
    @endphp

    <!-- Sidebar -->
    <aside class="app-sidebar w-full md:w-auto bg-slate-900/50 backdrop-blur-xl border-b md:border-b-0 md:border-r border-slate-800/60 flex flex-col shrink-0">
        <!-- Logo Header -->
        <div class="h-16 flex items-center px-4 border-b border-slate-800/60 justify-between">
            @php
                $companyLogo = \App\Models\Setting::get('company_logo');
                $companyName = \App\Models\Setting::get('company_name', 'Billing RT/RW');
            @endphp
            <a href="{{ route('dashboard') }}" class="sidebar-logo-link flex items-center gap-2.5">
                @if($companyLogo)
                    <img src="{{ route('settings.company-logo') . '?v=' . md5($companyLogo) }}" class="w-9 h-9 rounded-xl object-cover shadow-lg shadow-indigo-500/20">
                @else
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                @endif
                <div class="sidebar-reveal">
                    <h1 class="font-bold text-base leading-tight tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-100 to-slate-300">{{ $companyName }}</h1>
                    <span class="text-[10px] text-indigo-400 font-semibold uppercase tracking-wider">Premium Panel</span>
                </div>
            </a>
            
            <!-- Mobile Menu Toggle Button (handled via simple js inline) -->
            <button onclick="document.getElementById('mobile-nav').classList.toggle('hidden')" class="md:hidden text-slate-400 hover:text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links (Desktop) -->
        <nav class="hidden md:flex flex-col gap-1 p-3 flex-1 overflow-y-auto">
            @php
                $route = Route::currentRouteName();
            @endphp

            @if(auth()->check() && auth()->user()->role === 'client')
                <div class="px-4 py-2 mt-2 mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Portal Pelanggan</div>
                
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ $route === 'dashboard' ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ $route === 'dashboard' ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'invoices') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'invoices') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <span>Tagihan Saya</span>
                </a>

                <a href="{{ route('tickets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'tickets') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'tickets') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span>Pusat Bantuan</span>
                </a>
            @endif

            @if(Auth::check() && Auth::user()->isSaasOwner())
                <!-- SaaS Workspace Indicator -->
                <div class="mx-4 mt-2 mb-4 p-3 rounded-xl bg-gradient-to-r from-indigo-500/10 to-purple-500/10 border border-indigo-500/20">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest">Pusat Kendali</p>
                            <p class="text-sm font-semibold text-slate-200">SaaS Induk</p>
                        </div>
                        <button type="button" id="saas-menu-toggle" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-indigo-500/20 bg-slate-950/30 text-indigo-300 hover:bg-indigo-500/20 hover:text-white transition-colors" title="Ringkas menu SaaS" aria-label="Ringkas menu SaaS" aria-expanded="true">
                            <svg id="saas-menu-minus-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
                            <svg id="saas-menu-plus-icon" class="hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                        </button>
                    </div>
                    
                    <div id="saas-menu-links" class="space-y-1">
                        <a href="{{ route('saas.dashboard') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition-all duration-300 {{ $route === 'saas.dashboard' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="text-xs font-medium">Dashboard SaaS</span>
                        </a>
                        <a href="{{ route('saas.tenants.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition-all duration-300 {{ str_contains($route, 'saas.tenants') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="text-xs font-medium">Perusahaan Klien</span>
                        </a>
                        <a href="{{ route('saas.ppob.suppliers.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition-all duration-300 {{ str_contains($route, 'saas.ppob.suppliers') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            <span class="text-xs font-medium">API Suplier PPOB</span>
                        </a>
                        <a href="{{ route('saas.ppob.products.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition-all duration-300 {{ str_contains($route, 'saas.ppob.products') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <span class="text-xs font-medium">Master Produk PPOB</span>
                        </a>
                        <a href="{{ route('saas.ppob.transactions.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition-all duration-300 {{ str_contains($route, 'saas.ppob.transactions') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span class="text-xs font-medium">Global Transaksi PPOB</span>
                        </a>
                        <a href="{{ route('saas.invoices.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition-all duration-300 {{ str_contains($route, 'saas.invoices') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-xs font-medium">Tagihan Sewa (SaaS)</span>
                        </a>
                        <a href="{{ route('saas.settings.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition-all duration-300 {{ str_contains($route, 'saas.settings') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span class="text-xs font-medium">Pengaturan Gateway</span>
                        </a>
                    </div>
                </div>

                <div class="px-4 py-2 mt-4 mb-1 text-xs font-bold text-slate-500 uppercase tracking-widest border-t border-slate-800/60 pt-6">Ruang Kerja: {{ Auth::user()->tenant->name ?? 'Default' }}</div>
            @endif
            
            @can('dashboard')
                <div class="px-4 py-2 mt-2 mb-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Dashboard & Monitor</div>
                
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ $route === 'dashboard' ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ $route === 'dashboard' ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
                    </svg>
                    <span>Dashboard</span>
                </a>
            @endcan

            @can('network_map')
                <a href="{{ route('network-map') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'network-map') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'network-map') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <span>Peta Jaringan</span>
                </a>
            @endcan
            
            <div class="px-4 py-2 mt-4 mb-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-t border-slate-800/60 pt-4">Layanan & Klien</div>
            
            @can('customers')
                <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'customers') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'customers') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Pelanggan</span>
                </a>
                <a href="{{ route('installation-requests.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'installation-requests') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'installation-requests') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M7 21h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2zm5-7h.01M9 17h6" />
                    </svg>
                    <span>Calon Pasang</span>
                </a>
            @endcan

            @can('packages')
                <a href="{{ route('packages.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'packages') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>Paket Layanan</span>
                </a>
            @endcan
            
            @can('dashboard')
                <a href="{{ route('tickets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'tickets') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'tickets') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span>Tiket Gangguan</span>
                    @php
                        $openTickets = \App\Models\Ticket::where('tenant_id', auth()->user()->tenant_id)->where('status', 'open')->count();
                    @endphp
                    @if($openTickets > 0)
                        <span class="ml-auto bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $openTickets }}</span>
                    @endif
                </a>
            @endcan

            <div class="px-4 py-2 mt-4 mb-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-t border-slate-800/60 pt-4">Infrastruktur</div>

            @can('packages')
                <a href="{{ route('odps.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'odps') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>Manajemen ODP</span>
                </a>
                <a href="{{ route('routers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'routers') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                    <span>Router MikroTik</span>
                </a>
                <a href="{{ route('olts.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'olts') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M9 18h6" /></svg>
                    <span>Manajemen OLT</span>
                </a>
                <a href="{{ route('pppoe.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'pppoe') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Server PPPoE</span>
                </a>
            @endcan

            @can('device_stocks')
                <a href="{{ route('device-stocks.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'device-stocks') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'device-stocks') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Stok Perangkat</span>
                </a>
            @endcan

            @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin']))
                <div class="px-4 py-2 mt-4 mb-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-t border-slate-800/60 pt-4">Layanan PPOB</div>
                
                <a href="{{ route('admin.ppob.markup.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ request()->is('admin/ppob/markup*') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Katalog & Markup</span>
                </a>

                <a href="{{ route('admin.ppob.transactions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ request()->is('admin/ppob/transactions*') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Riwayat Transaksi</span>
                </a>
            @endif

            <div class="px-4 py-2 mt-4 mb-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-t border-slate-800/60 pt-4">Keuangan & Tagihan</div>

            @can('invoices')
                <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'invoices') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'invoices') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Tagihan & Invoice</span>
                </a>
            @endcan
            
            @can('expenses')
                <a href="{{ route('expenses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'expenses') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'expenses') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Pengeluaran Kas</span>
                </a>
            @endcan

            @can('financial_reports')
                <a href="{{ route('financial-reports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'financial-reports') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'financial-reports') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                    <span>Laporan Keuangan</span>
                </a>
            @endcan

            <div class="px-4 py-2 mt-4 mb-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-t border-slate-800/60 pt-4">Sistem & Pengaturan</div>
            
            <a href="{{ route('admin.saas_invoices.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'saas_invoices') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'saas_invoices') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Tagihan Langganan</span>
            </a>

            @can('settings')
                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group {{ str_contains($route, 'settings') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-100' }}">
                    <svg class="w-5 h-5 transition-colors group-hover:text-indigo-400 {{ str_contains($route, 'settings') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Pengaturan</span>
                </a>
            @endcan
        </nav>

        <!-- Mobile Nav Dropdown -->
        <div id="mobile-nav" class="hidden md:hidden border-t border-slate-850 p-4 bg-slate-900/90 flex flex-col gap-1">
            @can('dashboard')
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ $route === 'dashboard' ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Dashboard</a>
            @endcan
            @can('customers')
                <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'customers') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Pelanggan</a>
                <a href="{{ route('installation-requests.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'installation-requests') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Calon Pasang</a>
            @endcan
            @can('packages')
                <a href="{{ route('packages.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'packages') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Paket Layanan</a>
            @endcan
            @can('invoices')
                <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'invoices') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Tagihan & Invoice</a>
            @endcan
            @can('odps')
                <a href="{{ route('odps.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'odps') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Manajemen ODP</a>
            @endcan
            @can('olts')
                <a href="{{ route('olts.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'olts') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Manajemen OLT</a>
            @endcan
            @can('network_map')
                <a href="{{ route('network-map') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'network-map') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Peta Jaringan</a>
            @endcan
            @can('device_stocks')
                <a href="{{ route('device-stocks.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'device-stocks') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Stok Perangkat</a>
            @endcan
            @can('expenses')
                <a href="{{ route('expenses.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'expenses') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Pengeluaran Kas</a>
            @endcan
            @can('financial_reports')
                <a href="{{ route('financial-reports.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'financial-reports') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Laporan Keuangan</a>
            @endcan

            @if(Auth::check() && Auth::user()->isSaasOwner())
                <div class="mt-3 pt-3 border-t border-slate-800 text-[10px] font-bold text-indigo-400 uppercase tracking-widest px-4">Pusat Kendali SaaS</div>
                <a href="{{ route('saas.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ $route === 'saas.dashboard' ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Dashboard SaaS</a>
                <a href="{{ route('saas.tenants.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'saas.tenants') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Perusahaan Klien</a>
                <a href="{{ route('saas.ppob.transactions.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'saas.ppob.transactions') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Global Transaksi PPOB</a>
                <a href="{{ route('saas.invoices.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'saas.invoices') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Tagihan Sewa SaaS</a>
                <a href="{{ route('saas.settings.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'saas.settings') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Pengaturan Gateway SaaS</a>
            @endif

            @can('settings')
                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ str_contains($route, 'settings') ? 'bg-indigo-650 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/50' }}">Pengaturan</a>
            @endcan
            
            <form action="{{ url('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="w-full text-left flex items-center gap-3 px-4 py-2.5 rounded-lg text-rose-400 hover:bg-rose-500/10 transition-colors">
                    Keluar / Logout
                </button>
            </form>
        </div>

        <!-- Footer / Admin Info & Logout -->
        <div class="mt-auto p-3 border-t border-slate-800/60 hidden md:block">
            <div class="flex items-center justify-between gap-2 p-2 rounded-xl bg-slate-950/40 border border-slate-800/40">
                <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 overflow-hidden hover:bg-slate-800/30 p-1 -m-1 rounded-lg transition-colors cursor-pointer w-full">
                    <div class="w-8 h-8 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-center text-indigo-400 font-semibold text-xs overflow-hidden shrink-0">
                        @if(Auth::check() && Auth::user()->photo)
                            <img src="{{ route('settings.users.photo', Auth::id()) . '?v=' . md5(Auth::user()->photo) }}" class="w-full h-full object-cover">
                        @else
                            {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'AD' }}
                        @endif
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-xs font-semibold text-slate-200 truncate group-hover:text-indigo-400 transition-colors">{{ Auth::check() ? Auth::user()->name : 'Admin RT/RW' }}</h4>
                        <span class="text-[9px] text-slate-400 font-medium truncate block">{{ Auth::check() ? Auth::user()->email : 'admin@rtrw.net' }}</span>
                    </div>
                </a>
                <form action="{{ url('logout') }}" method="POST" class="inline shrink-0">
                    @csrf
                    <button type="submit" class="p-1.5 hover:bg-rose-500/10 border border-transparent hover:border-rose-500/20 rounded-lg text-slate-500 hover:text-rose-400 transition-colors" title="Logout">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col overflow-y-auto min-w-0">
        <!-- Top Bar Header -->
        <header class="h-16 border-b border-slate-800/60 flex items-center justify-between px-4 md:px-6 bg-slate-900/10 backdrop-blur-sm shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <button
                    type="button"
                    id="sidebar-toggle"
                    class="hidden md:inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-800 bg-slate-950/60 text-slate-400 hover:border-indigo-500/40 hover:bg-indigo-500/10 hover:text-white transition-colors"
                    title="Tampilkan / sembunyikan sidebar"
                    aria-label="Tampilkan atau sembunyikan sidebar"
                    aria-expanded="true">
                    <svg id="sidebar-toggle-hide-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7" />
                    </svg>
                    <svg id="sidebar-toggle-show-icon" class="hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7" />
                    </svg>
                </button>
                <div class="min-w-0">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Aplikasi Tagihan RT/RW</span>
                    <h2 class="text-base font-bold text-slate-100 leading-tight truncate">Welcome, {{ Auth::check() ? Auth::user()->name : 'Admin' }}</h2>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button type="button" id="theme-toggle" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-800 bg-slate-950/60 text-slate-400 hover:border-indigo-500/40 hover:bg-indigo-500/10 hover:text-white transition-colors" title="Ganti mode terang/gelap" aria-label="Ganti mode terang/gelap" aria-pressed="false">
                    <svg id="theme-icon-moon" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                    </svg>
                    <svg id="theme-icon-sun" class="hidden w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3.5" stroke-width="1.8" />
                        <path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3l1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3l1.42-1.42" />
                    </svg>
                </button>
                @if(Auth::check() && Auth::user()->tenant && Auth::user()->tenant->subscription_status === 'trial')
                    @php
                        $daysLeft = round(now()->diffInDays(Auth::user()->tenant->trial_ends_at, false));
                    @endphp
                    <a href="https://wa.me/6281234567890?text=Halo%20Admin,%20saya%20ingin%20berlangganan" target="_blank" class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 hover:bg-indigo-500/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-xs font-bold">Sisa Trial: {{ max($daysLeft, 0) }} Hari</span>
                    </a>
                @endif
                <!-- Current Date Indicator -->
                <div class="text-xs text-slate-400 font-medium bg-slate-900/60 border border-slate-800/60 px-3 py-1.5 rounded-lg hidden sm:flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span>{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <div class="flex-1 p-4 md:p-6 max-w-7xl w-full mx-auto space-y-5">
            
            <!-- Toast Notifications -->
            @if (session('success'))
                <div id="toast-success" class="flex items-center gap-3 p-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-300 shadow-lg shadow-emerald-950/10 animate-fade-in">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm font-medium">{{ session('success') }}</div>
                    <button onclick="document.getElementById('toast-success').remove()" class="ml-auto text-emerald-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div id="toast-error" class="flex items-center gap-3 p-3 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-300 shadow-lg shadow-rose-950/10 animate-fade-in">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-sm font-medium">{{ session('error') }}</div>
                    <button onclick="document.getElementById('toast-error').remove()" class="ml-auto text-rose-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            @endif

            @if (session('warning'))
                <div id="toast-warning" class="flex items-center gap-3 p-3 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-300 shadow-lg shadow-amber-950/10 animate-fade-in">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <div class="text-sm font-medium">{{ session('warning') }}</div>
                    @if (session('wa_manual_url'))
                        <a href="{{ session('wa_manual_url') }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center justify-center rounded-lg border border-amber-400/30 bg-amber-400/10 px-3 py-1.5 text-xs font-bold text-amber-100 hover:bg-amber-400 hover:text-slate-950">
                            Kirim Manual
                        </a>
                    @endif
                    <button onclick="document.getElementById('toast-warning').remove()" class="ml-1 text-amber-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            @endif

            @if (session('info'))
                <div id="toast-info" class="flex items-center gap-3 p-3 rounded-xl border border-indigo-500/30 bg-indigo-500/10 text-indigo-300 shadow-lg shadow-indigo-950/10 animate-fade-in">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm font-medium">{{ session('info') }}</div>
                    <button onclick="document.getElementById('toast-info').remove()" class="ml-auto text-indigo-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            @endif

            <!-- Main Yield -->
            @yield('content')
            
        </div>
    </main>

    <nav class="android-bottom-nav" aria-label="Navigasi utama mobile">
        @can('dashboard')
            <a href="{{ route('dashboard') }}" class="{{ $route === 'dashboard' ? 'is-active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
                <span>Home</span>
            </a>
        @endcan
        @can('customers')
            <a href="{{ route('customers.index') }}" class="{{ str_contains($route, 'customers') ? 'is-active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H2v-2a4 4 0 014-4h3m4-5a4 4 0 11-8 0 4 4 0 018 0m8 0a3 3 0 11-6 0 3 3 0 016 0"/></svg>
                <span>Klien</span>
            </a>
        @endcan
        @can('invoices')
            <a href="{{ route('invoices.index') }}" class="{{ str_contains($route, 'invoices') ? 'is-active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14h6m-6 4h6M7 3h7l5 5v13H7z"/></svg>
                <span>Tagihan</span>
            </a>
        @endcan
        @can('network_map')
            <a href="{{ route('network-map') }}" class="{{ str_contains($route, 'network-map') ? 'is-active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3zM9 3v15m6-12v15"/></svg>
                <span>Peta</span>
            </a>
        @endcan
        @can('dashboard')
            <a href="{{ route('tickets.index') }}" class="{{ str_contains($route, 'tickets') ? 'is-active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 10a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7M9 21h6"/></svg>
                <span>Tiket</span>
            </a>
        @endcan
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const storageKey = 'wifi_smartbilling_sidebar_hidden';
            const toggleButton = document.getElementById('sidebar-toggle');
            const hideIcon = document.getElementById('sidebar-toggle-hide-icon');
            const showIcon = document.getElementById('sidebar-toggle-show-icon');

            function setSidebarHidden(hidden) {
                document.body.classList.toggle('sidebar-hidden', hidden);
                if (toggleButton) {
                    toggleButton.setAttribute('aria-expanded', hidden ? 'false' : 'true');
                    toggleButton.title = hidden ? 'Tampilkan sidebar' : 'Sembunyikan sidebar';
                }
                if (hideIcon && showIcon) {
                    hideIcon.classList.toggle('hidden', hidden);
                    showIcon.classList.toggle('hidden', !hidden);
                }
                localStorage.setItem(storageKey, hidden ? '1' : '0');
            }

            setSidebarHidden(localStorage.getItem(storageKey) === '1');

            if (toggleButton) {
                toggleButton.addEventListener('click', function () {
                    setSidebarHidden(!document.body.classList.contains('sidebar-hidden'));
                });
            }

            const saasStorageKey = 'wifi_smartbilling_saas_menu_minimized';
            const saasToggleButton = document.getElementById('saas-menu-toggle');
            const saasLinks = document.getElementById('saas-menu-links');
            const saasMinusIcon = document.getElementById('saas-menu-minus-icon');
            const saasPlusIcon = document.getElementById('saas-menu-plus-icon');

            function setSaasMenuMinimized(minimized) {
                if (!saasLinks) return;

                saasLinks.classList.toggle('hidden', minimized);
                if (saasToggleButton) {
                    saasToggleButton.setAttribute('aria-expanded', minimized ? 'false' : 'true');
                    saasToggleButton.title = minimized ? 'Tampilkan menu SaaS' : 'Ringkas menu SaaS';
                }
                if (saasMinusIcon && saasPlusIcon) {
                    saasMinusIcon.classList.toggle('hidden', minimized);
                    saasPlusIcon.classList.toggle('hidden', !minimized);
                }
                localStorage.setItem(saasStorageKey, minimized ? '1' : '0');
            }

            setSaasMenuMinimized(localStorage.getItem(saasStorageKey) === '1');

            if (saasToggleButton) {
                saasToggleButton.addEventListener('click', function () {
                    setSaasMenuMinimized(!saasLinks.classList.contains('hidden'));
                });
            }

            const themeToggle = document.getElementById('theme-toggle');
            const moonIcon = document.getElementById('theme-icon-moon');
            const sunIcon = document.getElementById('theme-icon-sun');

            function setTheme(theme) {
                const isLight = theme === 'light';
                document.documentElement.dataset.theme = isLight ? 'light' : 'dark';
                if (themeToggle) themeToggle.setAttribute('aria-pressed', isLight ? 'true' : 'false');
                if (moonIcon && sunIcon) {
                    moonIcon.classList.toggle('hidden', isLight);
                    sunIcon.classList.toggle('hidden', !isLight);
                }
                localStorage.setItem('wifi_smartbilling_theme', isLight ? 'light' : 'dark');
            }

            setTheme(document.documentElement.dataset.theme || 'dark');
            if (themeToggle) {
                themeToggle.addEventListener('click', function () {
                    setTheme(document.documentElement.dataset.theme === 'light' ? 'dark' : 'light');
                });
            }

            if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            }
        });
    </script>
</body>
</html>
