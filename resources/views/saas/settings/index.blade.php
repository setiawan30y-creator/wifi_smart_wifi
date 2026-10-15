@extends('layouts.app')

@section('title', 'Pengaturan Gateway (SaaS)')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Pengaturan Gateway (SaaS)</h1>
        <p class="text-slate-400 text-sm mt-1">Konfigurasi Tripay dan WhatsApp untuk otomatisasi tagihan ke Penyewa (RT/RW Net).</p>
    </div>
</div>

@if(session('success'))
    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl mb-6 text-sm font-semibold flex items-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('saas.settings.store') }}" method="POST" class="space-y-6">
    @csrf
    
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl shadow-slate-950">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
            </div>
            <h2 class="text-lg font-bold text-white">Konfigurasi Tripay (Payment Gateway)</h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Tripay API Key</label>
                    <input type="text" name="saas_tripay_api_key" value="{{ $settings['saas_tripay_api_key'] ?? '' }}" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-mono" placeholder="DEV-xxx...">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Tripay Private Key</label>
                    <input type="password" name="saas_tripay_private_key" value="{{ $settings['saas_tripay_private_key'] ?? '' }}" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Tripay Merchant Code</label>
                    <input type="text" name="saas_tripay_merchant_code" value="{{ $settings['saas_tripay_merchant_code'] ?? '' }}" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-mono" placeholder="Txxxx">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Mode Tripay</label>
                    <select name="saas_tripay_mode" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
                        <option value="sandbox" {{ ($settings['saas_tripay_mode'] ?? '') == 'sandbox' ? 'selected' : '' }}>Sandbox (Testing)</option>
                        <option value="production" {{ ($settings['saas_tripay_mode'] ?? '') == 'production' ? 'selected' : '' }}>Production (Live)</option>
                    </select>
                </div>
            </div>
            <p class="text-xs text-slate-500">Tripay ini digunakan khusus untuk menerima pembayaran tagihan sewa aplikasi dari para Tenant.</p>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl shadow-slate-950">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            </div>
            <h2 class="text-lg font-bold text-white">Konfigurasi WhatsApp Gateway (Penagih Otomatis)</h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">URL Endpoint WA Gateway</label>
                    <input type="url" name="saas_wa_endpoint" value="{{ $settings['saas_wa_endpoint'] ?? '' }}" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-mono" placeholder="http://localhost:3000/send-message">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Format Pesan Tagihan Otomatis</label>
                    <textarea name="saas_wa_invoice_template" rows="5" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-mono" placeholder="Halo {name}, Tagihan Anda sebesar {amount} telah terbit...">{{ $settings['saas_wa_invoice_template'] ?? "Halo *{name}*,\n\nTagihan sewa aplikasi Anda telah terbit.\nNomor Tagihan: *{invoice_number}*\nTotal Bayar: *Rp {amount}*\nJatuh Tempo: *{due_date}*\n\nSilakan bayar melalui link berikut (QRIS/VA):\n{checkout_url}\n\nTerima kasih!" }}</textarea>
                    <p class="text-[10px] text-slate-500 mt-1">Variabel tersedia: {name}, {invoice_number}, {amount}, {due_date}, {checkout_url}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-500/20">
            Simpan Konfigurasi Gateway
        </button>
    </div>
</form>

@endsection
