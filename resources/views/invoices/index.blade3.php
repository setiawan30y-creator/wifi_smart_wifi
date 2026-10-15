@extends('layouts.app')

@section('content')
@php
    $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $billingTypeLabels = \App\Models\Customer::billingTypeLabels();
@endphp

<style>
    .invoice-notebook-table {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1120px;
    }
    .invoice-notebook-table th,
    .invoice-notebook-table td {
        border-right: 1px solid rgba(71, 85, 105, .38);
        border-bottom: 1px solid rgba(71, 85, 105, .38);
        vertical-align: middle;
    }
    .invoice-notebook-table th:first-child,
    .invoice-notebook-table td:first-child {
        border-left: 1px solid rgba(71, 85, 105, .38);
    }
    .invoice-notebook-table thead th {
        border-top: 1px solid rgba(71, 85, 105, .38);
        background: rgba(15, 23, 42, .92);
    }
    .invoice-notebook-table .invoice-actions-col {
        position: sticky;
        right: 0;
        z-index: 15;
        min-width: 360px;
        box-shadow: -14px 0 22px -20px rgba(0, 0, 0, .95);
    }
    .invoice-notebook-table thead .invoice-actions-col {
        z-index: 25;
        background: rgba(15, 23, 42, .98);
    }
    .invoice-notebook-table tbody .invoice-actions-col {
        background: rgba(15, 23, 42, .96);
    }
    .invoice-notebook-table tbody tr:hover .invoice-actions-col {
        background: rgb(30, 41, 59);
    }
    .payment-detail-panel {
        height: min(88vh, 860px);
        max-height: calc(100vh - 32px);
    }
    .payment-detail-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        scrollbar-color: rgba(99, 102, 241, .75) rgba(15, 23, 42, .9);
    }
    .payment-detail-body::-webkit-scrollbar {
        width: 9px;
    }
    .payment-detail-body::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, .9);
        border-radius: 999px;
    }
    .payment-detail-body::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, .75);
        border-radius: 999px;
    }
    .edit-payment-overlay {
        position: fixed !important;
        inset: 0 !important;
        z-index: 9999 !important;
    }
    .edit-payment-panel {
        height: auto;
        max-height: calc(100vh - 32px);
    }

    .invoices-page .page-actions a,
    .invoices-page .page-actions button {
        min-height: 2.25rem;
        border-radius: .65rem !important;
        padding: .5rem .75rem !important;
        font-size: 12px !important;
    }

    .invoices-page .filter-card {
        border-radius: .75rem !important;
        padding: .8rem !important;
    }

    .invoices-page .filter-card input,
    .invoices-page .filter-card select {
        min-height: 2.15rem !important;
        padding: .45rem .65rem !important;
        font-size: 12px !important;
    }

    .invoices-page .invoice-notebook-table th,
    .invoices-page .invoice-notebook-table td {
        padding: .25rem .45rem !important;
        font-size: 12px !important;
        line-height: 1.15rem !important;
    }

    .invoices-page .invoice-notebook-table .invoice-actions-col {
        min-width: 330px;
    }

    .invoices-page .invoice-notebook-table .invoice-actions-col button,
    .invoices-page .invoice-notebook-table .invoice-actions-col a {
        border-radius: .45rem !important;
        padding: .32rem .45rem !important;
    }

    .invoices-page .fixed.inset-0 input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
    .invoices-page .fixed.inset-0 select,
    .invoices-page .fixed.inset-0 textarea {
        min-height: 2.2rem !important;
        padding: .5rem .7rem !important;
        font-size: 12px !important;
    }

    .invoices-page .fixed.inset-0 h3 {
        font-size: 1rem !important;
    }

    .invoices-page .payment-detail-body,
    .invoices-page .fixed.inset-0 .p-5,
    .invoices-page .fixed.inset-0 form.p-5 {
        padding: 1rem !important;
    }

    @media (max-width: 767px) {
        .invoice-notebook-table {
            min-width: 0;
            border-spacing: 0 .75rem;
        }

        .invoice-notebook-table thead {
            display: none;
        }

        .invoice-notebook-table,
        .invoice-notebook-table tbody,
        .invoice-notebook-table tr,
        .invoice-notebook-table td {
            display: block;
            width: 100%;
        }

        .invoice-notebook-table tr {
            overflow: hidden;
            border: 1px solid rgba(51, 65, 85, .78);
            border-radius: 1rem;
            background: rgba(15, 23, 42, .72);
            box-shadow: 0 14px 28px -24px rgba(2, 6, 23, .95);
        }

        .invoice-notebook-table td {
            border: 0;
            border-bottom: 1px solid rgba(51, 65, 85, .55);
            padding: .7rem .85rem !important;
        }

        .invoice-notebook-table td:last-child {
            border-bottom: 0;
        }

        .invoice-notebook-table td:not(:first-child):not(.invoice-actions-col)::before {
            display: block;
            margin-bottom: .25rem;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .invoice-notebook-table td:nth-child(2)::before { content: "No. Invoice"; }
        .invoice-notebook-table td:nth-child(3)::before { content: "Pelanggan"; }
        .invoice-notebook-table td:nth-child(4)::before { content: "Periode"; }
        .invoice-notebook-table td:nth-child(5)::before { content: "Jumlah"; }
        .invoice-notebook-table td:nth-child(6)::before { content: "Status"; }
        .invoice-notebook-table td:nth-child(7)::before { content: "Pembayaran"; }

        .invoice-notebook-table .invoice-actions-col {
            position: static;
            min-width: 0;
            background: rgba(2, 6, 23, .42);
            box-shadow: none;
            text-align: left;
            white-space: normal;
        }

        .invoice-notebook-table .invoice-actions-col button,
        .invoice-notebook-table .invoice-actions-col a,
        .invoice-notebook-table .invoice-actions-col form {
            margin-top: .35rem;
        }

        #payment-detail-modal,
        #edit-payment-modal,
        #payment-promise-modal,
        #global-pay-modal,
        #invoice-wa-modal,
        #generate-modal,
        #advance-payment-modal,
        #overdue-broadcast-modal,
        #invoice-import-modal {
            align-items: stretch;
            padding: 0;
        }

        .payment-detail-panel,
        .edit-payment-panel,
        #global-pay-modal > div,
        #invoice-wa-modal > div,
        #generate-modal > div,
        #advance-payment-modal > div,
        #overdue-broadcast-modal > div,
        #invoice-import-modal > div {
            width: 100%;
            max-width: none;
            min-height: 100vh;
            max-height: 100vh;
            border-radius: 0;
            border-left: 0;
            border-right: 0;
        }

        .flex.flex-wrap.items-center.gap-2.self-start > a,
        .flex.flex-wrap.items-center.gap-2.self-start > button {
            flex: 1 1 calc(50% - .25rem);
            justify-content: center;
            min-height: 2.75rem;
            padding-left: .75rem;
            padding-right: .75rem;
            font-size: 12px;
        }
    }
</style>

<div class="invoices-page space-y-5">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 md:gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white">Tagihan &amp; Invoice</h1>
            <p class="text-sm md:text-base text-slate-400 mt-1">Kelola dan pantau seluruh tagihan iuran/internet pelanggan bulanan.</p>
        </div>

        <div class="page-actions flex flex-wrap items-center gap-2 self-start">
            <a href="{{ route('invoices.automation-settings') }}"
               class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-500/10 text-sky-300 border border-sky-500/25 hover:bg-sky-500 hover:text-white transition-all duration-300 text-sm font-semibold"
               title="Pengaturan generate otomatis dan WA jatuh tempo">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 6h3m-6 6h9m-11 6h13M5 6h.01M5 12h.01M5 18h.01"/>
                </svg>
                Otomatis
            </a>

            {{-- Export --}}
            <a href="{{ route('invoices.export') }}"
               class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 hover:bg-emerald-500 hover:text-white transition-all duration-300 text-sm font-semibold"
               title="Export semua tagihan ke Excel (.xls)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export .xls
            </a>

            {{-- Import --}}
            <button
                onclick="document.getElementById('invoice-import-modal').classList.remove('hidden'); document.getElementById('invoice-import-modal').classList.add('flex')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/25 hover:bg-amber-500 hover:text-white transition-all duration-300 text-sm font-semibold"
                title="Import tagihan dari .xls atau .csv">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import .xls
            </button>

            {{-- Generate --}}
            <button onclick="document.getElementById('generate-modal').classList.remove('hidden'); document.getElementById('generate-modal').classList.add('flex')" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-650 hover:to-purple-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-300 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Generate Tagihan Bulanan</span>
            </button>

            <button type="button" onclick="openCustomerInvoiceModal()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-500/10 text-blue-300 border border-blue-500/25 hover:bg-blue-500 hover:text-white transition-all duration-300 text-sm font-semibold" title="Buat invoice untuk satu pelanggan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Generate Per Pelanggan</span>
            </button>

            {{-- Advance Payment --}}
            <button onclick="openAdvancePaymentModal()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-teal-500/10 text-teal-300 border border-teal-500/25 hover:bg-teal-500 hover:text-white transition-all duration-300 text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Bayar di Muka</span>
            </button>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filter-card bg-slate-900/20 border border-slate-800/60 p-6 rounded-2xl backdrop-blur-md">
        <form action="{{ route('invoices.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-8 gap-4 items-end">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Cari Pelanggan</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama pelanggan atau No. Telepon..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    <div class="absolute left-3.5 top-2.5 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Month -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Bulan</label>
                <select name="month" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    <option value="">Semua Bulan</option>
                    @foreach ($monthNames as $val => $name)
                        <option value="{{ $val }}" {{ $selectedMonth == $val ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Status</label>
                <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    <option value="">Semua Status</option>
                    <option value="unpaid" {{ $selectedStatus === 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                    <option value="paid" {{ $selectedStatus === 'paid' ? 'selected' : '' }}>Lunas</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase">Jenis Tagihan</label>
                <select name="billing_type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    <option value="">Semua Jenis</option>
                    <option value="paid" {{ $selectedBillingType === 'paid' ? 'selected' : '' }}>Berbayar</option>
                    <option value="free" {{ $selectedBillingType === 'free' ? 'selected' : '' }}>Semua Gratis</option>
                    @foreach ($billingTypeLabels as $value => $label)
                        @if ($value !== 'paid')
                            <option value="{{ $value }}" {{ $selectedBillingType === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-cyan-300 mb-1.5 uppercase">Mode Pembayaran</label>
                <select name="billing_mode" class="w-full bg-slate-950 border border-cyan-500/25 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition-colors">
                    <option value="">Semua Mode</option>
                    <option value="postpaid" {{ $selectedBillingMode === 'postpaid' ? 'selected' : '' }}>Pascabayar</option>
                    <option value="prepaid" {{ $selectedBillingMode === 'prepaid' ? 'selected' : '' }}>Prabayar</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-rose-300 mb-1.5 uppercase">Umur Tunggakan</label>
                <select name="aging" class="w-full bg-slate-950 border border-rose-500/25 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-rose-500 transition-colors">
                    <option value="">Semua Umur</option>
                    <option value="1" {{ $selectedAging === '1' ? 'selected' : '' }}>1 bulan</option>
                    <option value="2_3" {{ $selectedAging === '2_3' ? 'selected' : '' }}>2–3 bulan</option>
                    <option value="4_6" {{ $selectedAging === '4_6' ? 'selected' : '' }}>4–6 bulan</option>
                    <option value="7_12" {{ $selectedAging === '7_12' ? 'selected' : '' }}>7–12 bulan</option>
                    <option value="12_plus" {{ $selectedAging === '12_plus' ? 'selected' : '' }}>Lebih 1 tahun</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-sm font-semibold py-2 rounded-xl transition-colors">Filter</button>
                @if ($selectedMonth || $selectedYear != \Carbon\Carbon::now()->year || $selectedStatus || $selectedBillingType || $selectedBillingMode || $selectedAging || $search)
                    <a href="{{ route('invoices.index') }}" class="flex items-center justify-center p-2 rounded-xl bg-slate-950 border border-slate-850 hover:bg-slate-900 text-slate-400 hover:text-white transition-colors" title="Reset Filter">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3-3m0 0l3 3m-3-3v8" /></svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if ($selectedAging)
        <div class="rounded-2xl border border-rose-500/20 bg-rose-500/5 p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-bold text-white">Kelompok Tunggakan: {{ ['1' => '1 bulan', '2_3' => '2–3 bulan', '4_6' => '4–6 bulan', '7_12' => '7–12 bulan', '12_plus' => 'lebih dari 1 tahun'][$selectedAging] ?? $selectedAging }}</h2>
                        <span class="rounded-md bg-rose-500/15 px-2 py-0.5 text-[10px] font-bold text-rose-300">{{ $agingCustomers->count() }} pelanggan</span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">Dikelompokkan berdasarkan jumlah periode invoice yang belum lunas. Total nominal: <strong class="text-rose-200">Rp {{ number_format((int) $agingCustomers->sum('unpaid_invoice_total'), 0, ',', '.') }}</strong></p>
                </div>
                <button type="button" onclick="openOverdueBroadcastModal()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-950/30 transition hover:bg-rose-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.416 1.46 5.867 0 10.642-4.77 10.646-10.643.002-2.846-1.104-5.523-3.11-7.533C17.593 4.432 14.92 3.328 12.073 3.328 6.205 3.328 1.43 8.098 1.426 13.97c-.001 1.936.505 3.826 1.466 5.425l-.962 3.516 3.6-.945zm11.367-7.46c-.327-.164-1.938-.956-2.238-1.066-.3-.11-.519-.164-.738-.164-.219 0-.574.082-.875.41-.3.328-1.148 1.12-1.148 2.73 0 1.61 1.175 3.167 1.339 3.385.164.218 2.31 3.528 5.597 4.949.781.338 1.39.54 1.866.69.784.249 1.498.214 2.062.129.629-.094 1.938-.792 2.211-1.558.273-.766.273-1.422.191-1.558-.082-.137-.3-.218-.627-.382-.327-.164-1.938-.956-2.238-1.066-.3-.11-.519-.164-.738.164-.219.328-.847 1.066-1.039 1.284-.192.219-.383.246-.71.082-.327-.164-1.383-.51-2.634-1.627-.973-.867-1.63-1.939-1.821-2.266-.192-.328-.02-.505.143-.668.147-.146.327-.383.49-.574.164-.191.219-.328.328-.546.11-.219.055-.41-.027-.574-.082-.164-.738-1.777-1.011-2.434-.266-.64-.539-.554-.738-.564-.19-.01-.41-.01-.628-.01z"/></svg>
                    Kirim WA Tunggakan
                </button>
            </div>
        </div>
    @endif

    <!-- Bulk Actions (Hidden by default, shown when checkboxes are selected) -->
    <div id="bulk-actions" class="hidden mb-4 p-4 bg-slate-900 border border-slate-800 rounded-2xl flex flex-wrap items-center justify-between gap-3">
        <span class="text-sm font-medium text-slate-300">
            <span id="selected-count">0</span> tagihan dipilih
        </span>
        <div class="flex flex-wrap items-center gap-2">
        <button type="button" onclick="openBulkWaModal()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl flex items-center gap-2 transition-colors shadow-lg shadow-emerald-500/20">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.416 1.46 5.867 0 10.642-4.77 10.646-10.643.002-2.846-1.104-5.523-3.11-7.533C17.593 4.432 14.92 3.328 12.073 3.328 6.205 3.328 1.43 8.098 1.426 13.97c-.001 1.936.505 3.826 1.466 5.425l-.962 3.516 3.6-.945zm11.367-7.46c-.327-.164-1.938-.956-2.238-1.066-.3-.11-.519-.164-.738.164-.219.328-.847 1.066-1.039 1.284-.192.219-.383.246-.71.082-.327-.164-1.383-.51-2.634-1.627-.973-.867-1.63-1.939-1.821-2.266-.192-.328-.02-.505.143-.668.147-.146.327-.383.49-.574.164-.191.219-.328.328-.546.11-.219.055-.41-.027-.574-.082-.164-.738-1.777-1.011-2.434-.266-.64-.539-.554-.738-.564-.19-.01-.41-.01-.628-.01-.219 0-.574.082-.875.41-.3.328-1.148 1.12-1.148 2.73 0 1.61 1.175 3.167 1.339 3.385.164.218 2.31 3.528 5.597 4.949.781.338 1.39.54 1.866.69.784.249 1.498.214 2.062.129.629-.094 1.938-.792 2.211-1.558.273-.766.273-1.422.191-1.558-.082-.137-.3-.218-.627-.382z"/></svg>
            Kirim Tagihan WA (QRIS)
        </button>
        <form id="bulk-delete-form" action="{{ route('invoices.bulk-delete') }}" method="POST" onsubmit="return submitBulkDelete(event)" class="inline-flex">
            @csrf
            @method('DELETE')
            <div id="bulk-delete-inputs"></div>
            <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl flex items-center gap-2 transition-colors shadow-lg shadow-rose-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
                Hapus Terpilih
            </button>
        </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-3 sm:p-4">
        <div class="overflow-x-auto">
            <table class="invoice-notebook-table w-full text-left">
                <thead>
                    <tr class="text-slate-400 text-[10px] uppercase font-bold">
                        <th class="py-2 px-2 w-9">
                            <input type="checkbox" id="select-all" class="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500 transition-all">
                        </th>
                        <th class="py-2 px-2">No. Invoice</th>
                        <th class="py-2 px-2">Nama Pelanggan</th>
                        <th class="py-2 px-2 text-center">Periode</th>
                        <th class="py-2 px-2">Jumlah Tagihan</th>
                        <th class="py-2 px-2 text-center">Status</th>
                        <th class="py-2 px-2">Metode & Tgl Bayar</th>
                        <th class="py-2 px-2">Janji Bayar</th>
                        <th class="py-2 px-2 text-right invoice-actions-col">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-xs text-slate-200">
                    @forelse ($invoices as $invoice)
                    @php
                        $customerIsBillable = !$invoice->customer || $invoice->customer->isBillable();
                        $customerBillingLabel = $invoice->customer ? $invoice->customer->billingTypeLabel() : 'Berbayar';
                        $customerInvoices = $invoice->customer->invoices ?? collect();
                        $unpaidList = $customerInvoices->where('status', 'unpaid')->sortBy([
                            ['year', 'asc'],
                            ['month', 'asc'],
                            ['id', 'asc'],
                        ])->map(function($unp) use ($monthNames) {
                            return [
                                'id' => $unp->id,
                                'invoice_number' => $unp->invoice_number,
                                'amount' => $unp->amount,
                                'amount_formatted' => number_format($unp->amount, 0, ',', '.'),
                                'month' => $unp->month,
                                'month_name' => $monthNames[$unp->month],
                                'year' => $unp->year,
                            ];
                        })->values();
                        $paymentHistory = $customerInvoices->map(function($hist) use ($monthNames) {
                            return [
                                'id' => $hist->id,
                                'update_url' => route('invoices.payment.update', $hist->id),
                                'invoice_number' => $hist->invoice_number,
                                'period' => ($monthNames[$hist->month] ?? $hist->month) . ' ' . $hist->year,
                                'amount' => 'Rp ' . number_format($hist->amount, 0, ',', '.'),
                                'amount_raw' => (int) $hist->amount,
                                'status' => $hist->status === 'paid' ? 'Lunas' : 'Belum Lunas',
                                'status_raw' => $hist->status,
                                'payment_method' => $hist->payment_method ?: '-',
                                'paid_at' => $hist->paid_at ? $hist->paid_at->translatedFormat('d M Y - H:i') . ' WIB' : '-',
                                'paid_at_input' => $hist->paid_at ? $hist->paid_at->format('Y-m-d\TH:i') : '',
                                'payment_proof' => $hist->payment_proof,
                                'proof_url' => $hist->payment_proof ? route('invoices.payment-proof', $hist->id) : null,
                            ];
                        })->values();
                        $paidTotal = $customerInvoices->where('status', 'paid')->sum('amount');
                        $unpaidTotal = $customerInvoices->where('status', 'unpaid')->sum('amount');
                    @endphp

                        <tr class="hover:bg-slate-800/10 transition-colors">
                            <td class="py-2 px-2">
                                @if($invoice->status === 'unpaid' && $customerIsBillable)
                                    <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500 transition-all" data-customer-id="{{ $invoice->customer_id }}" data-amount="{{ $invoice->amount }}">
                                @endif
                            </td>
                            <td class="py-2 px-2 font-mono text-indigo-400 font-semibold">{{ $invoice->invoice_number }}</td>
                            <td class="py-2 px-2 font-semibold text-white">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span>{{ $invoice->customer->name }}</span>
                                    @unless($customerIsBillable)
                                        <span class="inline-flex items-center rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-0.5 text-[10px] font-bold text-sky-300">{{ $customerBillingLabel }}</span>
                                    @endunless
                                </div>
                                <div class="text-[10px] text-slate-400 font-normal">Tlp: {{ $invoice->customer->phone }}</div>
                                @unless($customerIsBillable)
                                    <div class="text-[10px] text-sky-300/80 font-normal">Tidak dikirim tagihan WA</div>
                                @endunless
                            </td>
                            <td class="py-2 px-2 text-center text-slate-300">
                                {{ $monthNames[$invoice->month] }} {{ $invoice->year }}
                            </td>
                            <td class="py-2 px-2 font-bold text-white">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                            <td class="py-2 px-2 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $invoice->status === 'paid' ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20' : 'bg-rose-500/10 text-rose-300 border-rose-500/20' }}">
                                    {{ $invoice->status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
                                </span>
                            </td>
                            <td class="py-2 px-2 text-[11px] text-slate-400">
                                @if ($invoice->status === 'paid')
                                    <div class="font-semibold text-slate-200">{{ $invoice->payment_method }}</div>
                                    <div>{{ $invoice->paid_at ? $invoice->paid_at->translatedFormat('d M Y - H:i') . ' WIB' : '-' }}</div>
                                @else
                                    <span class="text-slate-500 font-medium">-</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-[11px] text-slate-400">
                                @if($invoice->payment_promise_date)
                                    <div class="font-bold text-amber-200">{{ $invoice->payment_promise_date->translatedFormat('d M Y') }}</div>
                                    <div class="{{ $invoice->payment_promise_wa_sent_at ? 'text-emerald-300' : 'text-slate-500' }}">
                                        {{ $invoice->payment_promise_wa_sent_at ? 'WA terkirim' : 'Menunggu WA' }}
                                    </div>
                                @else
                                    <span class="text-slate-500 font-medium">-</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right space-x-1 whitespace-nowrap invoice-actions-col">
                                <button
                                    type="button"
                                    data-payment-detail="{{ json_encode([
                                        'invoice_number' => $invoice->invoice_number,
                                        'customer_name' => $invoice->customer->name,
                                        'customer_phone' => $invoice->customer->phone,
                                        'period' => ($monthNames[$invoice->month] ?? $invoice->month) . ' ' . $invoice->year,
                                        'amount' => 'Rp ' . number_format($invoice->amount, 0, ',', '.'),
                                        'status' => $invoice->status === 'paid' ? 'Lunas' : 'Belum Lunas',
                                        'payment_method' => $invoice->payment_method ?: '-',
                                        'paid_at' => $invoice->paid_at ? $invoice->paid_at->translatedFormat('d M Y - H:i') . ' WIB' : '-',
                                        'paid_total' => 'Rp ' . number_format($paidTotal, 0, ',', '.'),
                                        'unpaid_total' => 'Rp ' . number_format($unpaidTotal, 0, ',', '.'),
                                        'paid_count' => $customerInvoices->where('status', 'paid')->count(),
                                        'unpaid_count' => $customerInvoices->where('status', 'unpaid')->count(),
                                        'history' => $paymentHistory,
                                    ]) }}"
                                    onclick="openPaymentDetailModalFromBtn(this)"
                                    class="px-2.5 py-1.5 rounded-lg bg-slate-800/80 text-slate-300 border border-slate-700 hover:bg-slate-700 hover:text-white text-[11px] font-semibold transition-all duration-300 align-middle"
                                    title="Tampilkan data pembayaran">
                                    Data Bayar
                                </button>
                                <!-- WA Button — data-* prevents quote-conflict inside onclick HTML attribute -->
                                @if ($customerIsBillable)
                                <button
                                    type="button"
                                    data-wa-invoice="{{ json_encode([
                                        'id'              => $invoice->id,
                                        'invoice_number'  => $invoice->invoice_number,
                                        'amount'          => number_format($invoice->amount, 0, ',', '.'),
                                        'month_name'      => $monthNames[$invoice->month],
                                        'year'            => $invoice->year,
                                        'status'          => $invoice->status,
                                        'customer'        => [
                                            'name'     => $invoice->customer->name,
                                            'phone'    => $invoice->customer->phone,
                                            'odp_name' => $invoice->customer->odp ? $invoice->customer->odp->name : 'Tidak Terhubung',
                                        ],
                                        'unpaid_invoices' => $unpaidList,
                                    ]) }}"
                                    onclick="openInvoiceWaModalFromBtn(this)"
                                    class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 hover:bg-emerald-600 hover:text-white transition-all duration-300 inline-flex items-center justify-center align-middle"
                                    title="Kirim WhatsApp">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.416 1.46 5.867 0 10.642-4.77 10.646-10.643.002-2.846-1.104-5.523-3.11-7.533C17.593 4.432 14.92 3.328 12.073 3.328 6.205 3.328 1.43 8.098 1.426 13.97c-.001 1.936.505 3.826 1.466 5.425l-.962 3.516 3.6-.945zm11.367-7.46c-.327-.164-1.938-.956-2.238-1.066-.3-.11-.519-.164-.738.164-.219.328-.847 1.066-1.039 1.284-.192.219-.383.246-.71.082-.327-.164-1.383-.51-2.634-1.627-.973-.867-1.63-1.939-1.821-2.266-.192-.328-.02-.505.143-.668.147-.146.327-.383.49-.574.164-.191.219-.328.328-.546.11-.219.055-.41-.027-.574-.082-.164-.738-1.777-1.011-2.434-.266-.64-.539-.554-.738-.564-.19-.01-.41-.01-.628-.01-.219 0-.574.082-.875.41-.3.328-1.148 1.12-1.148 2.73 0 1.61 1.175 3.167 1.339 3.385.164.218 2.31 3.528 5.597 4.949.781.338 1.39.54 1.866.69.784.249 1.498.214 2.062.129.629-.094 1.938-.792 2.211-1.558.273-.766.273-1.422.191-1.558-.082-.137-.3-.218-.627-.382z"/>
                                    </svg>
                                </button>
                                @endif

                                <!-- Pay/Print Action -->
                                @if (!$customerIsBillable)
                                    <span class="inline-flex items-center px-3.5 py-1.5 rounded-lg bg-sky-500/10 text-sky-300 border border-sky-500/20 text-xs font-semibold align-middle">
                                        Gratis
                                    </span>
                                @elseif ($invoice->status === 'unpaid')
                                    @if(auth()->user()->role !== 'client')
                                        <button
                                            type="button"
                                            data-payment-promise="{{ json_encode([
                                                'id' => $invoice->id,
                                                'invoice_number' => $invoice->invoice_number,
                                                'customer_name' => $invoice->customer->name,
                                                'period' => ($monthNames[$invoice->month] ?? $invoice->month) . ' ' . $invoice->year,
                                                'amount' => 'Rp ' . number_format($invoice->amount, 0, ',', '.'),
                                                'update_url' => route('invoices.payment-promise.update', $invoice->id, false),
                                                'promise_date' => $invoice->payment_promise_date ? $invoice->payment_promise_date->format('Y-m-d') : '',
                                                'promise_note' => $invoice->payment_promise_note ?: '',
                                                'wa_template_id' => $invoice->payment_promise_wa_template_id ?: '',
                                                'sent_at' => $invoice->payment_promise_wa_sent_at ? $invoice->payment_promise_wa_sent_at->translatedFormat('d M Y H:i') . ' WIB' : '',
                                            ]) }}"
                                            onclick="openPaymentPromiseModalFromBtn(this)"
                                            class="px-3.5 py-1.5 rounded-lg bg-amber-500/10 text-amber-300 border border-amber-500/25 hover:bg-amber-500 hover:text-slate-950 text-xs font-semibold transition-all duration-300 align-middle mb-1 sm:mb-0">
                                            Janji Bayar
                                        </button>
                                        <button
                                            type="button"
                                            data-invoice-pay="{{ json_encode([
                                                'id'              => $invoice->id,
                                                'invoice_number'  => $invoice->invoice_number,
                                                'amount'          => $invoice->amount,
                                                'month_name'      => $monthNames[$invoice->month],
                                                'year'            => $invoice->year,
                                                'customer'        => [
                                                    'id'   => $invoice->customer->id,
                                                    'name' => $invoice->customer->name,
                                                ],
                                                'unpaid_invoices' => $unpaidList,
                                            ]) }}"
                                            onclick="openPayModalFromBtn(this)"
                                            class="px-3.5 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 hover:bg-emerald-500 hover:text-white text-xs font-semibold transition-all duration-300 align-middle mb-1 sm:mb-0">
                                            Bayar Manual
                                        </button>
                                    @endif

                                    <form action="{{ route('invoices.pay', $invoice->id) }}" method="POST" class="inline-flex align-middle">
                                        @csrf
                                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/25 hover:bg-indigo-500 hover:text-white text-xs font-semibold transition-all duration-300" title="Bayar via Tripay QRIS">
                                            Bayar QRIS
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" class="px-3.5 py-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/25 hover:bg-indigo-500 hover:text-white text-xs font-semibold transition-all duration-300 inline-block align-middle">
                                        Cetak Kuitansi
                                    </a>
                                @endif

                                <!-- Delete Action -->
                                <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST" class="inline-flex align-middle" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tagihan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all duration-300" title="Hapus Tagihan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-500 font-medium">Tidak ada tagihan/invoice yang sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($invoices->hasPages())
            <div class="mt-8 border-t border-slate-800/60 pt-6 flex items-center justify-between">
                <div class="text-xs text-slate-400">
                    Menampilkan {{ $invoices->firstItem() ?? 0 }} - {{ $invoices->lastItem() ?? 0 }} dari {{ $invoices->total() }} tagihan
                </div>
                <div class="flex items-center gap-1.5">
                    @if ($invoices->onFirstPage())
                        <span class="px-3.5 py-2 rounded-xl bg-slate-850/40 border border-slate-800 text-slate-500 text-xs font-semibold select-none cursor-not-allowed">Sebelumnya</span>
                    @else
                        <a href="{{ $invoices->previousPageUrl() }}" class="px-3.5 py-2 rounded-xl bg-slate-800/60 border border-slate-700 text-slate-200 hover:bg-slate-700 text-xs font-semibold transition-colors">Sebelumnya</a>
                    @endif

                    @if ($invoices->hasMorePages())
                        <a href="{{ $invoices->nextPageUrl() }}" class="px-3.5 py-2 rounded-xl bg-slate-800/60 border border-slate-700 text-slate-200 hover:bg-slate-700 text-xs font-semibold transition-colors">Berikutnya</a>
                    @else
                        <span class="px-3.5 py-2 rounded-xl bg-slate-850/40 border border-slate-800 text-slate-500 text-xs font-semibold select-none cursor-not-allowed">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<!-- OVERDUE BROADCAST MODAL -->
<div id="overdue-broadcast-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900 shadow-2xl shadow-slate-950/70">
        <div class="flex items-center justify-between border-b border-slate-800/80 bg-rose-500/10 px-5 py-4">
            <div>
                <h3 class="text-base font-bold text-white">Kirim WA Tunggakan</h3>
                <p class="mt-0.5 text-[11px] text-rose-200/80">Khusus kelompok pelanggan yang sedang difilter</p>
            </div>
            <button type="button" onclick="closeOverdueBroadcastModal()" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white" aria-label="Tutup">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('invoices.broadcast-overdue') }}" method="POST" onsubmit="return confirm('Kirim WA kepada semua pelanggan dalam kelompok tunggakan ini?')" class="space-y-4 p-5">
            @csrf
            <input type="hidden" name="aging" value="{{ $selectedAging }}">
            <div class="rounded-xl border border-rose-500/20 bg-rose-500/5 px-3 py-2.5 text-[11px] leading-relaxed text-slate-300">
                Sistem mengirim satu pesan per pelanggan yang memiliki nomor WhatsApp. Isi pesan dapat memakai placeholder periode, daftar invoice, jumlah tunggakan, dan total nominal.
            </div>
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Template WA</label>
                <select name="wa_template_id" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 focus:border-rose-500 focus:outline-none">
                    <option value="">Format tunggakan bawaan sistem</option>
                    @foreach ($waTemplates as $template)
                        <option value="{{ $template->id }}">{{ $template->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Jeda Minimum (detik)</label>
                    <input type="number" name="delay_min" value="5" min="0" max="3600" required class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 focus:border-rose-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Jeda Maksimum (detik)</label>
                    <input type="number" name="delay_max" value="20" min="0" max="3600" required class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 focus:border-rose-500 focus:outline-none">
                </div>
            </div>
            <p class="text-[10px] text-slate-500">Jeda acak membantu pengiriman tidak dilakukan serentak.</p>
            <div class="flex justify-end gap-2 border-t border-slate-800 pt-4">
                <button type="button" onclick="closeOverdueBroadcastModal()" class="rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-400 transition hover:bg-slate-800 hover:text-white">Batal</button>
                <button type="submit" class="rounded-xl bg-rose-500 px-5 py-2.5 text-xs font-extrabold text-white shadow-lg shadow-rose-950/30 transition hover:bg-rose-400">Kirim Sekarang</button>
            </div>
        </form>
    </div>
</div>

<!-- GENERATE MONTHLY INVOICES MODAL -->
<div id="customer-invoice-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full relative shadow-2xl shadow-slate-950">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-xl font-bold text-white">Generate Per Pelanggan</h3>
                <p class="text-[11px] text-slate-400 mt-1">Buat satu invoice untuk periode tertentu.</p>
            </div>
            <button type="button" onclick="closeCustomerInvoiceModal()" class="text-slate-400 hover:text-white p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="rounded-xl border border-blue-500/20 bg-blue-500/5 px-3 py-2.5 text-[11px] text-slate-300 mb-4">
            Hanya pelanggan aktif, berbayar, dan mode Pascabayar yang tersedia. Jika invoice periode tersebut sudah ada, sistem tidak membuat duplikat.
        </div>
        <form action="{{ route('invoices.generate-customer') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Pelanggan</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                    <select id="generate-customer-status" onchange="filterGenerateCustomers()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="isolated">Isolir</option>
                        <option value="stopped">Berhenti</option>
                    </select>
                    <input id="generate-customer-search" type="search" oninput="filterGenerateCustomers()" placeholder="Cari nama / nomor telepon..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-blue-500">
                </div>
                <select id="generate-customer-select" name="customer_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-3 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                    <option value="">Pilih pelanggan</option>
                    @forelse ($postpaidInvoiceCustomers as $customer)
                        <option value="{{ $customer->id }}" data-status="{{ $customer->status }}" data-search="{{ strtolower($customer->name . ' ' . ($customer->phone ?? '')) }}">{{ $customer->name }}{{ $customer->phone ? ' · ' . $customer->phone : '' }} ({{ ['active' => 'Aktif', 'isolated' => 'Isolir', 'stopped' => 'Berhenti'][$customer->status] ?? $customer->status }})</option>
                    @empty
                        <option value="" disabled>Tidak ada pelanggan Pascabayar aktif</option>
                    @endforelse
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Bulan</label>
                    <select name="month" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-3 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                        @foreach ($monthNames as $val => $name)
                            <option value="{{ $val }}" {{ (int) $val === (int) now()->month ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Tahun</label>
                    <input type="number" name="year" value="{{ now()->year }}" min="2020" max="2100" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-3 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-800 pt-4">
                <button type="button" onclick="closeCustomerInvoiceModal()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-800 hover:text-white">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-500 hover:bg-blue-400 text-xs font-extrabold text-white shadow-lg shadow-blue-950/30">Buat Invoice</button>
            </div>
        </form>
    </div>
</div>

<div id="generate-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full relative shadow-2xl shadow-slate-950 animate-fade-in text-left">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-white">Buat Tagihan Bulanan</h3>
            <button onclick="document.getElementById('generate-modal').classList.add('hidden'); document.getElementById('generate-modal').classList.remove('flex')" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <p class="text-xs text-slate-400 mb-5">Proses ini membuat tagihan baru untuk pelanggan <strong>Aktif</strong> dan <strong>Berbayar</strong>. Pelanggan gratis seperti admin, sosial, keluarga, anak yatim, atau jatah RT otomatis dilewati.</p>
        <form action="{{ route('invoices.generate') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Bulan Tagihan</label>
                <select name="month" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    @foreach ($monthNames as $val => $name)
                        <option value="{{ $val }}" {{ $val == \Carbon\Carbon::now()->month ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Tahun</label>
                <select name="year" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    @foreach ($years as $yr)
                        <option value="{{ $yr }}" {{ $yr == \Carbon\Carbon::now()->year ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                    @if (!in_array(\Carbon\Carbon::now()->year, $years))
                        <option value="{{ \Carbon\Carbon::now()->year }}" selected>{{ \Carbon\Carbon::now()->year }}</option>
                    @endif
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Template WA Tagihan</label>
                <select name="wa_template_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    <option value="">Pesan default sistem</option>
                    @foreach ($waTemplates as $template)
                        <option value="{{ $template->id }}" {{ (string) old('wa_template_id', session('invoice_generate_wa_template_id')) === (string) $template->id ? 'selected' : '' }}>
                            {{ $template->title }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-[11px] text-slate-500">Template ini menjadi pilihan awal saat admin membuka tombol WA Tagihan.</p>
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold py-3 rounded-xl hover:from-indigo-650 hover:to-purple-700 shadow-lg shadow-indigo-600/10 transition-all duration-300">Proses Tagihan Massal</button>
            </div>
        </form>
    </div>
</div>

<!-- ADVANCE PAYMENT MODAL -->
<div id="advance-payment-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-700/70 rounded-3xl w-full max-w-2xl shadow-2xl shadow-slate-950/70 flex flex-col overflow-hidden" style="max-height: 92vh;">
        <div class="px-5 sm:px-6 py-4 border-b border-slate-800/80 bg-gradient-to-r from-teal-500/15 to-cyan-500/5 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-teal-500/15 border border-teal-500/25 flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-white">Bayar di Muka</h3>
                    <p class="text-[11px] text-teal-300/80">Buat dan lunasi beberapa periode sekaligus</p>
                </div>
            </div>
            <button type="button" onclick="closeAdvancePaymentModal()" class="text-slate-400 hover:text-white hover:bg-slate-800 p-2 rounded-xl transition-colors" aria-label="Tutup">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('invoices.advance-payment') }}" method="POST" enctype="multipart/form-data" class="flex min-h-0 flex-col">
            @csrf
            <div class="overflow-y-auto p-5 sm:p-6 space-y-4 text-left">
                <div class="rounded-xl border border-teal-500/20 bg-teal-500/5 px-3.5 py-3 text-[11px] leading-relaxed text-slate-300">
                    Jika invoice periode belum ada, sistem akan membuatnya otomatis. Invoice yang sudah ada dan belum lunas akan ikut dibayar, sedangkan periode yang sudah lunas akan dilewati.
                </div>

                <div>
                    <label for="advance-payment-customer" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Pelanggan</label>
                    <select id="advance-payment-customer" name="customer_id" required onchange="updateAdvancePaymentSummary()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-3 text-sm text-slate-200 focus:outline-none focus:border-teal-500">
                        <option value="">Pilih pelanggan berbayar</option>
                        @forelse ($advancePaymentCustomers as $customer)
                            <option value="{{ $customer->id }}" data-price="{{ (float) ($customer->package?->price ?? 0) }}" data-package="{{ $customer->package?->name ?? 'Tanpa paket' }}">
                                {{ $customer->name }}{{ $customer->phone ? ' · ' . $customer->phone : '' }}
                            </option>
                        @empty
                            <option value="" disabled>Tidak ada pelanggan berbayar aktif</option>
                        @endforelse
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="advance-payment-start-month" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Mulai Bulan</label>
                        <select id="advance-payment-start-month" name="start_month" required onchange="updateAdvancePaymentSummary()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-3 text-sm text-slate-200 focus:outline-none focus:border-teal-500">
                            @foreach ($monthNames as $val => $name)
                                <option value="{{ $val }}" {{ (int) $val === (int) now()->month ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="advance-payment-start-year" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Tahun</label>
                        <input id="advance-payment-start-year" type="number" name="start_year" min="2020" max="2100" value="{{ now()->year }}" required onchange="updateAdvancePaymentSummary()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-3 text-sm text-slate-200 focus:outline-none focus:border-teal-500">
                    </div>
                    <div>
                        <label for="advance-payment-month-count" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Jumlah Bulan</label>
                        <select id="advance-payment-month-count" name="month_count" required onchange="updateAdvancePaymentSummary()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-3 text-sm text-slate-200 focus:outline-none focus:border-teal-500">
                            @for ($count = 1; $count <= 24; $count++)
                                <option value="{{ $count }}" {{ $count === 1 ? 'selected' : '' }}>{{ $count }} bulan</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="advance-payment-method" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Metode Pembayaran</label>
                        <select id="advance-payment-method" name="payment_method" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-3 text-sm text-slate-200 focus:outline-none focus:border-teal-500">
                            <option value="Cash">Cash</option>
                            <option value="Transfer (BCA)">Transfer (BCA)</option>
                            <option value="Transfer (Mandiri)">Transfer (Mandiri)</option>
                            <option value="QRIS">QRIS</option>
                            <option value="E-Wallet">E-Wallet</option>
                        </select>
                    </div>
                    <div>
                        <label for="advance-payment-paid-at" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Tanggal Bayar</label>
                        <input id="advance-payment-paid-at" type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\\TH:i') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-3 text-sm text-slate-200 focus:outline-none focus:border-teal-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-start">
                    <div>
                        <label for="advance-payment-proof" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Bukti Pembayaran <span class="normal-case font-normal text-slate-500">(opsional)</span></label>
                        <input id="advance-payment-proof" type="file" name="payment_proof" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" onchange="previewAdvancePaymentProof(this)" class="block w-full text-[11px] text-slate-400 file:mr-2 file:rounded-lg file:border-0 file:bg-teal-500/15 file:px-3 file:py-2 file:text-[11px] file:font-semibold file:text-teal-300 hover:file:bg-teal-500/25">
                        <img id="advance-payment-proof-preview" class="hidden mt-2 h-16 w-16 rounded-lg border border-slate-700 object-cover" alt="Preview bukti pembayaran">
                    </div>
                    <div>
                        <label for="advance-payment-template" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Template WA Setelah Bayar</label>
                        <select id="advance-payment-template" name="wa_template_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-3 text-sm text-slate-200 focus:outline-none focus:border-teal-500">
                            <option value="">Pesan default sistem</option>
                            @foreach ($waTemplates as $template)
                                <option value="{{ $template->id }}" {{ (string) session('invoice_payment_wa_template_id') === (string) $template->id ? 'selected' : '' }}>{{ $template->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950/50 px-3.5 py-3 cursor-pointer">
                    <input type="checkbox" name="send_wa" value="1" checked class="rounded border-slate-700 bg-slate-950 text-teal-500 focus:ring-teal-500">
                    <span class="text-xs text-slate-300">Kirim notifikasi pembayaran melalui WA Gateway setelah berhasil</span>
                </label>

                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Ringkasan</span>
                        <span id="advance-payment-package" class="text-[11px] text-slate-400">Pilih pelanggan</span>
                    </div>
                    <div id="advance-payment-period-summary" class="text-xs text-slate-300">Belum ada periode dipilih.</div>
                    <div class="mt-3 flex items-center justify-between border-t border-slate-800 pt-3">
                        <span class="text-xs font-semibold text-slate-400">Total dibayar</span>
                        <span id="advance-payment-total" class="text-lg font-extrabold text-teal-300">Rp 0</span>
                    </div>
                </div>
            </div>

            <div class="px-5 sm:px-6 py-4 border-t border-slate-800/80 bg-slate-950/40 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeAdvancePaymentModal()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-teal-500 hover:bg-teal-400 text-slate-950 text-xs font-extrabold shadow-lg shadow-teal-500/20 transition-colors">Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<!-- GLOBAL WHATSAPP MESSAGE MODAL (Compact & Premium) -->
<div id="invoice-wa-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-700/60 rounded-2xl w-full max-w-md shadow-2xl shadow-slate-950/60 flex flex-col overflow-hidden" style="max-height: 90vh;">

        <!-- Header: gradient accent + icon -->
        <div class="relative bg-gradient-to-r from-emerald-600/20 to-teal-600/10 border-b border-slate-800/80 px-5 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.416 1.46 5.867 0 10.642-4.77 10.646-10.643.002-2.846-1.104-5.523-3.11-7.533C17.593 4.432 14.92 3.328 12.073 3.328 6.205 3.328 1.43 8.098 1.426 13.97c-.001 1.936.505 3.826 1.466 5.425l-.962 3.516 3.6-.945zm11.367-7.46c-.327-.164-1.938-.956-2.238-1.066-.3-.11-.519-.164-.738.164-.219.328-.847 1.066-1.039 1.284-.192.219-.383.246-.71.082-.327-.164-1.383-.51-2.634-1.627-.973-.867-1.63-1.939-1.821-2.266-.192-.328-.02-.505.143-.668.147-.146.327-.383.49-.574.164-.191.219-.328.328-.546.11-.219.055-.41-.027-.574-.082-.164-.738-1.777-1.011-2.434-.266-.64-.539-.554-.738-.564-.19-.01-.41-.01-.628-.01-.219 0-.574.082-.875.41-.3.328-1.148 1.12-1.148 2.73 0 1.61 1.175 3.167 1.339 3.385.164.218 2.31 3.528 5.597 4.949.781.338 1.39.54 1.866.69.784.249 1.498.214 2.062.129.629-.094 1.938-.792 2.211-1.558.273-.766.273-1.422.191-1.558-.082-.137-.3-.218-.627-.382z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white leading-tight">Kirim WA Tagihan</h3>
                    <p class="text-[10px] text-emerald-400/80 font-medium">WhatsApp via API</p>
                </div>
            </div>
            <button type="button" onclick="closeInvoiceWaModal()" class="text-slate-500 hover:text-white hover:bg-slate-800 p-1.5 rounded-lg transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-4 overflow-y-auto space-y-3 text-left">

            <!-- Recipient chip -->
            <div class="flex items-center gap-2.5 bg-slate-950/60 border border-slate-800 rounded-xl px-3.5 py-2.5">
                <div class="w-7 h-7 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-white truncate" id="invoice-wa-recipient-name">-</div>
                    <div class="text-[10px] text-indigo-400 font-mono" id="invoice-wa-recipient-phone">-</div>
                </div>
                <span class="text-[9px] font-bold uppercase text-slate-500 bg-slate-800 px-1.5 py-0.5 rounded">Penerima</span>
            </div>

            <!-- Combined bills toggle (hidden unless multi-unpaid) -->
            <div id="combined-bill-container" class="hidden items-center justify-between gap-3 bg-emerald-500/5 border border-emerald-500/20 rounded-xl px-3.5 py-2.5">
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <div>
                        <span class="text-[10px] font-bold text-emerald-400 uppercase">Gabungkan</span>
                        <span class="text-[10px] text-slate-400 ml-1"><span id="unpaid-months-count" class="text-white font-bold">X</span> tagihan belum lunas</span>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" id="combined-bill-checkbox" class="sr-only peer" onchange="handleInvoiceTemplateChange()">
                    <div class="w-9 h-5 bg-slate-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
            </div>

            <!-- Template picker -->
            <div class="grid grid-cols-2 gap-2.5">
                <div class="col-span-2">
                    <label class="block text-[9px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Template Pesan</label>
                    <select id="invoice-wa-template-select" onchange="handleInvoiceTemplateChange()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors">
                        <option value="" selected>📝 Pesan Kustom (Rincian Otomatis)</option>
                        @foreach ($waTemplates as $tmpl)
                            <option value="{{ $tmpl->id }}">{{ $tmpl->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Message textarea -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-[9px] font-bold text-slate-500 uppercase tracking-wider">Isi Pesan</label>
                    <!-- Placeholder hint collapsible -->
                    <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" class="text-[9px] text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-0.5 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Placeholder
                    </button>
                    <div class="hidden absolute right-4 mt-20 z-10 bg-slate-800 border border-slate-700 rounded-xl p-3 text-[10px] text-slate-300 shadow-xl w-64 space-y-1">
                        <div class="font-bold text-indigo-400 mb-1.5">Variabel yang tersedia:</div>
                        <div><code class="text-emerald-400">{nama_pelanggan}</code> → Nama</div>
                        <div><code class="text-emerald-400">{nominal}</code> → Jumlah tagihan</div>
                        <div><code class="text-emerald-400">{nomor_tagihan}</code> → No. Invoice</div>
                        <div><code class="text-emerald-400">{bulan}</code> → Periode bulan</div>
                        <div><code class="text-emerald-400">{nama_odp}</code> → Nama ODP</div>
                    </div>
                </div>
                <textarea id="invoice-wa-message-text" rows="5" placeholder="Isi pesan akan muncul otomatis setelah memilih template…" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors font-mono resize-none leading-relaxed"></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-end gap-2 shrink-0">
            <button type="button" onclick="closeInvoiceWaModal()" class="text-slate-400 hover:text-white text-xs font-semibold px-4 py-2 rounded-lg hover:bg-slate-800 transition-all">
                Batal
            </button>
            <button type="button" onclick="sendInvoiceWaMessage()" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow-lg shadow-emerald-600/20 transition-all duration-200 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.416 1.46 5.867 0 10.642-4.77 10.646-10.643.002-2.846-1.104-5.523-3.11-7.533C17.593 4.432 14.92 3.328 12.073 3.328 6.205 3.328 1.43 8.098 1.426 13.97c-.001 1.936.505 3.826 1.466 5.425l-.962 3.516 3.6-.945zm11.367-7.46c-.327-.164-1.938-.956-2.238-1.066-.3-.11-.519-.164-.738.164-.219.328-.847 1.066-1.039 1.284-.192.219-.383.246-.71.082-.327-.164-1.383-.51-2.634-1.627-.973-.867-1.63-1.939-1.821-2.266-.192-.328-.02-.505.143-.668.147-.146.327-.383.49-.574.164-.191.219-.328.328-.546.11-.219.055-.41-.027-.574-.082-.164-.738-1.777-1.011-2.434-.266-.64-.539-.554-.738-.564-.19-.01-.41-.01-.628-.01-.219 0-.574.082-.875.41-.3.328-1.148 1.12-1.148 2.73 0 1.61 1.175 3.167 1.339 3.385.164.218 2.31 3.528 5.597 4.949.781.338 1.39.54 1.866.69.784.249 1.498.214 2.062.129.629-.094 1.938-.792 2.211-1.558.273-.766.273-1.422.191-1.558-.082-.137-.3-.218-.627-.382z"/>
                </svg>
                Kirim Sekarang
            </button>
        </div>
    </div>
</div>

<!-- GLOBAL PAYMENT MODAL (Premium Design) -->
<div id="global-pay-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-700/60 rounded-2xl w-full max-w-md shadow-2xl shadow-slate-950/60 flex flex-col overflow-hidden" style="max-height: 90vh;">
        
        <!-- Header: gradient accent + icon -->
        <div class="relative bg-gradient-to-r from-emerald-600/20 to-teal-600/10 border-b border-slate-800/80 px-5 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white leading-tight">Konfirmasi Pembayaran</h3>
                    <p class="text-[10px] text-emerald-400/80 font-medium">Proses pelunasan tagihan pelanggan</p>
                </div>
            </div>
            <button type="button" onclick="closePayModal()" class="text-slate-500 hover:text-white hover:bg-slate-800 p-1.5 rounded-lg transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Form inside Modal Body -->
        <form id="global-pay-form" action="{{ route('invoices.pay-batch') }}" method="POST" enctype="multipart/form-data" class="flex flex-col overflow-hidden flex-1" style="max-height: calc(90vh - 65px);">
            @csrf
            
            <!-- Scrollable content area -->
            <div class="p-5 overflow-y-auto space-y-4 text-left flex-1">
                
                <!-- Recipient Info -->
                <div class="bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pelanggan</div>
                    <div class="text-sm font-bold text-white" id="pay-customer-name">-</div>
                </div>

                <!-- Unpaid Invoices Checkboxes -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-2 uppercase tracking-wider">Pilih Tagihan yang Dibayar</label>
                    <div id="pay-invoices-list" class="space-y-2 max-h-40 overflow-y-auto pr-1">
                        <!-- Dynamic list of invoices with checkboxes will be injected here -->
                    </div>
                </div>

                <!-- Payment Method and Bank Selection -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Metode Bayar</label>
                        <select name="payment_type" id="pay-method-type" onchange="togglePaymentFields()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors">
                            <option value="Cash">Cash / Tunai</option>
                            <option value="Transfer">Transfer Bank</option>
                        </select>
                    </div>

                    <div id="bank-select-container" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Bank Tujuan</label>
                        <select name="bank_name" id="pay-bank-name" onchange="updatePaymentMethodInput()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors">
                            <option value="BCA">BCA</option>
                            <option value="Mandiri">Mandiri</option>
                            <option value="BRI">BRI</option>
                            <option value="Jenius">Jenius</option>
                            <option value="BSI">BSI</option>
                        </select>
                    </div>
                </div>

                <!-- Hidden input for final payment_method passed to backend -->
                <input type="hidden" name="payment_method" id="pay-final-method" value="Cash">

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Tanggal Bayar</label>
                    <input
                        type="datetime-local"
                        name="paid_at"
                        id="pay-paid-at"
                        value="{{ now()->format('Y-m-d\TH:i') }}"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors">
                    <p class="mt-1 text-[10px] text-slate-500">Bisa diubah jika pembayaran dicatat untuk tanggal sebelumnya.</p>
                </div>

                <!-- Upload Proof (visible only if Transfer) -->
                <div id="payment-proof-container" class="hidden">
                    <label class="block text-[10px] font-bold text-slate-500 mb-2 uppercase tracking-wider">Upload Bukti Transfer</label>
                    <label for="pay-proof-file" class="relative flex flex-col items-center justify-center gap-2 w-full min-h-28 border border-dashed border-slate-700 hover:border-emerald-500/50 bg-slate-950/60 rounded-xl cursor-pointer transition-all group overflow-hidden">
                        <img id="pay-proof-preview" src="" alt="Preview bukti transfer" class="hidden absolute inset-0 h-full w-full object-cover">
                        <div id="pay-proof-placeholder" class="relative z-10 flex flex-col items-center justify-center gap-2 rounded-xl px-4 py-3 text-center">
                        <svg class="w-6 h-6 text-slate-500 group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span id="pay-proof-label" class="text-xs text-slate-500 group-hover:text-slate-300 transition-colors">Pilih foto bukti transfer</span>
                        </div>
                        <span id="pay-proof-preview-badge" class="hidden absolute bottom-2 left-2 z-10 max-w-[75%] truncate rounded-lg bg-slate-950/80 px-2 py-1 text-[10px] font-bold text-emerald-300 border border-emerald-500/20"></span>
                        <input id="pay-proof-file" type="file" name="payment_proof" accept="image/*" class="hidden" onchange="previewPaymentProof(this)">
                    </label>
                    <button type="button" id="pay-proof-remove" onclick="resetPaymentProof()" class="hidden mt-2 text-[11px] font-bold text-rose-300 hover:text-white rounded-lg border border-rose-500/20 bg-rose-500/10 hover:bg-rose-600 px-3 py-1.5 transition-colors">
                        Hapus bukti transfer
                    </button>
                </div>

                <!-- WA Notification Toggle -->
                <div class="flex items-center justify-between gap-3 bg-indigo-500/5 border border-indigo-500/20 rounded-xl px-3.5 py-2.5">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <div>
                            <span class="text-[10px] font-bold text-indigo-400 uppercase">Kirim WhatsApp</span>
                            <span class="text-[10px] text-slate-400 ml-1">Kirim kwitansi via WA</span>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="send_wa" value="1" checked class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/45 px-3.5 py-3">
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Template WA Pembayaran</label>
                        <a href="{{ route('settings.index', ['tab' => 'wa-templates']) }}" target="_blank" class="text-[10px] font-bold text-emerald-300 hover:text-white">Edit Template</a>
                    </div>
                    <select name="wa_template_id" id="pay-wa-template-id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors">
                        <option value="">Template kwitansi bawaan sistem</option>
                        @foreach ($waTemplates as $tmpl)
                            <option value="{{ $tmpl->id }}" {{ (string) session('invoice_payment_wa_template_id') === (string) $tmpl->id ? 'selected' : '' }}>
                                {{ $tmpl->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-slate-500">Pilih template bayar/lunas dari Pengaturan &gt; Template WA. Kosongkan untuk format kwitansi bawaan.</p>
                </div>

                <!-- Summary Total Box -->
                <div class="bg-indigo-950/40 border border-indigo-900/40 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider">Total Pembayaran</div>
                        <div class="text-[10px] text-slate-400"><span id="pay-selected-count" class="text-white font-bold">1</span> tagihan dipilih</div>
                    </div>
                    <div class="text-xl font-extrabold text-indigo-300" id="pay-total-amount">Rp 0</div>
                </div>

            </div>

            <!-- Footer actions -->
            <div class="px-5 py-3.5 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-end gap-2 shrink-0">
                <button type="button" onclick="closePayModal()" class="text-slate-400 hover:text-white text-xs font-semibold px-4 py-2 rounded-lg hover:bg-slate-800 transition-all">
                    Batal
                </button>
                <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow-lg shadow-emerald-600/20 transition-all duration-200 flex items-center gap-1.5">
                    Konfirmasi Lunas
                </button>
            </div>
        </form>
    </div>
</div>

<!-- PAYMENT DETAIL MODAL -->
<div id="payment-detail-modal" class="hidden fixed inset-0 z-50 items-center justify-center overflow-y-auto bg-slate-950/80 backdrop-blur-sm p-4">
    <div class="payment-detail-panel bg-slate-900 border border-slate-800 rounded-2xl max-w-4xl w-full shadow-2xl shadow-slate-950 overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-base font-bold text-white">Data Pembayaran</h3>
                <p class="text-[11px] text-slate-500" id="payment-detail-invoice">-</p>
            </div>
            <button type="button" onclick="closePaymentDetailModal()" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="payment-detail-body p-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                    <div class="text-[10px] font-bold uppercase text-emerald-300">Sudah Lunas</div>
                    <div class="mt-1 text-lg font-extrabold text-white" id="payment-detail-paid-total">Rp 0</div>
                    <div class="text-[11px] text-emerald-200/80"><span id="payment-detail-paid-count">0</span> invoice</div>
                </div>
                <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3">
                    <div class="text-[10px] font-bold uppercase text-rose-300">Belum Lunas</div>
                    <div class="mt-1 text-lg font-extrabold text-white" id="payment-detail-unpaid-total">Rp 0</div>
                    <div class="text-[11px] text-rose-200/80"><span id="payment-detail-unpaid-count">0</span> invoice</div>
                </div>
                <div class="sm:col-span-2 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-800 bg-slate-800 text-xs">
                    <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Pelanggan</div>
                    <div class="bg-slate-950/60 px-3 py-2 text-slate-200 font-semibold" id="payment-detail-customer">-</div>
                    <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">No. Telepon</div>
                    <div class="bg-slate-950/60 px-3 py-2 text-slate-200" id="payment-detail-phone">-</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-800 bg-slate-800 text-xs">
                <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Nominal</div>
                <div class="bg-slate-950/60 px-3 py-2 text-white font-bold" id="payment-detail-amount">-</div>
                <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Status</div>
                <div class="bg-slate-950/60 px-3 py-2 text-slate-200" id="payment-detail-status">-</div>
                <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Metode</div>
                <div class="bg-slate-950/60 px-3 py-2 text-slate-200" id="payment-detail-method">-</div>
                <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Tanggal Bayar</div>
                <div class="bg-slate-950/60 px-3 py-2 text-slate-200" id="payment-detail-paid-at">-</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-2 rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                <div class="relative">
                    <input
                        type="text"
                        id="payment-history-search"
                        placeholder="Cari invoice, periode, nominal, metode bayar..."
                        onkeydown="if(event.key === 'Enter'){ event.preventDefault(); renderPaymentDetailHistory(); }"
                        class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 pr-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:outline-none">
                    <button type="button" onclick="renderPaymentDetailHistory()" class="absolute right-1 top-1 rounded-md bg-indigo-500/10 p-1.5 text-indigo-300 hover:bg-indigo-500 hover:text-white transition-colors" title="Cari">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </button>
                </div>
                <select id="payment-history-status" onchange="renderPaymentDetailHistory()" class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="paid">Sudah Lunas</option>
                    <option value="unpaid">Belum Lunas</option>
                </select>
                <button type="button" onclick="resetPaymentDetailFilter()" class="rounded-lg border border-slate-800 bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                    Reset
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="w-full min-w-[1020px] text-left text-xs">
                    <thead class="bg-slate-950/90 text-[10px] uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-2 border-b border-r border-slate-800">No. Invoice</th>
                            <th class="px-3 py-2 border-b border-r border-slate-800">Periode</th>
                            <th class="px-3 py-2 border-b border-r border-slate-800">Nominal</th>
                            <th class="px-3 py-2 border-b border-r border-slate-800 text-center">Status</th>
                            <th class="px-3 py-2 border-b border-r border-slate-800">Metode</th>
                            <th class="px-3 py-2 border-b border-r border-slate-800">Tanggal Bayar</th>
                            <th class="px-3 py-2 border-b border-r border-slate-800">Bukti Pembayaran</th>
                            <th class="px-3 py-2 border-b border-slate-800 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="payment-detail-history" class="bg-slate-900/70 text-slate-200">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- EDIT PAYMENT MODAL -->
<div id="edit-payment-modal" class="edit-payment-overlay hidden items-center justify-center overflow-y-auto bg-slate-950/85 backdrop-blur-sm p-4">
    <div class="edit-payment-panel bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full shadow-2xl shadow-slate-950 overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-base font-bold text-white">Edit Pembayaran</h3>
                <p class="text-[11px] text-slate-500" id="edit-payment-invoice">-</p>
            </div>
            <button type="button" onclick="closeEditPaymentModal()" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form id="edit-payment-form" method="POST" enctype="multipart/form-data" class="overflow-y-auto flex-1 min-h-0">
            @csrf
            @method('PATCH')

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-800 bg-slate-800 text-xs">
                    <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Periode</div>
                    <div class="bg-slate-950/60 px-3 py-2 text-slate-200" id="edit-payment-period">-</div>
                    <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Nominal</div>
                    <div class="bg-slate-950/60 px-3 py-2 text-white font-bold" id="edit-payment-amount">-</div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Status Pembayaran</label>
                    <select name="status" id="edit-payment-status" onchange="toggleEditPaymentFields()" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                        <option value="paid">Lunas</option>
                        <option value="unpaid">Belum Lunas</option>
                    </select>
                </div>

                <div id="edit-payment-paid-fields" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Metode Bayar</label>
                        <input type="text" name="payment_method" id="edit-payment-method" placeholder="Cash / Transfer (BCA) / QRIS" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Tanggal Bayar</label>
                        <input type="datetime-local" name="paid_at" id="edit-payment-paid-at" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-2 uppercase tracking-wider">Bukti Pembayaran</label>
                        <div id="edit-payment-current-proof" class="hidden mb-2 rounded-xl border border-slate-800 bg-slate-950/60 p-2"></div>
                        <input type="file" name="payment_proof" accept="image/*" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-500/10 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-indigo-300">
                        <label class="mt-2 flex items-center gap-2 text-[11px] text-slate-400">
                            <input type="checkbox" name="remove_payment_proof" value="1" class="rounded border-slate-700 bg-slate-950 text-rose-500 focus:ring-rose-500">
                            Hapus bukti pembayaran lama
                        </label>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-slate-800 bg-slate-950/40 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeEditPaymentModal()" class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white shadow-lg shadow-indigo-600/20 transition-colors">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- PAYMENT PROMISE MODAL -->
<div id="payment-promise-modal" class="hidden fixed inset-0 z-50 items-center justify-center overflow-y-auto bg-slate-950/80 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full shadow-2xl shadow-slate-950 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Janji Bayar</h3>
                <p class="text-[11px] text-slate-500" id="payment-promise-invoice">-</p>
            </div>
            <button type="button" onclick="closePaymentPromiseModal()" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form id="payment-promise-form" method="POST" class="p-5 space-y-4">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-800 bg-slate-800 text-xs">
                <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Pelanggan</div>
                <div class="bg-slate-950/60 px-3 py-2 text-slate-200 font-semibold" id="payment-promise-customer">-</div>
                <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Periode</div>
                <div class="bg-slate-950/60 px-3 py-2 text-slate-200" id="payment-promise-period">-</div>
                <div class="bg-slate-950/80 px-3 py-2 text-slate-500 font-bold uppercase">Nominal</div>
                <div class="bg-slate-950/60 px-3 py-2 text-white font-bold" id="payment-promise-amount">-</div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Tanggal Janji Bayar</label>
                <input type="date" name="payment_promise_date" id="payment-promise-date" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200 focus:border-amber-500 focus:outline-none">
                <p class="mt-1 text-[10px] text-slate-500">Pada tanggal ini scheduler akan mengirim WA otomatis jika invoice masih belum lunas.</p>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Template WA</label>
                <select name="payment_promise_wa_template_id" id="payment-promise-template" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200 focus:border-amber-500 focus:outline-none">
                    <option value="">Template default janji bayar</option>
                    @foreach ($waTemplates as $tmpl)
                        <option value="{{ $tmpl->id }}">{{ $tmpl->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Catatan</label>
                <textarea name="payment_promise_note" id="payment-promise-note" rows="3" placeholder="Contoh: pelanggan janji bayar setelah gajian / sore hari / minta ditagih ulang." class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200 placeholder:text-slate-600 focus:border-amber-500 focus:outline-none"></textarea>
            </div>

            <div id="payment-promise-sent-info" class="hidden rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-2 text-[11px] font-semibold text-emerald-300"></div>

            <label class="flex items-center gap-2 rounded-xl border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-[11px] text-rose-100">
                <input type="checkbox" name="clear_promise" value="1" id="payment-promise-clear" class="rounded border-rose-500/40 bg-slate-950 text-rose-500 focus:ring-rose-500">
                Hapus janji bayar untuk invoice ini
            </label>

            <div class="flex justify-end gap-2 border-t border-slate-800 pt-4">
                <button type="button" onclick="closePaymentPromiseModal()" class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-xs font-bold text-slate-950 shadow-lg shadow-amber-600/20 transition-colors">Simpan Janji</button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentPayData = null;
    let currentPaymentProofPreviewUrl = null;
    let currentPaymentDetailHistory = [];
    let reopenPaymentDetailAfterEdit = false;

    function openCustomerInvoiceModal() {
        const modal = document.getElementById('customer-invoice-modal');
        if (!modal) return;
        filterGenerateCustomers();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeCustomerInvoiceModal() {
        const modal = document.getElementById('customer-invoice-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function filterGenerateCustomers() {
        const status = document.getElementById('generate-customer-status')?.value || '';
        const search = (document.getElementById('generate-customer-search')?.value || '').toLowerCase().trim();
        const select = document.getElementById('generate-customer-select');
        if (!select) return;

        Array.from(select.options).forEach((option, index) => {
            if (index === 0 || option.disabled) return;
            const matchesStatus = !status || option.dataset.status === status;
            const matchesSearch = !search || (option.dataset.search || option.textContent.toLowerCase()).includes(search);
            option.hidden = !(matchesStatus && matchesSearch);
            if (option.hidden && option.selected) {
                select.value = '';
            }
        });
    }

    function openOverdueBroadcastModal() {
        const modal = document.getElementById('overdue-broadcast-modal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeOverdueBroadcastModal() {
        const modal = document.getElementById('overdue-broadcast-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    const advancePaymentMonthNames = @json($monthNames);

    function openAdvancePaymentModal() {
        const modal = document.getElementById('advance-payment-modal');
        const customerSelect = document.getElementById('advance-payment-customer');
        const paidAt = document.getElementById('advance-payment-paid-at');

        if (customerSelect && customerSelect.options.length > 1 && !customerSelect.value) {
            customerSelect.selectedIndex = 1;
        }
        if (paidAt && !paidAt.value) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            paidAt.value = now.toISOString().slice(0, 16);
        }
        updateAdvancePaymentSummary();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAdvancePaymentModal() {
        const modal = document.getElementById('advance-payment-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function updateAdvancePaymentSummary() {
        const customerSelect = document.getElementById('advance-payment-customer');
        const monthSelect = document.getElementById('advance-payment-start-month');
        const yearInput = document.getElementById('advance-payment-start-year');
        const countSelect = document.getElementById('advance-payment-month-count');
        const totalElement = document.getElementById('advance-payment-total');
        const periodElement = document.getElementById('advance-payment-period-summary');
        const packageElement = document.getElementById('advance-payment-package');
        const selectedOption = customerSelect?.options[customerSelect.selectedIndex];
        const price = Number(selectedOption?.dataset.price || 0);
        const count = Number(countSelect?.value || 1);
        const startMonth = Number(monthSelect?.value || 1);
        const startYear = Number(yearInput?.value || new Date().getFullYear());

        packageElement.textContent = selectedOption?.value
            ? (selectedOption.dataset.package || 'Tanpa paket')
            : 'Pilih pelanggan';
        totalElement.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(price * count)}`;

        if (!selectedOption?.value) {
            periodElement.textContent = 'Belum ada periode dipilih.';
            return;
        }

        const periods = [];
        for (let index = 0; index < count; index++) {
            const date = new Date(startYear, startMonth - 1 + index, 1);
            const month = date.getMonth() + 1;
            const year = date.getFullYear();
            periods.push(`${advancePaymentMonthNames[month] || month} ${year}`);
        }
        periodElement.textContent = periods.join(' · ');
    }

    function previewAdvancePaymentProof(input) {
        const preview = document.getElementById('advance-payment-proof-preview');
        if (!input.files || !input.files[0]) {
            preview.src = '';
            preview.classList.add('hidden');
            return;
        }
        preview.src = URL.createObjectURL(input.files[0]);
        preview.classList.remove('hidden');
    }

    function openPaymentDetailModalFromBtn(btn) {
        try {
            openPaymentDetailModal(JSON.parse(btn.getAttribute('data-payment-detail')));
        } catch (e) {
            console.error('Payment detail parse error:', e);
        }
    }

    function paymentDetailEscape(value) {
        const div = document.createElement('div');
        div.textContent = value || '-';
        return div.innerHTML;
    }

    function paymentDetailEscapeAttr(value) {
        return paymentDetailEscape(value).replace(/"/g, '&quot;');
    }

    function openPaymentDetailModal(data) {
        document.getElementById('payment-detail-invoice').textContent = data.invoice_number || '-';
        document.getElementById('payment-detail-customer').textContent = data.customer_name || '-';
        document.getElementById('payment-detail-phone').textContent = data.customer_phone || '-';
        document.getElementById('payment-detail-amount').textContent = data.amount || '-';
        document.getElementById('payment-detail-status').textContent = data.status || '-';
        document.getElementById('payment-detail-method').textContent = data.payment_method || '-';
        document.getElementById('payment-detail-paid-at').textContent = data.paid_at || '-';
        document.getElementById('payment-detail-paid-total').textContent = data.paid_total || 'Rp 0';
        document.getElementById('payment-detail-unpaid-total').textContent = data.unpaid_total || 'Rp 0';
        document.getElementById('payment-detail-paid-count').textContent = data.paid_count || 0;
        document.getElementById('payment-detail-unpaid-count').textContent = data.unpaid_count || 0;

        currentPaymentDetailHistory = Array.isArray(data.history) ? data.history : [];
        document.getElementById('payment-history-search').value = '';
        document.getElementById('payment-history-status').value = '';
        renderPaymentDetailHistory();

        const modal = document.getElementById('payment-detail-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function resetPaymentDetailFilter() {
        document.getElementById('payment-history-search').value = '';
        document.getElementById('payment-history-status').value = '';
        renderPaymentDetailHistory();
    }

    function renderPaymentDetailHistory() {
        const historyContainer = document.getElementById('payment-detail-history');
        const search = (document.getElementById('payment-history-search').value || '').toLowerCase().trim();
        const status = document.getElementById('payment-history-status').value;
        const history = currentPaymentDetailHistory.filter(item => {
            const statusMatched = !status || item.status_raw === status;
            const text = [
                item.invoice_number,
                item.period,
                item.amount,
                item.status,
                item.payment_method,
                item.paid_at,
            ].join(' ').toLowerCase();

            return statusMatched && (!search || text.includes(search));
        });
        historyContainer.innerHTML = '';

        if (history.length === 0) {
            historyContainer.innerHTML = '<tr><td colspan="8" class="px-3 py-6 text-center text-slate-500">Tidak ada riwayat yang sesuai pencarian/filter.</td></tr>';
        } else {
            history.forEach(item => {
                const isPaid = item.status_raw === 'paid';
                const proofHtml = item.proof_url
                    ? `<a href="${paymentDetailEscapeAttr(item.proof_url)}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[10px] font-bold text-emerald-300 hover:bg-emerald-500 hover:text-white transition-colors">
                            <img src="${paymentDetailEscapeAttr(item.proof_url)}" alt="Bukti pembayaran" class="h-7 w-7 rounded object-cover border border-emerald-500/20" loading="lazy" onerror="this.remove(); this.parentElement.classList.remove('text-emerald-300'); this.parentElement.classList.add('text-rose-300'); this.parentElement.lastChild.textContent='File tidak ada';">
                            Lihat
                       </a>`
                    : '<span class="text-slate-500">Tidak ada</span>';
                const row = document.createElement('tr');
                row.className = 'hover:bg-slate-800/50 transition-colors';
                row.innerHTML = `
                    <td class="px-3 py-2 border-b border-r border-slate-800 font-mono text-indigo-300">${paymentDetailEscape(item.invoice_number)}</td>
                    <td class="px-3 py-2 border-b border-r border-slate-800">${paymentDetailEscape(item.period)}</td>
                    <td class="px-3 py-2 border-b border-r border-slate-800 font-bold text-white">${paymentDetailEscape(item.amount)}</td>
                    <td class="px-3 py-2 border-b border-r border-slate-800 text-center">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold border ${isPaid ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20' : 'bg-rose-500/10 text-rose-300 border-rose-500/20'}">${paymentDetailEscape(item.status)}</span>
                    </td>
                    <td class="px-3 py-2 border-b border-r border-slate-800">${paymentDetailEscape(item.payment_method)}</td>
                    <td class="px-3 py-2 border-b border-r border-slate-800">${paymentDetailEscape(item.paid_at)}</td>
                    <td class="px-3 py-2 border-b border-r border-slate-800">${proofHtml}</td>
                    <td class="px-3 py-2 border-b border-slate-800 text-right">
                        <button type="button" onclick="openEditPaymentModalById(${Number(item.id) || 0})" class="rounded-lg border border-indigo-500/20 bg-indigo-500/10 px-2.5 py-1.5 text-[10px] font-bold text-indigo-300 hover:bg-indigo-500 hover:text-white transition-colors">
                            Edit
                        </button>
                    </td>
                `;
                historyContainer.appendChild(row);
            });
        }
    }

    function closePaymentDetailModal() {
        const modal = document.getElementById('payment-detail-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openEditPaymentModalById(invoiceId) {
        const item = currentPaymentDetailHistory.find(history => Number(history.id) === Number(invoiceId));
        if (!item) {
            alert('Data pembayaran tidak ditemukan di history.');
            return;
        }

        openEditPaymentModal(item);
    }

    function openEditPaymentModal(item) {
        const form = document.getElementById('edit-payment-form');
        form.action = item.update_url || '#';
        form.reset();

        document.getElementById('edit-payment-invoice').textContent = item.invoice_number || '-';
        document.getElementById('edit-payment-period').textContent = item.period || '-';
        document.getElementById('edit-payment-amount').textContent = item.amount || '-';
        document.getElementById('edit-payment-status').value = item.status_raw || 'unpaid';
        document.getElementById('edit-payment-method').value = item.payment_method && item.payment_method !== '-' ? item.payment_method : 'Cash';
        document.getElementById('edit-payment-paid-at').value = item.paid_at_input || formatLocalDateTimeForInput(new Date());

        const proofBox = document.getElementById('edit-payment-current-proof');
        if (item.proof_url) {
            proofBox.innerHTML = `
                <a href="${paymentDetailEscapeAttr(item.proof_url)}" target="_blank" class="flex items-center gap-3 text-xs text-slate-200 hover:text-white">
                    <img src="${paymentDetailEscapeAttr(item.proof_url)}" alt="Bukti pembayaran" class="h-14 w-14 rounded-lg object-cover border border-slate-700" loading="lazy" onerror="this.remove(); this.parentElement.querySelector('span').textContent='File bukti pembayaran tidak ditemukan';">
                    <span class="font-semibold text-emerald-300">Lihat bukti pembayaran saat ini</span>
                </a>
            `;
            proofBox.classList.remove('hidden');
        } else {
            proofBox.innerHTML = '';
            proofBox.classList.add('hidden');
        }

        toggleEditPaymentFields();

        const paymentDetailModal = document.getElementById('payment-detail-modal');
        reopenPaymentDetailAfterEdit = paymentDetailModal && !paymentDetailModal.classList.contains('hidden');
        if (reopenPaymentDetailAfterEdit) {
            paymentDetailModal.classList.add('hidden');
            paymentDetailModal.classList.remove('flex');
        }

        const modal = document.getElementById('edit-payment-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function toggleEditPaymentFields() {
        const status = document.getElementById('edit-payment-status').value;
        const paidFields = document.getElementById('edit-payment-paid-fields');

        if (status === 'paid') {
            paidFields.classList.remove('hidden');
        } else {
            paidFields.classList.add('hidden');
        }
    }

    function closeEditPaymentModal() {
        const modal = document.getElementById('edit-payment-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        if (reopenPaymentDetailAfterEdit) {
            const paymentDetailModal = document.getElementById('payment-detail-modal');
            paymentDetailModal.classList.remove('hidden');
            paymentDetailModal.classList.add('flex');
            reopenPaymentDetailAfterEdit = false;
        }
    }

    function openPaymentPromiseModalFromBtn(btn) {
        try {
            openPaymentPromiseModal(JSON.parse(btn.getAttribute('data-payment-promise')));
        } catch (e) {
            console.error('Payment promise modal parse error:', e);
        }
    }

    function openPaymentPromiseModal(data) {
        const form = document.getElementById('payment-promise-form');
        form.action = data.update_url || '#';
        form.reset();

        document.getElementById('payment-promise-invoice').textContent = data.invoice_number || '-';
        document.getElementById('payment-promise-customer').textContent = data.customer_name || '-';
        document.getElementById('payment-promise-period').textContent = data.period || '-';
        document.getElementById('payment-promise-amount').textContent = data.amount || '-';
        document.getElementById('payment-promise-date').value = data.promise_date || '';
        document.getElementById('payment-promise-note').value = data.promise_note || '';
        document.getElementById('payment-promise-template').value = data.wa_template_id ? String(data.wa_template_id) : '';
        document.getElementById('payment-promise-clear').checked = false;

        const sentInfo = document.getElementById('payment-promise-sent-info');
        if (data.sent_at) {
            sentInfo.textContent = 'WA janji bayar sudah terkirim pada ' + data.sent_at + '. Jika tanggal/catatan diubah, status kirim akan direset.';
            sentInfo.classList.remove('hidden');
        } else {
            sentInfo.textContent = '';
            sentInfo.classList.add('hidden');
        }

        const modal = document.getElementById('payment-promise-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePaymentPromiseModal() {
        const modal = document.getElementById('payment-promise-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function resetPaymentProof() {
        const input = document.getElementById('pay-proof-file');
        const label = document.getElementById('pay-proof-label');
        const preview = document.getElementById('pay-proof-preview');
        const placeholder = document.getElementById('pay-proof-placeholder');
        const badge = document.getElementById('pay-proof-preview-badge');
        const removeButton = document.getElementById('pay-proof-remove');

        if (currentPaymentProofPreviewUrl) {
            URL.revokeObjectURL(currentPaymentProofPreviewUrl);
            currentPaymentProofPreviewUrl = null;
        }

        if (input) input.value = '';
        if (label) label.textContent = 'Pilih foto bukti transfer';
        if (preview) {
            preview.src = '';
            preview.classList.add('hidden');
        }
        if (placeholder) {
            placeholder.classList.remove('bg-slate-950/70');
        }
        if (badge) {
            badge.textContent = '';
            badge.classList.add('hidden');
        }
        if (removeButton) {
            removeButton.classList.add('hidden');
        }
    }

    function previewPaymentProof(input) {
        const file = input.files && input.files[0] ? input.files[0] : null;
        const label = document.getElementById('pay-proof-label');
        const preview = document.getElementById('pay-proof-preview');
        const placeholder = document.getElementById('pay-proof-placeholder');
        const badge = document.getElementById('pay-proof-preview-badge');
        const removeButton = document.getElementById('pay-proof-remove');

        if (!file) {
            resetPaymentProof();
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert('File bukti transfer harus berupa gambar.');
            resetPaymentProof();
            return;
        }

        if (currentPaymentProofPreviewUrl) {
            URL.revokeObjectURL(currentPaymentProofPreviewUrl);
        }

        currentPaymentProofPreviewUrl = URL.createObjectURL(file);
        preview.src = currentPaymentProofPreviewUrl;
        preview.classList.remove('hidden');
        placeholder.classList.add('bg-slate-950/70');
        label.textContent = 'Klik untuk ganti foto';
        badge.textContent = file.name;
        badge.classList.remove('hidden');
        removeButton.classList.remove('hidden');
    }

    function formatLocalDateTimeForInput(date) {
        const pad = value => String(value).padStart(2, '0');

        return [
            date.getFullYear(),
            pad(date.getMonth() + 1),
            pad(date.getDate())
        ].join('-') + 'T' + [
            pad(date.getHours()),
            pad(date.getMinutes())
        ].join(':');
    }

    function openPayModal(data) {
        currentPayData = data;
        
        // Populate Customer Name
        document.getElementById('pay-customer-name').textContent = data.customer.name;
        document.getElementById('pay-paid-at').value = formatLocalDateTimeForInput(new Date());
        
        // Reset file input and label
        resetPaymentProof();
        
        // Reset payment type & bank selection
        const typeSelect = document.getElementById('pay-method-type');
        typeSelect.value = 'Cash';
        const bankSelect = document.getElementById('pay-bank-name');
        bankSelect.value = 'BCA';
        const paymentWaTemplateSelect = document.getElementById('pay-wa-template-id');
        if (paymentWaTemplateSelect) {
            paymentWaTemplateSelect.value = defaultPaymentWaTemplateId ? String(defaultPaymentWaTemplateId) : "";
        }
        
        togglePaymentFields();
        
        // Generate Checkboxes for Unpaid Invoices
        const listContainer = document.getElementById('pay-invoices-list');
        listContainer.innerHTML = '';
        
        if (data.unpaid_invoices && data.unpaid_invoices.length > 0) {
            data.unpaid_invoices.forEach(inv => {
                const isChecked = inv.id === data.id ? 'checked' : '';
                
                const itemDiv = document.createElement('div');
                itemDiv.className = 'flex items-center justify-between bg-slate-950/40 border border-slate-850 hover:bg-slate-950/80 hover:border-slate-800 p-3 rounded-xl transition-all';
                itemDiv.innerHTML = `
                    <label class="flex items-center gap-3 cursor-pointer flex-1 select-none">
                        <input type="checkbox" name="invoice_ids[]" value="${inv.id}" ${isChecked} onchange="calculatePayTotal()" class="rounded border-slate-800 bg-slate-950 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-slate-900 w-4 h-4 cursor-pointer">
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-white block truncate">${inv.month_name} ${inv.year}</span>
                            <span class="text-[10px] text-slate-400 block font-mono">${inv.invoice_number}</span>
                        </div>
                    </label>
                    <span class="text-xs font-extrabold text-indigo-400">Rp ${inv.amount_formatted}</span>
                `;
                listContainer.appendChild(itemDiv);
            });
        }
        
        calculatePayTotal();

        // Show Modal
        const modal = document.getElementById('global-pay-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function openPayModalFromBtn(btn) {
        try {
            const data = JSON.parse(btn.getAttribute('data-invoice-pay'));
            openPayModal(data);
        } catch(e) {
            console.error('Pay modal parse error:', e);
        }
    }

    function closePayModal() {
        const modal = document.getElementById('global-pay-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetPaymentProof();
        currentPayData = null;
    }

    function togglePaymentFields() {
        const typeSelect = document.getElementById('pay-method-type');
        const type = typeSelect.value;
        
        const bankContainer = document.getElementById('bank-select-container');
        const proofContainer = document.getElementById('payment-proof-container');
        
        if (type === 'Transfer') {
            bankContainer.classList.remove('hidden');
            proofContainer.classList.remove('hidden');
        } else {
            bankContainer.classList.add('hidden');
            proofContainer.classList.add('hidden');
            resetPaymentProof();
        }
        
        updatePaymentMethodInput();
    }

    function updatePaymentMethodInput() {
        const type = document.getElementById('pay-method-type').value;
        const bank = document.getElementById('pay-bank-name').value;
        const finalInput = document.getElementById('pay-final-method');
        
        if (type === 'Transfer') {
            finalInput.value = 'Transfer (' + bank + ')';
        } else {
            finalInput.value = 'Cash';
        }
    }

    function calculatePayTotal() {
        if (!currentPayData) return;
        
        const checkboxes = document.querySelectorAll('#pay-invoices-list input[type="checkbox"]:checked');
        let total = 0;
        let count = checkboxes.length;
        
        checkboxes.forEach(cb => {
            const invId = parseInt(cb.value);
            const invoice = currentPayData.unpaid_invoices.find(i => i.id === invId);
            if (invoice) {
                total += parseInt(invoice.amount);
            }
        });
        
        document.getElementById('pay-selected-count').textContent = count;
        document.getElementById('pay-total-amount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    }

    let currentInvoice = null;
    const waTemplates = @json($waTemplates);
    const defaultGenerateWaTemplateId = @json(session('invoice_generate_wa_template_id'));
    const defaultPaymentWaTemplateId = @json(session('invoice_payment_wa_template_id'));

    function openInvoiceWaModal(invoice) {
        currentInvoice = invoice;
        document.getElementById('invoice-wa-recipient-name').textContent = invoice.customer.name;
        document.getElementById('invoice-wa-recipient-phone').textContent = invoice.customer.phone;
        
        // Reset template dropdown
        const templateSelect = document.getElementById('invoice-wa-template-select');
        templateSelect.value = defaultGenerateWaTemplateId ? String(defaultGenerateWaTemplateId) : "";
        
        // Show/hide combined option
        const combinedBillContainer = document.getElementById('combined-bill-container');
        const combinedCheckbox = document.getElementById('combined-bill-checkbox');
        
        if (invoice.status === 'unpaid' && invoice.unpaid_invoices && invoice.unpaid_invoices.length > 1) {
            combinedBillContainer.classList.remove('hidden');
            combinedBillContainer.classList.add('flex');
            document.getElementById('unpaid-months-count').textContent = invoice.unpaid_invoices.length;
            // Auto-check combined invoices for convenience
            combinedCheckbox.checked = true;
        } else {
            combinedBillContainer.classList.add('hidden');
            combinedBillContainer.classList.remove('flex');
            combinedCheckbox.checked = false;
        }

        
        // Update message content
        handleInvoiceTemplateChange();
        
        // Show modal
        const modal = document.getElementById('invoice-wa-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // Bridge function — reads JSON from data-wa-invoice attribute and calls openInvoiceWaModal
    function openInvoiceWaModalFromBtn(btn) {
        try {
            const data = JSON.parse(btn.getAttribute('data-wa-invoice'));
            openInvoiceWaModal(data);
        } catch(e) {
            console.error('WA modal parse error:', e);
        }
    }

    function closeInvoiceWaModal() {
        const modal = document.getElementById('invoice-wa-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentInvoice = null;
    }

    function handleInvoiceTemplateChange() {
        if (!currentInvoice) return;
        
        const templateSelect = document.getElementById('invoice-wa-template-select');
        const templateId = templateSelect.value;
        const combinedCheckbox = document.getElementById('combined-bill-checkbox');
        const isCombined = combinedCheckbox && combinedCheckbox.checked;
        
        let content = "";
        
        // Let's compile data depending on whether combined is checked or not
        let invoiceNumStr = "";
        let monthStr = "";
        let nominalStr = "";
        let detailsListStr = "";
        let totalAmount = 0;
        
        if (isCombined && currentInvoice.unpaid_invoices && currentInvoice.unpaid_invoices.length > 1) {
            const list = currentInvoice.unpaid_invoices;
            const invoiceNumbers = list.map(i => i.invoice_number);
            const months = list.map(i => i.month_name);
            const totalSum = list.reduce((sum, i) => sum + parseInt(i.amount), 0);
            
            invoiceNumStr = invoiceNumbers.join(', ');
            // Format months nicely: "April, Mei, Juni " + year
            const yearStr = list[0].year;
            monthStr = months.join(', ') + ' ' + yearStr;
            totalAmount = totalSum;
            
            detailsListStr = "Rincian tagihan belum lunas:\n";
            list.forEach(i => {
                detailsListStr += `- Bulan ${i.month_name} ${i.year} (${i.invoice_number}): Rp ${i.amount_formatted}\n`;
            });
        } else {
            invoiceNumStr = currentInvoice.invoice_number;
            monthStr = currentInvoice.month_name + ' ' + currentInvoice.year;
            totalAmount = parseInt(currentInvoice.amount.replace(/[^0-9]/g, ''));
            
            detailsListStr = `- Tagihan ${currentInvoice.month_name} ${currentInvoice.year} (${currentInvoice.invoice_number}): Rp ${currentInvoice.amount}\n`;
        }
        
        // Format nominal total sum
        nominalStr = new Intl.NumberFormat('id-ID').format(totalAmount);

        const formatDueDate = (item) => {
            const billingDay = Math.max(1, Math.min(parseInt(currentInvoice.customer.billing_day || 1), new Date(item.year, item.month, 0).getDate()));
            return String(billingDay).padStart(2, '0') + ' ' + new Date(item.year, item.month - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        };
        const dueDateStr = isCombined && currentInvoice.unpaid_invoices && currentInvoice.unpaid_invoices.length > 1
            ? currentInvoice.unpaid_invoices.map(item => formatDueDate(item)).join(', ')
            : formatDueDate(currentInvoice);
        
        if (templateId) {
            // Standard template substitution
            const template = waTemplates.find(t => t.id == templateId);
            if (template) {
                content = template.content;
                content = content.replace(/{nama_pelanggan}/g, currentInvoice.customer.name);
                content = content.replace(/{nama_odp}/g, currentInvoice.customer.odp_name || 'Tidak Terhubung');
                content = content.replace(/{nomor_tagihan}/g, invoiceNumStr);
                content = content.replace(/{bulan}/g, monthStr);
                content = content.replace(/{tanggal_jatuh_tempo}/g, dueDateStr);
                content = content.replace(/{nominal}/g, nominalStr);
            }
        } else {
            // Default Custom Message showing detailed breakdown
            if (currentInvoice.status === 'paid') {
                content = `Halo ${currentInvoice.customer.name},\n\nPembayaran tagihan nomor ${currentInvoice.invoice_number} untuk periode ${currentInvoice.month_name} ${currentInvoice.year} sebesar Rp ${currentInvoice.amount} telah kami terima dan dinyatakan LUNAS.\n\nTerima kasih atas kerja samanya.`;
            } else {
                if (isCombined && currentInvoice.unpaid_invoices && currentInvoice.unpaid_invoices.length > 1) {
                    content = `Halo ${currentInvoice.customer.name},\n\nBerikut rincian tunggakan tagihan internet Anda yang belum terbayar:\n${detailsListStr}\nTotal Tunggakan: ${currentInvoice.unpaid_invoices.length} Bulan (Total Rp ${nominalStr})\n\nMohon segera melakukan pembayaran. Terima kasih atas perhatian dan kerja samanya.`;
                } else {
                    content = `Halo ${currentInvoice.customer.name},\n\nBerikut rincian tagihan internet Anda yang belum terbayar:\n${detailsListStr}\nTotal Tagihan: Rp ${nominalStr}\n\nMohon segera melakukan pembayaran sebelum tanggal jatuh tempo. Terima kasih atas perhatiannya.`;
                }
            }
        }
        
        document.getElementById('invoice-wa-message-text').value = content;
    }

    async function sendInvoiceWaMessage() {
        if (!currentInvoice) return;
        const message = document.getElementById('invoice-wa-message-text').value;
        if (!message.trim()) {
            alert('Pesan tidak boleh kosong!');
            return;
        }
        
        // Format phone: remove leading zero and replace with 62
        let phone = currentInvoice.customer.phone.trim().replace(/[-+\s]/g, '');
        if (phone.startsWith('0')) {
            phone = '62' + phone.substring(1);
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        try {
            const response = await fetch('{{ route("wa.send") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    phone: phone,
                    message: message,
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
                alert('Gagal kirim WA Gateway: ' + (data.message || 'Cek token dan konfigurasi WA Gateway.'));
                return;
            }

            alert(data.message || 'Pesan berhasil dikirim via WA Gateway.');
            closeInvoiceWaModal();
        } catch (error) {
            alert('Gagal koneksi ke server WA Gateway: ' + error.message);
        }
    }

    // --- Bulk WA / QRIS Logic ---
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        const bulkActions = document.getElementById('bulk-actions');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkActions() {
            const checked = document.querySelectorAll('.invoice-checkbox:checked');
            selectedCount.textContent = checked.length;
            if (checked.length > 0) {
                bulkActions.classList.remove('hidden');
            } else {
                bulkActions.classList.add('hidden');
                if(selectAll) selectAll.checked = false;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkActions();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkActions);
        });
    });

    function submitBulkDelete(event) {
        const checked = Array.from(document.querySelectorAll('.invoice-checkbox:checked'));
        const inputContainer = document.getElementById('bulk-delete-inputs');

        if (!checked.length) {
            event.preventDefault();
            alert('Pilih minimal satu tagihan terlebih dahulu.');
            return false;
        }

        inputContainer.innerHTML = '';
        checked.forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'invoice_ids[]';
            input.value = checkbox.value;
            inputContainer.appendChild(input);
        });

        return confirm(`Hapus ${checked.length} tagihan yang dipilih? Data yang sudah dihapus tidak dapat dikembalikan.`);
    }

    function openBulkWaModal() {
        const checked = document.querySelectorAll('.invoice-checkbox:checked');
        if (checked.length === 0) return;

        let customerId = null;
        let isSameCustomer = true;
        let totalAmount = 0;
        let invoiceIds = [];

        checked.forEach(cb => {
            const id = cb.getAttribute('data-customer-id');
            const amt = parseInt(cb.getAttribute('data-amount'));
            if (customerId === null) {
                customerId = id;
            } else if (customerId !== id) {
                isSameCustomer = false;
            }
            totalAmount += amt;
            invoiceIds.push(cb.value);
        });

        if (!isSameCustomer) {
            alert('Untuk mengirim tagihan gabungan, pastikan Anda hanya memilih tagihan dari SATU pelanggan yang sama.');
            return;
        }

        if (!confirm('Anda akan membuat 1 Barcode QRIS gabungan sebesar Rp ' + new Intl.NumberFormat('id-ID').format(totalAmount) + ' untuk ' + invoiceIds.length + ' tagihan ini. Lanjutkan?')) {
            return;
        }

        // Submit via AJAX
        fetch('{{ route("invoices.bulkWaQris") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                invoice_ids: invoiceIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Berhasil! ' + data.message);
                // Uncheck all
                document.querySelectorAll('.invoice-checkbox:checked').forEach(cb => cb.checked = false);
                document.getElementById('select-all').checked = false;
                document.getElementById('bulk-actions').classList.add('hidden');
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(err => {
            alert('Terjadi kesalahan koneksi server.');
            console.error(err);
        });
    }
</script>

{{-- ====== INVOICE IMPORT MODAL ====== --}}
<div id="invoice-import-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-700/60 rounded-2xl w-full max-w-md shadow-2xl shadow-slate-950/60 overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-amber-600/20 to-orange-600/10 border-b border-slate-800/80 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white leading-tight">Import Tagihan</h3>
                    <p class="text-[10px] text-amber-400/80 font-medium">Upload file .xls atau .csv</p>
                </div>
            </div>
            <button type="button"
                onclick="document.getElementById('invoice-import-modal').classList.add('hidden'); document.getElementById('invoice-import-modal').classList.remove('flex')"
                class="text-slate-500 hover:text-white hover:bg-slate-800 p-1.5 rounded-lg transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <form action="{{ route('invoices.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf

            {{-- Petunjuk --}}
            <div class="bg-indigo-500/5 border border-indigo-500/20 rounded-xl p-3.5 text-xs text-slate-400 space-y-1.5 leading-relaxed">
                <div class="font-bold text-indigo-400 text-[11px] uppercase tracking-wide mb-1.5">Format Kolom yang Didukung</div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 font-mono text-[10px]">
                    <div><span class="text-slate-500">Kol A:</span> <span class="text-slate-300">No. Invoice</span> <span class="text-slate-600">(opsional)</span></div>
                    <div><span class="text-slate-500">Kol B:</span> <span class="text-slate-300">Nama Pelanggan</span></div>
                    <div><span class="text-slate-500">Kol C:</span> <span class="text-slate-300">No. Telepon</span> ⭐</div>
                    <div><span class="text-slate-500">Kol D:</span> <span class="text-slate-300">Paket</span></div>
                    <div><span class="text-slate-500">Kol E:</span> <span class="text-slate-300">Bulan</span> ⭐</div>
                    <div><span class="text-slate-500">Kol F:</span> <span class="text-slate-300">Tahun</span> ⭐</div>
                    <div><span class="text-slate-500">Kol G:</span> <span class="text-slate-300">Jumlah (Rp)</span></div>
                    <div><span class="text-slate-500">Kol H:</span> <span class="text-slate-300">Status</span> <span class="text-slate-600">(Lunas/Belum)</span></div>
                    <div><span class="text-slate-500">Kol I:</span> <span class="text-slate-300">Metode Bayar</span></div>
                    <div><span class="text-slate-500">Kol J:</span> <span class="text-slate-300">Tgl Bayar</span></div>
                </div>
                <div class="border-t border-slate-800 pt-2 mt-1 space-y-1">
                    <div class="flex gap-1.5"><span class="text-amber-400">↓</span> <a href="{{ route('invoices.export') }}" class="text-amber-400 hover:text-amber-300 font-semibold underline">Download contoh file</a> dari data yang sudah ada.</div>
                    <div class="flex gap-1.5"><span class="text-emerald-400">✓</span> Jika tagihan sudah ada (bulan+tahun sama), akan <strong class="text-slate-300">diperbarui</strong>.</div>
                    <div class="flex gap-1.5"><span class="text-emerald-400">✓</span> Bulan bisa angka (1-12) atau nama (Januari, dst).</div>
                </div>
            </div>

            {{-- File Input --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-2 uppercase tracking-wider">Pilih File</label>
                <label for="invoice-import-file" class="flex flex-col items-center justify-center gap-2 w-full h-28 border-2 border-dashed border-slate-700 hover:border-amber-500/50 bg-slate-950/60 rounded-xl cursor-pointer transition-all group">
                    <svg class="w-7 h-7 text-slate-500 group-hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span id="invoice-import-label" class="text-xs text-slate-500 group-hover:text-slate-300 transition-colors">Klik untuk pilih file .xls / .csv</span>
                    <input id="invoice-import-file" type="file" name="import_file" accept=".xls,.csv,.txt,.xlsx" class="hidden"
                        onchange="document.getElementById('invoice-import-label').textContent = this.files[0] ? this.files[0].name : 'Klik untuk pilih file .xls / .csv'">
                </label>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button"
                    onclick="document.getElementById('invoice-import-modal').classList.add('hidden'); document.getElementById('invoice-import-modal').classList.remove('flex')"
                    class="text-slate-400 hover:text-white text-xs font-semibold px-4 py-2 rounded-lg hover:bg-slate-800 transition-all">
                    Batal
                </button>
                <button type="submit"
                    class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow-lg shadow-amber-600/20 transition-all duration-200 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Mulai Import
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
