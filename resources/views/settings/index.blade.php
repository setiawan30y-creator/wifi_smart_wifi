@extends('layouts.app')

@section('content')
<style>
    .settings-page .settings-card {
        border-radius: .9rem !important;
        border: 1px solid color-mix(in oklab, var(--neutral-600) 42%, transparent) !important;
        background: color-mix(in oklab, var(--neutral-900) 72%, transparent) !important;
        padding: 1rem !important;
    }

    .settings-page .settings-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        border-bottom: 1px solid color-mix(in oklab, var(--neutral-700) 65%, transparent);
        padding-bottom: .55rem;
    }

    .settings-page .settings-card-toggle {
        display: inline-flex;
        height: 1.8rem;
        width: 1.8rem;
        align-items: center;
        justify-content: center;
        border-radius: .55rem;
        border: 1px solid color-mix(in oklab, var(--neutral-600) 70%, transparent);
        background: color-mix(in oklab, var(--neutral-950) 55%, transparent);
        color: var(--neutral-400);
        transition: background-color 150ms ease, color 150ms ease, border-color 150ms ease;
    }

    .settings-page .settings-card-toggle:hover {
        border-color: color-mix(in oklab, var(--brand-400) 55%, transparent);
        background: color-mix(in oklab, var(--brand-600) 16%, transparent);
        color: #fff;
    }

    .settings-page .settings-card.is-collapsed [data-card-body] {
        display: none;
    }

    .settings-page .settings-card.is-collapsed .settings-card-header {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .settings-page .settings-card.is-collapsed .card-minus-icon {
        display: none;
    }

    .settings-page .settings-card:not(.is-collapsed) .card-plus-icon {
        display: none;
    }

    .settings-page .settings-upload-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    @media (max-width: 640px) {
        .settings-page .settings-upload-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="settings-page space-y-6 animate-fade-in">
    <!-- Header Section -->
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-white">Pengaturan Sistem</h1>
        <p class="text-xs text-slate-400 mt-1">Konfigurasi profil perusahaan, pengelolaan staf pengguna, kredensial akun, serta pembatasan hak akses sistem.</p>
    </div>

    <!-- Aligned Navigation Menu (Tabs) -->
    <div class="border-b border-slate-800/80 flex flex-wrap gap-2">
        <button onclick="switchTab('tab-company')" id="btn-tab-company" class="tab-btn px-5 py-3 border-b-2 font-semibold text-sm transition-all duration-300 border-indigo-500 text-white">
            Profil Perusahaan
        </button>
        <button onclick="switchTab('tab-users')" id="btn-tab-users" class="tab-btn px-5 py-3 border-b-2 font-semibold text-sm transition-all duration-300 border-transparent text-slate-400 hover:text-slate-200">
            Pengguna (Profil Staf)
        </button>
        <button onclick="switchTab('tab-accounts')" id="btn-tab-accounts" class="tab-btn px-5 py-3 border-b-2 font-semibold text-sm transition-all duration-300 border-transparent text-slate-400 hover:text-slate-200">
            Akun Pengguna & Role
        </button>
        <button onclick="switchTab('tab-permissions')" id="btn-tab-permissions" class="tab-btn px-5 py-3 border-b-2 font-semibold text-sm transition-all duration-300 border-transparent text-slate-400 hover:text-slate-200">
            Pengaturan Hak Akses
        </button>
        @can('wa_templates')
        <button onclick="switchTab('tab-wa-templates')" id="btn-tab-wa-templates" class="tab-btn px-5 py-3 border-b-2 font-semibold text-sm transition-all duration-300 border-transparent text-slate-400 hover:text-slate-200">
            Template WhatsApp
        </button>
        @endcan
        <button onclick="switchTab('tab-appearance')" id="btn-tab-appearance" class="tab-btn px-5 py-3 border-b-2 font-semibold text-sm transition-all duration-300 border-transparent text-slate-400 hover:text-slate-200">
            Tampilan &amp; Warna
        </button>
        <button onclick="switchTab('tab-traffic')" id="btn-tab-traffic" class="tab-btn px-5 py-3 border-b-2 font-semibold text-sm transition-all duration-300 border-transparent text-slate-400 hover:text-slate-200">
            Trafik Dashboard
        </button>
    </div>

    <!-- TAB 1: Profil Perusahaan -->
    <div id="tab-company" class="tab-content block">
        <div class="max-w-6xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl p-4 md:p-5">
            <h3 class="text-base font-bold text-white mb-4">Informasi Profil Perusahaan & Titik Koordinat Jaringan</h3>
            <form action="{{ route('settings.company') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left: Company Profile Info (lg:col-span-5) -->
                    <div class="lg:col-span-5 space-y-6">
                        <div class="settings-card bg-slate-950/20 border border-slate-850 p-5 rounded-2xl space-y-4" data-settings-card="company-identity">
                            <div class="settings-card-header">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-400">Identitas & Kontak</h4>
                                <button type="button" class="settings-card-toggle" data-toggle-settings-card="company-identity" title="Minimize / buka kartu" aria-label="Minimize atau buka kartu identitas">
                                    <svg class="card-minus-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
                                    <svg class="card-plus-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                            
                            <div data-card-body class="space-y-4">
                                <div class="settings-upload-grid">
                                    <!-- Company Logo Upload & Preview -->
                                    <div class="space-y-3 flex flex-col items-center rounded-xl border border-slate-800 bg-slate-950/30 p-3">
                                        <div class="w-24 h-24 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center overflow-hidden relative group shadow-inner">
                                            <img id="company-logo-preview" src="{{ $companyLogo ? route('settings.company-logo') . '?v=' . md5($companyLogo) : '' }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 {{ $companyLogo ? '' : 'hidden' }}">
                                            <div id="company-logo-placeholder" class="text-slate-650 flex flex-col items-center justify-center gap-1.5 p-3 {{ $companyLogo ? 'hidden' : 'flex' }}">
                                                <svg class="w-7 h-7 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v14.25A2.25 2.25 0 005.25 19.5h13.5A2.25 2.25 0 0021 17.25V6.75A2.25 2.25 0 0018.75 4.5H5.25A2.25 2.25 0 003 6.75zM12 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm5.25 4.5h-10.5m10.5-3h-10.5M12 9v6" /></svg>
                                                <span class="text-[8px] text-slate-500 font-semibold font-mono text-center">BELUM ADA LOGO</span>
                                            </div>
                                        </div>
                                        <label class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl text-[10px] font-semibold text-slate-200 cursor-pointer transition-colors shadow">
                                            Pilih Foto Logo
                                            <input type="file" name="company_logo" accept="image/*" class="hidden" onchange="previewImage(this, 'company-logo-preview', 'company-logo-placeholder')">
                                        </label>
                                        <p class="text-[10px] text-slate-500 text-center leading-relaxed">Logo tampil di sidebar dan dokumen. Disarankan PNG/JPG kotak, maksimal 2 MB.</p>
                                    </div>

                                    <!-- Favicon Upload & Preview -->
                                    <div class="space-y-3 flex flex-col items-center rounded-xl border border-slate-800 bg-slate-950/30 p-3">
                                        <div class="w-16 h-16 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center overflow-hidden relative group shadow-inner">
                                            <img id="company-favicon-preview" src="{{ $companyFavicon ? route('settings.company-favicon') . '?v=' . md5($companyFavicon) : '' }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 {{ $companyFavicon ? '' : 'hidden' }}">
                                            <div id="company-favicon-placeholder" class="text-slate-650 flex flex-col items-center justify-center gap-1 p-2 {{ $companyFavicon ? 'hidden' : 'flex' }}">
                                                <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h13.5m-13.5 7.5h13.5m-12-12h10.5A2.25 2.25 0 0119.5 6v12A2.25 2.25 0 0117.25 20.25H6.75A2.25 2.25 0 014.5 18V6A2.25 2.25 0 016.75 3.75z" /></svg>
                                                <span class="text-[8px] text-slate-500 font-semibold font-mono text-center">FAVICON</span>
                                            </div>
                                        </div>
                                        <label class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl text-[10px] font-semibold text-slate-200 cursor-pointer transition-colors shadow">
                                            Pilih Favicon
                                            <input type="file" name="company_favicon" accept=".ico,image/png,image/jpeg,image/webp" class="hidden" onchange="previewImage(this, 'company-favicon-preview', 'company-favicon-placeholder')">
                                        </label>
                                        <p class="text-[10px] text-slate-500 text-center leading-relaxed">Ikon kecil di tab browser. Disarankan 32x32 atau 64x64 PNG/ICO, maksimal 1 MB.</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Nama Perusahaan / RT/RW</label>
                                    <input type="text" name="company_name" value="{{ $companyName }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Billing RT/RW Premium">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Nomor Telepon Kontak</label>
                                    <input type="text" name="company_phone" value="{{ $companyPhone }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="081234567890">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Alamat Kantor / Domisili</label>
                                    <textarea name="company_address" rows="2" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Alamat lengkap ...">{{ $companyAddress }}</textarea>
                                </div>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp Gateway Configuration Card -->
                        <div class="settings-card bg-slate-950/20 border border-slate-850 p-5 rounded-2xl space-y-4" data-settings-card="wa-gateway">
                            <div class="settings-card-header">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-lg bg-gradient-to-tr from-green-600 to-green-400 flex items-center justify-center text-white shrink-0 shadow-sm shadow-green-500/20">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                    </span>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-green-400">Integrasi WhatsApp Gateway</h4>
                                </div>
                                <button type="button" class="settings-card-toggle" data-toggle-settings-card="wa-gateway" title="Minimize / buka kartu" aria-label="Minimize atau buka kartu WhatsApp Gateway">
                                    <svg class="card-minus-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
                                    <svg class="card-plus-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                            
                            <div data-card-body class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Provider Gateway</label>
                                        <select name="wa_gateway_provider" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                            <option value="fonnte" {{ $waGatewayProvider === 'fonnte' ? 'selected' : '' }}>Fonnte</option>
                                            <option value="custom" {{ $waGatewayProvider === 'custom' ? 'selected' : '' }}>Custom / Gateway Lain</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Nomor WA Admin (Teknisi)</label>
                                        <input type="text" name="admin_whatsapp" value="{{ $adminWhatsapp }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: 08123456789">
                                        <p class="text-[10px] text-slate-500 mt-1">Untuk menerima notifikasi klien mati (Down) dari MikroTik.</p>
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Token / API Key</label>
                                        <input type="text" name="wa_gateway_token" value="{{ $waGatewayToken }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Token API gateway">
                                        <input type="hidden" name="fonnte_token" value="{{ $waGatewayToken }}">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Endpoint Kirim Pesan</label>
                                        <input type="url" name="wa_gateway_endpoint" value="{{ $waGatewayEndpoint }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="https://api.provider.com/send">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Nama Header Token</label>
                                        <input type="text" name="wa_gateway_auth_header" value="{{ $waGatewayAuthHeader }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Authorization">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Prefix Token</label>
                                        <input type="text" name="wa_gateway_auth_prefix" value="{{ $waGatewayAuthPrefix }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Bearer atau kosong">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Format Payload</label>
                                        <select name="wa_gateway_payload_format" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                            <option value="multipart" {{ $waGatewayPayloadFormat === 'multipart' ? 'selected' : '' }}>multipart/form-data</option>
                                            <option value="form" {{ $waGatewayPayloadFormat === 'form' ? 'selected' : '' }}>x-www-form-urlencoded</option>
                                            <option value="json" {{ $waGatewayPayloadFormat === 'json' ? 'selected' : '' }}>JSON</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Field Nomor Tujuan</label>
                                        <input type="text" name="wa_gateway_target_field" value="{{ $waGatewayTargetField }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="target atau phone">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Field Isi Pesan</label>
                                        <input type="text" name="wa_gateway_message_field" value="{{ $waGatewayMessageField }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="message atau text">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Success Key</label>
                                        <input type="text" name="wa_gateway_success_key" value="{{ $waGatewaySuccessKey }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="status">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Success Value</label>
                                        <input type="text" name="wa_gateway_success_value" value="{{ $waGatewaySuccessValue }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="true / success / 1">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Payload Tambahan (JSON)</label>
                                    <textarea name="wa_gateway_extra_payload" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono" placeholder='{"countryCode":"62"}'>{{ $waGatewayExtraPayload }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Dokumentasi WA Gateway</label>
                                    <textarea name="wa_gateway_documentation" rows="5" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono" placeholder="Catatan dokumentasi API, contoh payload, header, dan link dashboard provider.">{{ $waGatewayDocumentation }}</textarea>
                                </div>
                            </div>

                            <!-- Payment Gateway (Tripay) -->
                            <div class="mt-8 border-t border-slate-800/60 pt-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                    </span>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-400">Tripay Payment Gateway (QRIS)</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">API Key</label>
                                        <input type="text" name="tripay_api_key" value="{{ $tripayApiKey }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Tripay API Key">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Private Key</label>
                                        <input type="password" name="tripay_private_key" value="{{ $tripayPrivateKey }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Tripay Private Key">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Merchant Code</label>
                                        <input type="text" name="tripay_merchant_code" value="{{ $tripayMerchantCode }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: T12345">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Environment</label>
                                        <select name="tripay_environment" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                            <option value="sandbox" {{ $tripayEnvironment == 'sandbox' ? 'selected' : '' }}>Sandbox (Uji Coba)</option>
                                            <option value="production" {{ $tripayEnvironment == 'production' ? 'selected' : '' }}>Production (Asli)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Network Coordinates Settings (lg:col-span-7) -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- ISP Gateways Configuration Card -->
                        <div class="settings-card bg-slate-950/20 border border-slate-850 p-5 rounded-2xl space-y-6" data-settings-card="isp-gateway">
                            <div class="settings-card-header">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-lg bg-gradient-to-tr from-amber-600 to-amber-400 flex items-center justify-center text-white shrink-0 shadow-sm shadow-amber-500/20">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                                            <path d="M2 12h20"></path>
                                        </svg>
                                    </span>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-amber-400">Koordinat ISP Gateway (Maks 3)</h4>
                                </div>
                                <button type="button" class="settings-card-toggle" data-toggle-settings-card="isp-gateway" title="Minimize / buka kartu" aria-label="Minimize atau buka kartu ISP Gateway">
                                    <svg class="card-minus-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
                                    <svg class="card-plus-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>

                            <div data-card-body>
                            @php
                                $isps = [
                                    1 => ['name' => $networkIspName1, 'lat' => $networkIspLatitude1, 'lng' => $networkIspLongitude1, 'desc' => $networkIspDescription1, 'label' => 'Utama'],
                                    2 => ['name' => $networkIspName2, 'lat' => $networkIspLatitude2, 'lng' => $networkIspLongitude2, 'desc' => $networkIspDescription2, 'label' => 'Cadangan 1'],
                                    3 => ['name' => $networkIspName3, 'lat' => $networkIspLatitude3, 'lng' => $networkIspLongitude3, 'desc' => $networkIspDescription3, 'label' => 'Cadangan 2']
                                ];
                            @endphp

                            <div class="space-y-6">
                                @foreach($isps as $i => $isp)
                                <div class="space-y-3 {{ $i > 1 ? 'pt-4 border-t border-slate-800/60' : '' }}">
                                    <h5 class="text-[10px] font-bold text-slate-300 uppercase flex items-center gap-2">
                                        <span class="bg-amber-500/10 text-amber-500 px-1.5 py-0.5 rounded border border-amber-500/20">ISP {{ $i }}</span>
                                        <span>{{ $isp['label'] }}</span>
                                    </h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase">Nama Marker ISP</label>
                                            <input type="text" name="network_isp_name_{{ $i }}" value="{{ $isp['name'] }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Kosongkan jika tidak ada">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase">Deskripsi / Detail ISP</label>
                                            <input type="text" name="network_isp_description_{{ $i }}" value="{{ $isp['desc'] }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Keterangan opsional">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase">Latitude (Lintang)</label>
                                            <input type="number" step="any" name="network_isp_latitude_{{ $i }}" value="{{ $isp['lat'] }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="-6.2065">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase">Longitude (Bujur)</label>
                                            <input type="number" step="any" name="network_isp_longitude_{{ $i }}" value="{{ $isp['lng'] }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="106.8430">
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            </div>
                        </div>

                        <!-- Server Core NOC Coordinates Card -->
                        <div class="settings-card bg-slate-950/20 border border-slate-850 p-5 rounded-2xl space-y-4" data-settings-card="server-core">
                            <div class="settings-card-header">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-lg bg-gradient-to-tr from-cyan-600 to-cyan-400 flex items-center justify-center text-white shrink-0 shadow-sm shadow-cyan-500/20">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect>
                                            <rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect>
                                            <line x1="6" x2="6.01" y1="6" y2="6"></line>
                                            <line x1="6" x2="6.01" y1="18" y2="18"></line>
                                        </svg>
                                    </span>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-cyan-400">Koordinat Server Core NOC</h4>
                                </div>
                                <button type="button" class="settings-card-toggle" data-toggle-settings-card="server-core" title="Minimize / buka kartu" aria-label="Minimize atau buka kartu Server Core">
                                    <svg class="card-minus-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
                                    <svg class="card-plus-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>

                            <div data-card-body class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Nama Marker Server</label>
                                    <input type="text" name="network_server_name" value="{{ $networkServerName }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Server Core NOC">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Deskripsi / Detail Server</label>
                                    <input type="text" name="network_server_description" value="{{ $networkServerDescription }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Mikrotik CCR, OLT, dll">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Latitude (Lintang)</label>
                                    <input type="number" step="any" name="network_server_latitude" value="{{ $networkServerLatitude }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="-6.2070">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Longitude (Bujur)</label>
                                    <input type="number" step="any" name="network_server_longitude" value="{{ $networkServerLongitude }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="106.8445">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800/60 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 rounded-xl font-bold text-xs text-white shadow-lg shadow-indigo-600/20 transition-all duration-300">
                        Simpan Profil Perusahaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 2: Pengguna (Profil Staf) -->
    <div id="tab-users" class="tab-content hidden space-y-6">
        <!-- Users Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($users as $u)
                <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 flex flex-col justify-between transition-all duration-300 hover:border-slate-700/60 group relative hover:shadow-lg hover:shadow-slate-950/20">
                    <div class="flex items-start gap-4">
                        <!-- Photo Profile -->
                        <div class="w-16 h-16 rounded-xl bg-slate-950 border border-slate-850 flex items-center justify-center overflow-hidden shrink-0 relative shadow">
                            @if($u->photo)
                                <img src="{{ route('settings.users.photo', $u) . '?v=' . md5($u->photo) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-indigo-500/10 text-indigo-400 font-bold font-mono text-lg flex items-center justify-center">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                            @endif
                        </div>

                        <!-- Profile Info -->
                        <div class="space-y-1 overflow-hidden flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-white text-sm truncate">{{ $u->name }}</h4>
                            </div>
                            <p class="text-[10px] font-bold tracking-wider uppercase font-mono px-2 py-0.5 rounded inline-block {{ $u->role === 'superadmin' ? 'bg-indigo-500/10 text-indigo-400' : ($u->role === 'admin' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400') }}">
                                {{ $u->role }}
                            </p>
                            
                            <div class="text-xs text-slate-400 space-y-0.5 pt-2">
                                <p class="flex items-center gap-1.5 font-sans">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    <span>{{ $u->phone ?? '-' }}</span>
                                </p>
                                <p class="flex items-center gap-1.5 truncate font-sans">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                                    <span class="truncate" title="{{ $u->address }}">{{ $u->address ?? '-' }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Profile Trigger Button at Bottom -->
                    <div class="mt-4 pt-3.5 border-t border-slate-850/60 flex justify-end">
                        <button onclick="document.getElementById('modal-edit-profile-{{ $u->id }}').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 flex items-center gap-1.5 text-xs font-semibold text-slate-300 hover:text-white transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            <span>Edit Profil</span>
                        </button>
                    </div>
                </div>

                <!-- MODAL: Edit Profile -->
                <div id="modal-edit-profile-{{ $u->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                        <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                            <h3 class="text-base font-bold text-white">Edit Profil Pengguna</h3>
                            <button onclick="document.getElementById('modal-edit-profile-{{ $u->id }}').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <form action="{{ route('settings.users.update', $u->id) }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-3.5">
                            @csrf
                            @method('PUT')
                            
                            <!-- Profile Photo Selection -->
                            <div class="flex flex-col items-center space-y-2 pb-2">
                                <div class="w-20 h-20 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center overflow-hidden relative shadow">
                                    <img id="photo-preview-{{ $u->id }}" src="{{ $u->photo ? route('settings.users.photo', $u) . '?v=' . md5($u->photo) : '' }}" class="w-full h-full object-cover {{ $u->photo ? '' : 'hidden' }}">
                                    <div id="photo-placeholder-{{ $u->id }}" class="text-slate-600 font-bold text-lg font-mono {{ $u->photo ? 'hidden' : 'flex' }} items-center justify-center w-full h-full bg-indigo-500/10 text-indigo-400">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                </div>
                                <label class="px-3 py-1.5 bg-slate-850 hover:bg-slate-800 border border-slate-700/60 rounded-xl text-[10px] font-semibold text-slate-200 cursor-pointer transition-colors shadow">
                                    Pilih Foto Profil
                                    <input type="file" name="photo" accept="image/*" class="hidden" onchange="previewImage(this, 'photo-preview-{{ $u->id }}', 'photo-placeholder-{{ $u->id }}')">
                                </label>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Nama Lengkap</label>
                                <input type="text" name="name" value="{{ $u->name }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Nama staf">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Nomor HP</label>
                                <input type="text" name="phone" value="{{ $u->phone }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="08xxxxxx">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Alamat Domisili</label>
                                <textarea name="address" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Alamat rumah ...">{{ $u->address }}</textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Kata Sandi Baru (Kosongkan jika tidak diganti)</label>
                                <input type="password" name="password" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Minimal 6 karakter">
                            </div>

                            <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                                <button type="button" onclick="document.getElementById('modal-edit-profile-{{ $u->id }}').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-xs font-semibold">Batal</button>
                                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all text-xs">Simpan Profil</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- TAB 3: Akun Pengguna & Role -->
    <div id="tab-accounts" class="tab-content hidden space-y-6">
        <!-- Add User Trigger Button -->
        <div class="flex justify-end">
            <button onclick="document.getElementById('modal-add-user').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white shadow shadow-indigo-600/25 transition-all duration-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                <span>Tambah Pengguna</span>
            </button>
        </div>

        <!-- Accounts List Table -->
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl overflow-hidden shadow-lg">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800/60 text-[10px] font-bold tracking-wider uppercase text-slate-400 bg-slate-950/20">
                            <th class="px-6 py-4">Nama</th>
                            <th class="px-6 py-4">Alamat Email</th>
                            <th class="px-6 py-4">Hak Akses / Role</th>
                            <th class="px-6 py-4">Dibuat Pada</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850/60 text-xs">
                        @foreach($users as $u)
                            <tr class="hover:bg-slate-900/10 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-200">{{ $u->name }}</td>
                                <td class="px-6 py-4 text-slate-400 font-mono">{{ $u->email }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-[9px] font-bold font-mono tracking-wider uppercase px-2 py-0.5 rounded {{ $u->role === 'superadmin' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : ($u->role === 'admin' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') }}">
                                        {{ $u->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-400">{{ $u->created_at->translatedFormat('d M Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit Account Credentials -->
                                        <button onclick="document.getElementById('modal-edit-account-{{ $u->id }}').classList.remove('hidden')" class="p-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-lg text-slate-400 hover:text-white transition-colors" title="Kelola Kredensial & Role">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                                        </button>

                                        <!-- Delete Account (Disabled if self) -->
                                        @if($u->id !== Auth::id())
                                            <button onclick="document.getElementById('modal-delete-user-{{ $u->id }}').classList.remove('hidden')" class="p-1.5 bg-rose-500/10 hover:bg-rose-550 border border-rose-500/20 rounded-lg text-rose-400 hover:text-white transition-colors" title="Hapus Pengguna">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- MODAL: Edit Account -->
                            <div id="modal-edit-account-{{ $u->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 overflow-y-auto">
                                <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                                    <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                                        <h3 class="text-base font-bold text-white">Kelola Akun Login & Peran</h3>
                                        <button onclick="document.getElementById('modal-edit-account-{{ $u->id }}').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <form action="{{ route('settings.accounts.update', $u->id) }}" method="POST" class="p-5 space-y-3.5">
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase font-mono">Email / Username Login</label>
                                            <input type="email" name="email" value="{{ $u->email }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono" placeholder="staf@rtrw.net">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Tipe Hak Akses (Role)</label>
                                            <select name="role" required {{ ($u->id === Auth::id() && $u->role === 'superadmin') ? 'disabled' : '' }} class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                <option value="superadmin" {{ $u->role === 'superadmin' ? 'selected' : '' }}>Superadmin (Semua Akses)</option>
                                                <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin (Custom)</option>
                                                <option value="tehnisi" {{ $u->role === 'tehnisi' ? 'selected' : '' }}>Tehnisi (Custom)</option>
                                            </select>
                                            @if($u->id === Auth::id() && $u->role === 'superadmin')
                                                <input type="hidden" name="role" value="superadmin">
                                                <span class="text-[9px] text-slate-500 mt-1 block">Anda tidak bisa mengubah role superadmin akun Anda sendiri saat sedang masuk.</span>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Kata Sandi Baru (Kosongkan jika tidak diganti)</label>
                                            <input type="password" name="password" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Minimal 6 karakter">
                                        </div>

                                        <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                                            <button type="button" onclick="document.getElementById('modal-edit-account-{{ $u->id }}').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-xs font-semibold">Batal</button>
                                            <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all text-xs">Simpan Akun</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- MODAL: Delete User Confirmation -->
                            @if($u->id !== Auth::id())
                                <div id="modal-delete-user-{{ $u->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
                                    <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                                        <div class="p-6 text-center space-y-4">
                                            <div class="w-16 h-16 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center mx-auto">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                            </div>
                                            <div>
                                                <h3 class="text-xl font-bold text-white">Hapus Akun Pengguna?</h3>
                                                <p class="text-sm text-slate-400 mt-2">Apakah Anda yakin ingin menghapus data dan akses login untuk pengguna <strong>{{ $u->name }}</strong>? Tindakan ini permanen.</p>
                                            </div>
                                            <div class="flex justify-center gap-3 pt-2">
                                                <button onclick="document.getElementById('modal-delete-user-{{ $u->id }}').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-sm font-semibold">Batal</button>
                                                <form action="{{ route('settings.users.destroy', $u->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold transition-colors">Hapus Permanen</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL: Add User -->
        <div id="modal-add-user" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Tambah Pengguna & Akun Baru</h3>
                    <button onclick="document.getElementById('modal-add-user').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form action="{{ route('settings.users.store') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-3.5 max-h-[75vh] overflow-y-auto">
                    @csrf
                    
                    <!-- Profile Photo Selection -->
                    <div class="flex flex-col items-center space-y-2 pb-2">
                        <div class="w-20 h-20 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center overflow-hidden relative shadow">
                            <img id="new-photo-preview" src="" class="w-full h-full object-cover hidden">
                            <div id="new-photo-placeholder" class="text-slate-650 flex flex-col items-center justify-center w-full h-full bg-slate-950 font-mono text-[9px] font-bold text-center">
                                NO FOTO
                            </div>
                        </div>
                        <label class="px-3 py-1.5 bg-slate-850 hover:bg-slate-800 border border-slate-700/60 rounded-xl text-[10px] font-semibold text-slate-200 cursor-pointer transition-colors shadow">
                            Pilih Foto
                            <input type="file" name="photo" accept="image/*" class="hidden" onchange="previewImage(this, 'new-photo-preview', 'new-photo-placeholder')">
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: Ahmad Subardjo">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase font-mono">Alamat Email Login</label>
                        <input type="email" name="email" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono" placeholder="staf@rtrw.net">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Kata Sandi Login</label>
                        <input type="password" name="password" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Minimal 6 karakter">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Hak Akses / Role</label>
                        <select name="role" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                            <option value="admin">Admin (Custom)</option>
                            <option value="tehnisi">Tehnisi (Custom)</option>
                            <option value="superadmin">Superadmin (Semua Akses)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Nomor HP</label>
                        <input type="text" name="phone" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="0812xxxxxxxx">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Alamat Domisili</label>
                        <textarea name="address" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Alamat rumah ..."></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('modal-add-user').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-xs font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all text-xs">Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 4: Pengaturan Hak Akses -->
    <div id="tab-permissions" class="tab-content hidden">
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl p-6 md:p-8">
            <div class="mb-6">
                <h3 class="text-xl font-bold text-white">Matriks Pengaturan Hak Akses</h3>
                <p class="text-xs text-slate-400 mt-1">Sesuaikan modul aplikasi mana saja yang boleh dibuka oleh masing-masing tipe pengguna sistem. (Akun dengan role <strong>Superadmin</strong> memiliki akses penuh ke seluruh modul secara permanen).</p>
            </div>

            <form action="{{ route('settings.permissions') }}" method="POST">
                @csrf
                <div class="overflow-x-auto border border-slate-800/80 rounded-2xl">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-[10px] font-bold tracking-wider uppercase text-slate-400 bg-slate-950/30">
                                <th class="px-6 py-4">Modul / Halaman Aplikasi</th>
                                <th class="px-6 py-4 text-center">Superadmin</th>
                                <th class="px-6 py-4 text-center">Admin</th>
                                <th class="px-6 py-4 text-center">Tehnisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850/60 text-xs">
                            @foreach($modules as $key => $label)
                                <tr class="hover:bg-slate-900/10 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-200">
                                        <span>{{ $label }}</span>
                                        <span class="block text-[10px] text-slate-500 font-mono font-medium">{{ $key }}</span>
                                    </td>
                                    <!-- Superadmin Column (Always enabled/readonly) -->
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center">
                                            <input type="checkbox" checked disabled class="w-4 h-4 rounded border-slate-800 bg-slate-950 text-indigo-500 focus:ring-0 opacity-50 cursor-not-allowed">
                                        </div>
                                    </td>
                                    <!-- Admin Column (Editable) -->
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center">
                                            <input type="hidden" name="permissions[admin][{{ $key }}]" value="0">
                                            <input type="checkbox" name="permissions[admin][{{ $key }}]" value="1" {{ !empty($permissions['admin'][$key]) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-800 bg-slate-950 text-emerald-500 focus:ring-offset-slate-950 focus:ring-emerald-500">
                                        </div>
                                    </td>
                                    <!-- Technician Column (Editable except settings page) -->
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center">
                                            @if($key === 'settings')
                                                <!-- settings page is always blocked for technicians to prevent privilege escalation -->
                                                <input type="checkbox" disabled class="w-4 h-4 rounded border-slate-800 bg-slate-950 text-amber-500 focus:ring-0 opacity-50 cursor-not-allowed">
                                            @else
                                                <input type="hidden" name="permissions[tehnisi][{{ $key }}]" value="0">
                                                <input type="checkbox" name="permissions[tehnisi][{{ $key }}]" value="1" {{ !empty($permissions['tehnisi'][$key]) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-800 bg-slate-950 text-amber-500 focus:ring-offset-slate-950 focus:ring-amber-500">
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-6 mt-6 border-t border-slate-800/60 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 rounded-xl font-bold text-xs text-white shadow-lg shadow-indigo-600/20 transition-all duration-300">
                        Simpan Hak Akses
                    </button>
                </div>
            </form>
        </div>
    </div>

    @can('wa_templates')
    <!-- TAB 5: Template WhatsApp -->
    <div id="tab-wa-templates" class="tab-content hidden space-y-6">
        <!-- Header Section inside Settings -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-xl font-bold text-white">Template WhatsApp</h3>
                <p class="text-xs text-slate-400 mt-1">Kelola format draf pesan otomatis untuk penagihan, konfirmasi bayar, dan status ODP/jaringan pusat.</p>
            </div>
            <div>
                <button onclick="document.getElementById('modal-add-template').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white shadow shadow-indigo-600/25 transition-all duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Template</span>
                </button>
            </div>
        </div>

        <!-- Main Content Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- Left Side: Templates List (2 cols on large screen) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($waTemplates as $tmpl)
                        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 flex flex-col justify-between transition-all duration-300 hover:border-slate-700/60 group hover:shadow-lg hover:shadow-slate-950/20 relative overflow-hidden text-left">
                            <div>
                                <!-- Header info -->
                                <div class="flex items-center justify-between mb-3.5">
                                    <span class="text-[10px] font-bold tracking-wider uppercase font-mono px-2 py-0.5 rounded {{ $tmpl->is_system ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' }}">
                                        {{ $tmpl->is_system ? 'Sistem' : 'Kustom' }}
                                    </span>
                                    <span class="text-[10px] font-semibold text-slate-500 font-mono">{{ $tmpl->name }}</span>
                                </div>

                                <h3 class="text-base font-bold text-slate-200 group-hover:text-white transition-colors">{{ $tmpl->title }}</h3>
                                
                                <!-- Template Content Preview -->
                                <div class="mt-3 bg-slate-950/50 border border-slate-850/60 rounded-xl p-3.5 text-xs text-slate-350 font-sans leading-relaxed whitespace-pre-wrap min-h-[100px] line-clamp-5">
                                    {!! preg_replace('/(\{[a-zA-Z0-9_]+\})/', '<span class="text-indigo-400 font-bold">$1</span>', e($tmpl->content)) !!}
                                </div>
                            </div>

                            <!-- Card Actions -->
                            <div class="mt-5 pt-3.5 border-t border-slate-850/60 flex items-center justify-end gap-2.5">
                                <!-- Edit Button -->
                                <button onclick="document.getElementById('modal-edit-template-{{ $tmpl->id }}').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 flex items-center gap-1.5 text-xs font-semibold text-slate-300 hover:text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    <span>Edit</span>
                                </button>

                                <!-- Delete Button (Disabled for system templates) -->
                                @if(!$tmpl->is_system)
                                    <button onclick="document.getElementById('modal-delete-template-{{ $tmpl->id }}').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-550 border border-rose-500/20 flex items-center gap-1.5 text-xs font-semibold text-rose-400 hover:text-white transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        <span>Hapus</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- MODAL: Edit Template -->
                        <div id="modal-edit-template-{{ $tmpl->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 overflow-y-auto">
                            <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                                <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                                    <h3 class="text-base font-bold text-white">Edit Template WA</h3>
                                    <button onclick="document.getElementById('modal-edit-template-{{ $tmpl->id }}').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <form action="{{ route('wa-templates.update', $tmpl->id) }}" method="POST" class="p-5 space-y-3.5 text-left">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Judul Tampilan</label>
                                        <input type="text" name="title" value="{{ $tmpl->title }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: Kirim Tagihan">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Kode Unik / Slug</label>
                                        <input type="text" name="name" value="{{ $tmpl->name }}" required {{ $tmpl->is_system ? 'readonly disabled' : '' }} class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 disabled:opacity-50 disabled:bg-slate-900/40 disabled:text-slate-500 transition-colors font-mono" placeholder="Contoh: kirim_tagihan_cepat">
                                        @if($tmpl->is_system)
                                            <span class="text-[9px] text-slate-500 mt-1 block">Kode template bawaan sistem dilindungi dan tidak dapat diubah.</span>
                                        @endif
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="block text-xs font-semibold text-slate-400 uppercase">Isi Pesan WA</label>
                                            <span class="text-[9px] text-indigo-400 font-semibold uppercase tracking-wider">Mendukung Placeholder</span>
                                        </div>
                                        <textarea name="content" rows="6" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 font-mono focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Ketik draf pesan WhatsApp...">{{ $tmpl->content }}</textarea>
                                    </div>

                                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                                        <button type="button" onclick="document.getElementById('modal-edit-template-{{ $tmpl->id }}').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-xs font-semibold">Batal</button>
                                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all text-xs">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- MODAL: Delete Template -->
                        @if(!$tmpl->is_system)
                            <div id="modal-delete-template-{{ $tmpl->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
                                <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                                    <div class="p-6 text-center space-y-4">
                                        <div class="w-16 h-16 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center mx-auto">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-white">Hapus Template WA?</h3>
                                            <p class="text-sm text-slate-400 mt-2">Apakah Anda yakin ingin menghapus template kustom <strong>{{ $tmpl->title }}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
                                        </div>
                                        <div class="flex justify-center gap-3 pt-2">
                                            <button onclick="document.getElementById('modal-delete-template-{{ $tmpl->id }}').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-sm font-semibold">Batal</button>
                                            <form action="{{ route('wa-templates.destroy', $tmpl->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold transition-colors">Hapus Sekarang</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @empty
                        <div class="col-span-full bg-slate-900/20 border border-dashed border-slate-800 rounded-3xl py-16 text-center">
                            <svg class="w-12 h-12 text-slate-650 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <h3 class="text-lg font-bold text-slate-300">Belum Ada Template</h3>
                            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Gunakan tombol di atas untuk menambahkan template baru kustom.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right Side: Placeholders Reference Guide -->
            <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 space-y-5">
                <div>
                    <h3 class="text-base font-bold text-slate-200">Bantuan Variabel</h3>
                    <p class="text-xs text-slate-400 mt-1">Gunakan kode di bawah ini untuk memuat data pelanggan secara dinamis di dalam pesan WA Anda.</p>
                </div>

                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-3 text-left">
                    <div class="text-xs font-bold text-emerald-300">Variabel Khusus Pembayaran</div>
                    <p class="mt-1 text-[11px] text-emerald-100/80">Dipakai untuk template yang dipilih pada form Konfirmasi Pembayaran.</p>
                    <div class="mt-2 grid gap-1.5 font-mono text-[11px] text-emerald-100">
                        <span>{rincian_tagihan}</span>
                        <span>{sisa_tagihan}</span>
                        <span>{rincian_sisa_tagihan}</span>
                        <span>{periode_bayar}</span>
                        <span>{periode_sisa_tagihan}</span>
                        <span>{total_sisa_tagihan}</span>
                        <span>{status_sisa_tagihan}</span>
                        <span>{info_sisa_tagihan}</span>
                        <span>{nominal_dibayar}</span>
                        <span>{nominal_dibayar_miring}</span>
                        <span>{nominal_dibayar_terbilang}</span>
                        <span>{metode_bayar}</span>
                        <span>{tanggal_bayar}</span>
                        <span>{tanggal_bayar_lengkap}</span>
                        <span>{tanggal_janji_bayar}</span>
                        <span>{catatan_janji_bayar}</span>
                        <span>{jumlah_bulan}</span>
                    </div>
                </div>

                <div class="space-y-3.5">
                    @foreach($placeholders as $code => $desc)
                        <div class="p-3 bg-slate-950/40 border border-slate-850/60 rounded-xl flex items-start justify-between gap-3 group transition-colors hover:border-slate-800 text-left">
                            <div>
                                <code class="text-xs font-bold text-indigo-400 font-mono bg-indigo-500/5 px-2 py-0.5 rounded border border-indigo-500/10">{{ $code }}</code>
                                <p class="text-[11px] text-slate-400 mt-1.5 font-medium">{{ $desc }}</p>
                            </div>
                            <button onclick="navigator.clipboard.writeText('{{ $code }}'); alert('Disalin: {{ $code }}')" class="opacity-0 group-hover:opacity-100 text-slate-500 hover:text-indigo-400 p-1 rounded transition-all" title="Salin Variabel">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m-5 4h6m-6 4h6m-6 4h5" /></svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="p-3.5 bg-indigo-500/5 border border-indigo-500/10 rounded-xl text-xs text-indigo-300 flex items-start gap-2.5 text-left">
                    <svg class="w-5 h-5 shrink-0 text-indigo-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="leading-relaxed"><strong>Tip:</strong> Anda bisa memadukan format teks tebal di WA menggunakan tanda bintang. Contoh: <code>*{nama_pelanggan}*</code> akan tercetak tebal di WA.</p>
                </div>
            </div>

        </div>

        <!-- MODAL: Add Template -->
        <div id="modal-add-template" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Tambah Template WA</h3>
                    <button onclick="document.getElementById('modal-add-template').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form action="{{ route('wa-templates.store') }}" method="POST" class="p-5 space-y-3.5 text-left">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Judul Tampilan</label>
                        <input type="text" name="title" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: Tagihan Reminder">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Kode Unik / Slug (Huruf & Underline)</label>
                        <input type="text" name="name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono" placeholder="Contoh: tagihan_reminder">
                        <span class="text-[9px] text-slate-500 mt-1 block">Gunakan format slug/snake_case untuk kode pengenalan sistem.</span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-slate-400 uppercase">Isi Pesan WA</label>
                            <span class="text-[9px] text-indigo-400 font-semibold uppercase tracking-wider">Mendukung Placeholder</span>
                        </div>
                        <textarea name="content" rows="6" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200 font-mono focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Ketik draf pesan WhatsApp..."></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('modal-add-template').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-xs font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all text-xs">Simpan Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
    <!-- TAB: Trafik Dashboard -->
    <div id="tab-appearance" class="tab-content hidden">
        @include('settings.partials.appearance')
    </div>

    <div id="tab-traffic" class="tab-content hidden space-y-6">
        <div class="max-w-3xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl p-6 md:p-8">
            <h3 class="text-xl font-bold text-white mb-2">Pengaturan Trafik Jaringan</h3>
            <p class="text-sm text-slate-400 mb-6">Pilih antarmuka (interface) MikroTik mana saja yang ingin Anda pantau kecepatannya secara langsung di halaman Dashboard.</p>
            
            <form action="{{ route('settings.dashboard_interfaces') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="bg-slate-950/20 border border-slate-800 p-5 rounded-2xl space-y-4">
                    <h4 class="text-sm font-semibold text-slate-200">Daftar Antarmuka Aktif</h4>
                    @if(empty($mikrotikInterfaces))
                        <div class="text-sm text-rose-400">Gagal mengambil antarmuka. Pastikan router MikroTik terhubung.</div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($mikrotikInterfaces as $iface)
                                @php
                                    // Skip PPPoE interfaces to avoid clutter
                                    if(str_contains($iface['name'] ?? '', '<pppoe-')) continue;
                                @endphp
                                <label class="flex items-center gap-3 p-3 rounded-xl border {{ in_array($iface['name'], $dashboardInterfaces) ? 'border-indigo-500 bg-indigo-500/10' : 'border-slate-800 bg-slate-900 hover:border-slate-700' }} cursor-pointer transition-colors group">
                                    <input type="checkbox" name="dashboard_interfaces[]" value="{{ $iface['name'] }}" {{ in_array($iface['name'], $dashboardInterfaces) ? 'checked' : '' }} class="w-4 h-4 text-indigo-500 bg-slate-950 border-slate-700 rounded focus:ring-indigo-500 focus:ring-2">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-slate-200 group-hover:text-white">{{ $iface['name'] }}</span>
                                        <span class="text-[10px] text-slate-500">{{ $iface['type'] ?? 'Unknown' }} | {{ $iface['mac-address'] ?? '-' }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-500 hover:bg-indigo-600 text-white font-semibold text-sm rounded-xl transition-all shadow-lg shadow-indigo-500/20">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>

        <div class="max-w-3xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl p-6 md:p-8">
            <h3 class="text-xl font-bold text-white mb-2">Notifikasi Klien Up/Down (Webhook MikroTik)</h3>
            <p class="text-sm text-slate-400 mb-4">Pasang URL ini di script <em>On Up</em> / <em>On Down</em> pada PPP Profile MikroTik. Notifikasi dikirim ke nomor WhatsApp admin di Profil Perusahaan. URL berisi token rahasia, jangan dibagikan.</p>

            <label class="block text-[10px] font-bold text-slate-500 mb-2 uppercase tracking-wider">URL Webhook</label>
            <input type="text" readonly value="{{ $mikrotikWebhookUrl }}" onclick="this.select()" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-3 text-xs font-mono">

            <label class="block text-[10px] font-bold text-slate-500 mt-4 mb-2 uppercase tracking-wider">Contoh script On Down</label>
            <pre class="bg-slate-950 border border-slate-800 text-slate-300 rounded-xl px-4 py-3 text-xs overflow-x-auto">/tool fetch url="{{ $mikrotikWebhookUrl }}&amp;username=$user&amp;status=down" keep-result=no</pre>
            <p class="text-xs text-slate-500 mt-2">Untuk <em>On Up</em>, ganti <code>status=down</code> menjadi <code>status=up</code>.</p>

            <form action="{{ route('settings.mikrotik-webhook.regenerate') }}" method="POST" class="flex justify-end pt-4" onsubmit="return confirm('Ganti token? URL lama di MikroTik tidak akan berfungsi lagi.')">
                @csrf
                <button type="submit" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm rounded-xl transition-all">Ganti Token</button>
            </form>
        </div>
    </div>
</div>

<script>
    /**
     * Handles tab switching.
     */
    function switchTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(function(el) {
            el.classList.add('hidden');
            el.classList.remove('block');
        });

        // Show selected tab content
        var activeTab = document.getElementById(tabId);
        if (activeTab) {
            activeTab.classList.remove('hidden');
            activeTab.classList.add('block');
        }

        // Reset all tab button styles
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.classList.remove('border-indigo-500', 'text-white');
            btn.classList.add('border-transparent', 'text-slate-400');
        });

        // Set active style on current tab button
        var activeBtn = document.getElementById('btn-' + tabId);
        if (activeBtn) {
            activeBtn.classList.add('border-indigo-500', 'text-white');
            activeBtn.classList.remove('border-transparent', 'text-slate-400');
        }
    }

    /**
     * Live preview for uploaded images.
     */
    function previewImage(input, previewId, placeholderId) {
        var preview = document.getElementById(previewId);
        var placeholder = document.getElementById(placeholderId);
        
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                if (placeholder) {
                    placeholder.classList.add('hidden');
                    placeholder.classList.remove('flex');
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Tab persistence from URL query params
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            switchTab('tab-' + tab);
        }

        document.querySelectorAll('[data-settings-card]').forEach(function(card) {
            const key = card.getAttribute('data-settings-card');
            if (!key) return;

            if (localStorage.getItem('settings_card_collapsed_' + key) === '1') {
                card.classList.add('is-collapsed');
            }
        });

        document.querySelectorAll('[data-toggle-settings-card]').forEach(function(button) {
            button.addEventListener('click', function() {
                const key = button.getAttribute('data-toggle-settings-card');
                const card = document.querySelector('[data-settings-card="' + key + '"]');
                if (!card) return;

                const collapsed = !card.classList.contains('is-collapsed');
                card.classList.toggle('is-collapsed', collapsed);
                localStorage.setItem('settings_card_collapsed_' + key, collapsed ? '1' : '0');
            });
        });
    });
</script>
@endsection
