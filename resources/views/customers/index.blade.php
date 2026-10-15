@extends('layouts.app')

@section('content')
@php
    $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $billingTypeLabels = \App\Models\Customer::billingTypeLabels();
    $billingModeLabels = \App\Models\Customer::billingModeLabels();
    $customerStatusLabels = [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'isolated' => 'Isolir',
        'stopped' => 'Berhenti',
    ];
    $customerStatusClasses = [
        'active' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
        'inactive' => 'bg-slate-800 text-slate-400 border-slate-700',
        'isolated' => 'bg-amber-500/10 text-amber-300 border-amber-500/20',
        'stopped' => 'bg-rose-500/10 text-rose-300 border-rose-500/20',
    ];
    $customerStatusDots = [
        'active' => 'bg-emerald-400',
        'inactive' => 'bg-slate-500',
        'isolated' => 'bg-amber-400',
        'stopped' => 'bg-rose-400',
    ];
    $customerActionLabels = [
        'isolir' => 'Isolir',
        'berhenti' => 'Berhenti',
        'aktif_kembali' => 'Aktif Kembali',
        'nonaktif' => 'Nonaktif',
    ];
    $activeCustomerFilters = collect([
        request('search'),
        request('filter_name'),
        request('filter_phone'),
        request('filter_address'),
        request('filter_odp'),
        request('filter_package'),
        request('filter_status'),
        request('filter_billing_type'),
        request('filter_billing_mode'),
        request('filter_billing_day'),
    ])->filter(fn ($value) => filled($value))->isNotEmpty();
@endphp
<style>
    .notebook-table {
        border-collapse: collapse;
        font-size: 12px;
        line-height: 1;
        min-width: 1180px;
    }
    .notebook-table thead tr {
        background: rgba(15, 23, 42, 0.92);
    }
    .notebook-table th,
    .notebook-table td {
        border: 1px solid rgba(51, 65, 85, 0.78);
        padding: 2px 5px !important;
        vertical-align: middle;
    }
    .notebook-table th {
        color: #cbd5e1;
        font-size: 8.5px;
        letter-spacing: 0;
        white-space: nowrap;
        padding-bottom: 3px !important;
        padding-top: 3px !important;
    }
    .notebook-table tbody tr:nth-child(even) {
        background: rgba(15, 23, 42, 0.22);
    }
    .notebook-table tbody tr:hover {
        background: rgba(79, 70, 229, 0.12);
    }
    .notebook-table .customer-select-checkbox,
    .notebook-table #select-all-customers {
        height: 12px;
        width: 12px;
    }
    .notebook-table td:last-child button,
    .notebook-table td:last-child a {
        border-radius: 7px;
        padding: 3px !important;
    }
    .notebook-table td:last-child svg {
        height: 12px;
        width: 12px;
    }
    .notebook-table .inline-flex {
        border-radius: 6px;
    }
    .notebook-table td {
        height: 22px;
    }
    .notebook-table td .font-semibold,
    .notebook-table td .font-bold {
        line-height: 1.15;
    }
    .notebook-table td [class*="rounded-full"],
    .notebook-table td [class*="rounded-lg"] {
        padding-bottom: 1px;
        padding-top: 1px;
    }
    .notebook-table .space-x-1\.5 > :not([hidden]) ~ :not([hidden]) {
        margin-left: 3px;
    }
    .notebook-table .customer-actions-col {
        position: sticky;
        right: 0;
        z-index: 15;
        min-width: 292px;
        background: rgba(15, 23, 42, 0.96);
        box-shadow: -14px 0 22px -20px rgba(0, 0, 0, 0.95);
    }
    .notebook-table thead .customer-actions-col {
        z-index: 25;
        background: rgba(15, 23, 42, 0.98);
    }
    .notebook-table tbody tr:nth-child(even) .customer-actions-col {
        background: rgba(15, 23, 42, 0.98);
    }
    .notebook-table tbody tr:hover .customer-actions-col {
        background: rgb(30, 41, 59);
    }
    .notebook-table .cell-tight {
        padding: 2px 5px !important;
    }
    .notebook-table .status-mini {
        gap: 2px;
    }
    .notebook-table .status-mini > span:first-child {
        padding: 1px 5px !important;
        font-size: 10px;
    }
    .notebook-table .status-mini > span:last-child {
        font-size: 9px;
        line-height: 1;
    }

    .customers-page .page-actions a,
    .customers-page .page-actions button {
        min-height: 2.25rem;
        border-radius: .65rem !important;
        padding: .5rem .75rem !important;
        font-size: 12px !important;
    }

    .customers-page .customer-summary-card {
        border-radius: .75rem !important;
        padding: .75rem 1rem !important;
    }

    .customers-page #customer-filter-panel {
        border-radius: .75rem !important;
        padding: .65rem !important;
    }

    .customers-page #customer-filter-panel input,
    .customers-page #customer-filter-panel select {
        min-height: 2rem !important;
        padding: .38rem .6rem !important;
        font-size: 12px !important;
    }

    .customers-page .fixed.inset-0 form > .flex-1,
    .customers-page .fixed.inset-0 > div > .p-6,
    .customers-page .fixed.inset-0 > div > .p-5 {
        padding: 1rem !important;
    }

    .customers-page .fixed.inset-0 h3 {
        font-size: 1rem !important;
    }

    .customers-page .fixed.inset-0 h4 {
        font-size: .72rem !important;
    }

    .customers-page .fixed.inset-0 input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
    .customers-page .fixed.inset-0 select,
    .customers-page .fixed.inset-0 textarea {
        min-height: 2.2rem !important;
        padding: .5rem .7rem !important;
        font-size: 12px !important;
    }

    .customers-page .fixed.inset-0 .space-y-4 {
        row-gap: .7rem !important;
    }

    @media (max-width: 767px) {
        .notebook-table {
            border-collapse: separate;
            border-spacing: 0 .75rem;
            font-size: 12px;
        }

        .notebook-table thead {
            display: none;
        }

        .notebook-table,
        .notebook-table tbody,
        .notebook-table tr,
        .notebook-table td {
            display: block;
            width: 100%;
        }

        .notebook-table tr {
            overflow: hidden;
            border: 1px solid rgba(51, 65, 85, .78);
            border-radius: 1rem;
            background: rgba(15, 23, 42, .72);
            box-shadow: 0 14px 28px -24px rgba(2, 6, 23, .95);
        }

        .notebook-table td {
            height: auto;
            border: 0;
            border-bottom: 1px solid rgba(51, 65, 85, .55);
            padding: .7rem .85rem !important;
        }

        .notebook-table td:last-child {
            border-bottom: 0;
        }

        .notebook-table td:not(:first-child):not(.customer-actions-col)::before {
            display: block;
            margin-bottom: .25rem;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .notebook-table td:nth-child(2)::before { content: "Nama"; }
        .notebook-table td:nth-child(3)::before { content: "Kontak"; }
        .notebook-table td:nth-child(4)::before { content: "Alamat"; }
        .notebook-table td:nth-child(5)::before { content: "ODP"; }
        .notebook-table td:nth-child(6)::before { content: "Paket"; }
        .notebook-table td:nth-child(7)::before { content: "Tagih"; }
        .notebook-table td:nth-child(8)::before { content: "Status"; }

        .notebook-table .customer-actions-col {
            position: static;
            min-width: 0;
            background: rgba(2, 6, 23, .42) !important;
            box-shadow: none;
            text-align: left;
            white-space: normal;
        }

        .notebook-table .customer-actions-col button,
        .notebook-table .customer-actions-col a,
        .notebook-table .customer-actions-col form {
            margin-top: .35rem;
        }

        .flex.flex-wrap.items-center.gap-2.self-start > a,
        .flex.flex-wrap.items-center.gap-2.self-start > button,
        .flex.flex-wrap.items-center.gap-2.self-start.sm\:self-auto > a,
        .flex.flex-wrap.items-center.gap-2.self-start.sm\:self-auto > button {
            flex: 1 1 calc(50% - .25rem);
            justify-content: center;
            min-height: 2.75rem;
            padding-left: .75rem;
            padding-right: .75rem;
            font-size: 12px;
        }
    }
</style>
<div class="customers-page space-y-5">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 md:gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white">Kelola Pelanggan</h1>
            <p class="text-sm md:text-base text-slate-400 mt-1">Daftar warga dan pelanggan aktif iuran bulanan di wilayah RT/RW Anda.</p>
        </div>
        <div class="page-actions flex flex-wrap items-center gap-2 self-start sm:self-auto">
            {{-- Export Button --}}
            <a href="{{ route('customers.export') }}"
               class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 hover:bg-emerald-500 hover:text-white transition-all duration-300 text-sm font-semibold"
               title="Export ke Excel (.xls)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export .xls
            </a>

            {{-- Import Button --}}
            <button
                onclick="document.getElementById('import-modal').classList.remove('hidden'); document.getElementById('import-modal').classList.add('flex')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/25 hover:bg-amber-500 hover:text-white transition-all duration-300 text-sm font-semibold"
                title="Import dari Excel (.xlsx/.xls) atau CSV">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import
            </button>

            {{-- Hapus Semua Pelanggan --}}
            <form action="{{ route('customers.destroy-all') }}" method="POST" class="inline-flex" onsubmit="return confirmDeleteAllCustomers(this)">
                @csrf
                @method('DELETE')
                <input type="hidden" name="confirmation">
                <button type="submit"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/25 hover:bg-rose-600 hover:text-white transition-all duration-300 text-sm font-semibold"
                    title="Hapus semua data pelanggan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16" />
                    </svg>
                    Hapus Semua
                </button>
            </form>

            {{-- Hapus Pelanggan Terpilih --}}
            <button type="button"
                onclick="submitSelectedCustomersDelete()"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-500/10 text-rose-300 border border-rose-500/25 hover:bg-rose-600 hover:text-white transition-all duration-300 text-sm font-semibold"
                title="Hapus pelanggan yang dicentang">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M5 7h9M5 12h4M5 17h7" />
                </svg>
                Hapus Pilihan
            </button>

            {{-- Broadcast WA Button --}}
            <button onclick="openBroadcastModal()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-green-500/10 text-green-400 border border-green-500/25 hover:bg-green-500 hover:text-white transition-all duration-300 text-sm font-semibold" title="Kirim Broadcast Pesan WA ke Pelanggan di Halaman Ini">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Kirim Broadcast
            </button>

            {{-- Tambah Pelanggan --}}
            <button onclick="document.getElementById('add-modal').classList.remove('hidden'); document.getElementById('add-modal').classList.add('flex')" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-650 hover:to-purple-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-300 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>Tambah Pelanggan</span>
            </button>
        </div>
    </div>

    @if (session('success') || session('error') || $errors->any())
        <div class="space-y-3">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-4 text-sm font-semibold text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-5 py-4 text-sm font-semibold text-rose-200">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 px-5 py-4 text-sm text-amber-100">
                    <p class="font-bold">Ada data yang belum sesuai:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <!-- Filter & Search Section -->
    <div class="space-y-3 bg-slate-900/20 border border-slate-800/60 p-3 rounded-2xl backdrop-blur-md">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <button type="button" id="customer-filter-toggle" class="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-500/25 bg-indigo-500/10 px-4 py-2 text-sm font-bold text-indigo-300 transition hover:bg-indigo-600 hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12M10 20h4" />
                </svg>
                Filter
                @if($activeCustomerFilters)
                    <span class="rounded-full bg-indigo-400 px-1.5 py-0.5 text-[10px] font-black text-slate-950">Aktif</span>
                @endif
            </button>
            <div class="grid w-full grid-cols-2 gap-2 text-sm sm:grid-cols-4 lg:w-auto">
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Data</p>
                <p class="text-lg font-extrabold text-white">{{ number_format($customers->total(), 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tampil</p>
                <p class="text-lg font-extrabold text-white">{{ number_format($customers->count(), 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Halaman</p>
                <p class="text-lg font-extrabold text-white">{{ $customers->currentPage() }}/{{ $customers->lastPage() }}</p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Rentang</p>
                <p class="text-sm font-bold text-white">{{ $customers->firstItem() ?? 0 }}-{{ $customers->lastItem() ?? 0 }}</p>
            </div>
        </div>
    </div>
        <form id="customer-filter-panel" action="{{ route('customers.index') }}" method="GET" class="{{ $activeCustomerFilters ? '' : 'hidden' }} rounded-xl border border-slate-800 bg-slate-950/45 p-2">
            <div class="flex items-end gap-2 overflow-x-auto pb-1">
                <div class="relative min-w-64">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Cari Semua</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, telp, atau alamat..." class="w-full bg-slate-950 border border-slate-800 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    <div class="absolute left-2.5 top-6 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>
                <div class="min-w-40">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Nama</label>
                    <input type="text" name="filter_name" value="{{ request('filter_name') }}" placeholder="Nama" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                </div>
                <div class="min-w-32">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Telepon</label>
                    <input type="text" name="filter_phone" value="{{ request('filter_phone') }}" placeholder="08..." class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                </div>
                <div class="min-w-44">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Alamat</label>
                    <input type="text" name="filter_address" value="{{ request('filter_address') }}" placeholder="Alamat" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                </div>
                <div class="min-w-40">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">ODP</label>
                    <select name="filter_odp" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                        <option value="">Semua</option>
                        @foreach($odps as $odp)
                            <option value="{{ $odp->id }}" {{ (string) request('filter_odp') === (string) $odp->id ? 'selected' : '' }}>{{ $odp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-40">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Paket</label>
                    <select name="filter_package" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                        <option value="">Semua</option>
                        @foreach($packages as $package)
                            <option value="{{ $package->id }}" {{ (string) request('filter_package') === (string) $package->id ? 'selected' : '' }}>{{ $package->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-32">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Status</label>
                    <select name="filter_status" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                        <option value="">Semua</option>
                        @foreach($customerStatusLabels as $value => $label)
                            <option value="{{ $value }}" {{ request('filter_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-36">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Tagihan</label>
                    <select name="filter_billing_type" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                        <option value="">Semua</option>
                        @foreach($billingTypeLabels as $value => $label)
                            <option value="{{ $value }}" {{ request('filter_billing_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-32">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Mode</label>
                    <select name="filter_billing_mode" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                        <option value="">Semua</option>
                        @foreach($billingModeLabels as $value => $label)
                            <option value="{{ $value }}" {{ request('filter_billing_mode') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-24">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Tgl Tagih</label>
                    <select name="filter_billing_day" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                        <option value="">Semua</option>
                        @for($day = 1; $day <= 28; $day++)
                            <option value="{{ $day }}" {{ (int) request('filter_billing_day') === $day ? 'selected' : '' }}>{{ $day }}</option>
                        @endfor
                    </select>
                </div>
                <div class="min-w-24">
                    <label class="mb-1 block text-[9px] font-black uppercase tracking-wide text-slate-500">Baris</label>
                    <select name="per_page" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                        @foreach([25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
                <div class="flex min-w-36 items-end gap-2">
                    <button type="submit" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-slate-700">Terapkan</button>
                @if ($activeCustomerFilters || request('per_page'))
                        <a href="{{ route('customers.index') }}" class="px-1 text-xs font-semibold text-indigo-400 hover:text-indigo-300">Reset</a>
                @endif
                </div>
            </div>
        </form>
    </div>

    <script>
        window.toggleCustomerFilter = function () {
            const panel = document.getElementById('customer-filter-panel');
            if (!panel) return;

            panel.classList.toggle('hidden');

            if (!panel.classList.contains('hidden')) {
                const input = panel.querySelector('input[name="search"]');
                if (input) {
                    setTimeout(() => input.focus(), 50);
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('customer-filter-toggle');
            if (button) {
                button.addEventListener('click', window.toggleCustomerFilter);
            }

            window.updateSelectedCustomersCount();
        });

        window.selectedCustomerCheckboxes = function () {
            return Array.from(document.querySelectorAll('.customer-select-checkbox:checked'));
        };

        window.updateSelectedCustomersCount = function () {
            const selectedCount = window.selectedCustomerCheckboxes().length;
            const countLabel = document.getElementById('selected-customers-count');
            const selectAll = document.getElementById('select-all-customers');
            const allCheckboxes = Array.from(document.querySelectorAll('.customer-select-checkbox'));

            if (countLabel) countLabel.textContent = selectedCount;
            if (selectAll) {
                selectAll.checked = allCheckboxes.length > 0 && selectedCount === allCheckboxes.length;
                selectAll.indeterminate = selectedCount > 0 && selectedCount < allCheckboxes.length;
            }
        };

        window.setAllCustomerSelection = function (checked) {
            document.querySelectorAll('.customer-select-checkbox').forEach(function (checkbox) {
                checkbox.checked = Boolean(checked);
            });
            window.updateSelectedCustomersCount();
        };

        window.toggleAllCustomerSelection = function (source) {
            window.setAllCustomerSelection(source.checked);
        };
    </script>


    <!-- Customers Table -->
    <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-2">
        <form id="bulk-delete-customers-form" action="{{ route('customers.bulk-destroy') }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
            <input type="hidden" name="confirmation">
            <div id="bulk-delete-customer-inputs"></div>
        </form>

        <div class="mb-3 flex flex-col gap-3 rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2 text-xs text-slate-300 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="font-bold text-white" id="selected-customers-count">0</span>
                <span>pelanggan dipilih di halaman ini.</span>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <button type="button" onclick="setAllCustomerSelection(true)" class="inline-flex h-8 items-center justify-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-3 text-xs font-bold text-slate-200 transition hover:border-indigo-500/50 hover:bg-indigo-500/15 hover:text-white">
                    Pilih Semua
                </button>
                <button type="button" onclick="setAllCustomerSelection(false)" class="inline-flex h-8 items-center justify-center gap-2 rounded-lg border border-slate-700 bg-slate-900 px-3 text-xs font-semibold text-slate-400 transition hover:bg-slate-800 hover:text-white">
                    Batal Semua
                </button>
                <form id="bulk-billing-mode-form" action="{{ route('customers.bulk-billing-mode') }}" method="POST" onsubmit="return submitBulkBillingMode(event)" class="flex flex-wrap items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <div id="bulk-billing-mode-inputs"></div>
                    <select id="bulk-billing-mode-select" name="billing_mode" class="h-8 rounded-lg border border-slate-700 bg-slate-900 px-2 text-xs font-semibold text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($billingModeLabels as $mode => $label)
                            <option value="{{ $mode }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex h-8 items-center justify-center gap-2 rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-3 text-xs font-bold text-indigo-200 transition hover:bg-indigo-600 hover:text-white">
                        Ubah Mode
                    </button>
                </form>
                <button type="button" onclick="submitSelectedCustomersDelete()" class="inline-flex h-8 items-center justify-center gap-2 rounded-lg border border-rose-500/25 bg-rose-500/10 px-3 text-xs font-bold text-rose-300 transition hover:bg-rose-600 hover:text-white">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7M4 7h16" />
                    </svg>
                    Hapus Pelanggan Terpilih
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="notebook-table w-full text-left">
                <thead>
                    <tr class="text-slate-400 text-xs uppercase font-bold">
                        <th class="cell-tight w-10">
                            <input type="checkbox" id="select-all-customers" aria-label="Pilih semua pelanggan di halaman ini" title="Pilih semua pelanggan di halaman ini" onchange="toggleAllCustomerSelection(this)" class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="cell-tight">Nama Pelanggan</th>
                        <th class="cell-tight">Kontak</th>
                        <th class="cell-tight">Alamat Rumah</th>
                        <th class="cell-tight">ODP Terhubung</th>
                        <th class="cell-tight">Paket Layanan</th>
                        <th class="cell-tight text-center">Tgl Tagih</th>
                        <th class="cell-tight text-center">Status</th>
                        <th class="cell-tight text-right customer-actions-col">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-200">
                    @forelse ($customers as $customer)
                        <tr class="transition-colors">
                            <td class="cell-tight">
                                <input type="checkbox" value="{{ $customer->id }}" onchange="updateSelectedCustomersCount()" class="customer-select-checkbox h-4 w-4 rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <td class="cell-tight font-semibold text-white">{{ $customer->name }}</td>
                            <td class="cell-tight space-y-0.5">
                                <div class="font-medium text-slate-300">{{ $customer->phone }}</div>
                                <div class="text-[11px] text-slate-400">{{ $customer->email ?? 'no email' }}</div>
                            </td>
                            <td class="cell-tight text-slate-300 max-w-xs truncate" title="{{ $customer->address }}">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate">{{ $customer->address }}</span>
                                    @if($customer->latitude && $customer->longitude)
                                        <a href="{{ route('network-map') }}?customer_id={{ $customer->id }}" class="text-indigo-400 hover:text-indigo-300 shrink-0 inline-flex items-center" title="Lihat di Peta Jaringan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-tight">
                                @if ($customer->odp)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 font-mono">
                                        {{ $customer->odp->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500 italic">Belum terhubung</span>
                                @endif
                            </td>
                            <td class="cell-tight">
                                @if ($customer->package)
                                    <div class="font-semibold text-white text-xs">{{ $customer->package->name }}</div>
                                    <div class="text-[10px] text-indigo-400 font-bold">Rp {{ number_format($customer->package->price, 0, ',', '.') }}</div>
                                @else
                                    <span class="text-rose-400 font-medium text-xs">Belum Pilih Paket</span>
                                @endif
                            </td>
                            <td class="cell-tight text-center font-bold text-slate-400">Tgl {{ $customer->billing_day }}</td>
                            <td class="cell-tight text-center">
                                @php
                                    $status = $customer->status ?? 'active';
                                    $latestStatusHistory = $customer->statusHistories->first();
                                @endphp
                                <div class="status-mini flex flex-col items-center">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold border {{ $customerStatusClasses[$status] ?? $customerStatusClasses['inactive'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $customerStatusDots[$status] ?? $customerStatusDots['inactive'] }}"></span>
                                        <span>{{ $customerStatusLabels[$status] ?? ucfirst($status) }}</span>
                                    </span>
                                    <span class="text-[10px] text-slate-500">
                                        {{ $latestStatusHistory ? $latestStatusHistory->changed_at->translatedFormat('d M Y H:i') : 'Belum ada riwayat' }}
                                    </span>
                                </div>
                            </td>
                            <td class="cell-tight text-right space-x-1.5 whitespace-nowrap customer-actions-col">
                            <!-- WA Button — data-* avoids quote conflict in onclick HTML attribute -->
                                <button
                                    type="button"
                                    data-wa-customer="{{ json_encode([
                                        'id'                     => $customer->id,
                                        'name'                   => $customer->name,
                                        'phone'                  => $customer->phone,
                                        'odp_name'               => $customer->odp ? $customer->odp->name : '',
                                        'package_price'          => $customer->package ? number_format($customer->package->price, 0, ',', '.') : '0',
                                        'latest_invoice_number'  => $customer->latestInvoice ? $customer->latestInvoice->invoice_number : '',
                                        'latest_invoice_amount'  => $customer->latestInvoice ? number_format($customer->latestInvoice->amount, 0, ',', '.') : '',
                                        'latest_invoice_month'   => $customer->latestInvoice ? $monthNames[$customer->latestInvoice->month] : '',
                                        'latest_invoice_year'    => $customer->latestInvoice ? $customer->latestInvoice->year : '',
                                    ]) }}"
                                    onclick="openWaModalFromBtn(this)"
                                    class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-600 hover:text-white transition-all duration-300 inline-flex items-center justify-center"
                                    title="Kirim WhatsApp">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.416 1.46 5.867 0 10.642-4.77 10.646-10.643.002-2.846-1.104-5.523-3.11-7.533C17.593 4.432 14.92 3.328 12.073 3.328 6.205 3.328 1.43 8.098 1.426 13.97c-.001 1.936.505 3.826 1.466 5.425l-.962 3.516 3.6-.945zm11.367-7.46c-.327-.164-1.938-.956-2.238-1.066-.3-.11-.519-.164-.738.164-.219.328-.847 1.066-1.039 1.284-.192.219-.383.246-.71.082-.327-.164-1.383-.51-2.634-1.627-.973-.867-1.63-1.939-1.821-2.266-.192-.328-.02-.505.143-.668.147-.146.327-.383.49-.574.164-.191.219-.328.328-.546.11-.219.055-.41-.027-.574-.082-.164-.738-1.777-1.011-2.434-.266-.64-.539-.554-.738-.564-.19-.01-.41-.01-.628-.01-.219 0-.574.082-.875.41-.3.328-1.148 1.12-1.148 2.73 0 1.61 1.175 3.167 1.339 3.385.164.218 2.31 3.528 5.597 4.949.781.338 1.39.54 1.866.69.784.249 1.498.214 2.062.129.629-.094 1.938-.792 2.211-1.558.273-.766.273-1.422.191-1.558-.082-.137-.3-.218-.627-.382z"/>
                                    </svg>
                                </button>

                                <!-- Print Button -->
                                <a href="{{ route('customers.print', $customer->id) }}" target="_blank" class="p-2 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Cetak Data Pelanggan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2zm5-17V3a1 1 0 011-1h2a1 1 0 011 1v3M5 12h.01M19 12h.01" />
                                    </svg>
                                </a>

                                <!-- Detail Button -->
                                <button onclick="document.getElementById('detail-modal-{{ $customer->id }}').classList.remove('hidden'); document.getElementById('detail-modal-{{ $customer->id }}').classList.add('flex')" class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-600 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Lihat Detail Lengkap">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>

                                @if($customer->status === 'active')
                                    <form action="{{ route('customers.isolate', $customer->id) }}" method="POST" class="inline-flex" onsubmit="return confirmCustomerStatus(this, 'Isolir pelanggan ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="reason">
                                        <button type="submit" class="p-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Isolir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('customers.stop-service', $customer->id) }}" method="POST" class="inline-flex" onsubmit="return confirmCustomerStatus(this, 'Berhentikan layanan pelanggan ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="reason">
                                        <button type="submit" class="p-2 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Berhenti">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </form>
                                @elseif(in_array($customer->status, ['isolated', 'inactive']))
                                    <form action="{{ route('customers.reactivate', $customer->id) }}" method="POST" class="inline-flex" onsubmit="return confirmCustomerStatus(this, 'Aktifkan kembali pelanggan ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="reason">
                                        <button type="submit" class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Aktif Kembali">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('customers.stop-service', $customer->id) }}" method="POST" class="inline-flex" onsubmit="return confirmCustomerStatus(this, 'Berhentikan layanan pelanggan ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="reason">
                                        <button type="submit" class="p-2 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Berhenti">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </form>
                                @elseif($customer->status === 'stopped')
                                    <form action="{{ route('customers.reactivate', $customer->id) }}" method="POST" class="inline-flex" onsubmit="return confirmCustomerStatus(this, 'Aktifkan kembali pelanggan ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="reason">
                                        <button type="submit" class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Aktif Kembali">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                    </form>
                                @endif

                                <button onclick="document.getElementById('status-history-modal-{{ $customer->id }}').classList.remove('hidden'); document.getElementById('status-history-modal-{{ $customer->id }}').classList.add('flex')" class="p-2 rounded-lg bg-slate-500/10 text-slate-300 border border-slate-600/30 hover:bg-slate-700 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Riwayat Status">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>

                                <!-- Edit Button -->
                                <button onclick="document.getElementById('edit-modal-{{ $customer->id }}').classList.remove('hidden'); document.getElementById('edit-modal-{{ $customer->id }}').classList.add('flex')" class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Edit Data">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                
                                <!-- Delete Button -->
                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="inline-flex" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini? Semua tagihan terkait juga akan terhapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all duration-300 inline-flex items-center justify-center" title="Hapus Pelanggan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>

@empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-500 font-medium">Belum ada pelanggan ditemukan. Silakan tambah pelanggan baru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($customers->hasPages())
            <div class="mt-8 border-t border-slate-800/60 pt-6 flex items-center justify-between">
                <div class="text-xs text-slate-400">
                    Menampilkan {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} dari {{ $customers->total() }} pelanggan
                </div>
                <div class="flex items-center gap-1.5">
                    @if ($customers->onFirstPage())
                        <span class="px-3.5 py-2 rounded-xl bg-slate-850/40 border border-slate-800 text-slate-500 text-xs font-semibold select-none cursor-not-allowed">Sebelumnya</span>
                    @else
                        <a href="{{ $customers->previousPageUrl() }}" class="px-3.5 py-2 rounded-xl bg-slate-800/60 border border-slate-700 text-slate-200 hover:bg-slate-700 text-xs font-semibold transition-colors">Sebelumnya</a>
                    @endif

                    @if ($customers->hasMorePages())
                        <a href="{{ $customers->nextPageUrl() }}" class="px-3.5 py-2 rounded-xl bg-slate-800/60 border border-slate-700 text-slate-200 hover:bg-slate-700 text-xs font-semibold transition-colors">Berikutnya</a>
                    @else
                        <span class="px-3.5 py-2 rounded-xl bg-slate-850/40 border border-slate-800 text-slate-500 text-xs font-semibold select-none cursor-not-allowed">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>

<!-- ADD CUSTOMER MODAL -->
<div id="add-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full relative shadow-2xl shadow-slate-950 my-8 overflow-hidden flex flex-col max-h-[90vh] animate-fade-in">
        <form action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800/80 shrink-0">
                <h3 class="text-xl font-bold text-white">Tambah Pelanggan Baru</h3>
                <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden'); document.getElementById('add-modal').classList.remove('flex')" class="text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 sm:p-8 space-y-6 text-left">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Bagian 1: Data Diri Pelanggan -->
                <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-800">
                    <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-855 pb-2">1. Data Diri Pelanggan</h4>
                    <div>
                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Nama Lengkap</label>
                        <input type="text" name="name" placeholder="Contoh: Budi Santoso" required class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">No. Telepon</label>
                        <input type="text" name="phone" placeholder="Contoh: 08123456789" required class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Email (Opsional)</label>
                        <input type="email" name="email" placeholder="budi@example.com (opsional)" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    </div>
                </div>

                <!-- Bagian 2: Layanan & PPPoE -->
                <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-800">
                    <h4 class="text-xs font-bold text-purple-400 uppercase tracking-wider border-b border-slate-855 pb-2">2. Informasi Layanan & PPPoE</h4>
                    <div>
                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Pilih Paket Layanan</label>
                        <select name="package_id" required class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                            <option value="" disabled selected>-- Pilih Paket --</option>
                            @foreach ($packages as $pkg)
                                <option value="{{ $pkg->id }}">
                                    {{ $pkg->name }} (Rp {{ number_format($pkg->price, 0, ',', '.') }}/bln)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Tanggal Tagih</label>
                            <input type="number" name="billing_day" placeholder="5" value="5" required min="1" max="28" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Tanggal Pasang</label>
                            <input type="date" name="tanggal_pasang" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Status Tagihan</label>
                            <select name="billing_type" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                @foreach ($billingTypeLabels as $value => $label)
                                    <option value="{{ $value }}" {{ $value === 'paid' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Mode Pembayaran</label>
                            <select name="billing_mode" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                @foreach ($billingModeLabels as $value => $label)
                                    <option value="{{ $value }}" {{ $value === 'postpaid' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[10px] text-slate-500">Pascabayar ikut generate invoice. Prabayar dibayar melalui Bayar di Muka.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Catatan Gratis</label>
                            <input type="text" name="billing_note" placeholder="Contoh: jatah RT / keluarga" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">User PPPoE</label>
                            <input type="text" name="pppoe_username" placeholder="pppoe-budi" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Password PPPoE</label>
                            <input type="text" name="pppoe_password" placeholder="pass123" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Jalur Koneksi</label>
                            <select name="jalur" id="add-jalur" onchange="updateDeviceOptions(this, 'add-perangkat')" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                <option value="">-- Pilih Jalur --</option>
                                <option value="FO">FO (Fiber Optic)</option>
                                <option value="LAN">LAN</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Model Perangkat</label>
                            <select name="perangkat" id="add-perangkat" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                <option value="">-- Pilih Perangkat --</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Bagian 3: Lokasi & Rumah -->
                <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-800">
                    <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider border-b border-slate-855 pb-2">3. Lokasi & Status Rumah</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Status Rumah</label>
                            <select name="status_rumah" onchange="togglePemilikRumah(this, 'add-pemilik-container')" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                <option value="milik_sendiri" selected>Milik Sendiri</option>
                                <option value="ngontrak">Ngontrak / Sewa</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase font-bold text-indigo-400">Hubungkan Ke ODP</label>
                            <select name="odp_id" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                <option value="" selected>-- Pilih ODP (Opsional) --</option>
                                @foreach ($odps as $odp)
                                    <option value="{{ $odp->id }}">
                                        {{ $odp->name }} (Terpakai: {{ $odp->customers_count }} / {{ $odp->fo_ports + $odp->lan_ports }} Port)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="add-pemilik-container" class="hidden">
                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Nama Pemilik Rumah</label>
                        <input type="text" name="pemilik_rumah" placeholder="Nama pemilik" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase font-bold text-purple-400">Link Google Maps</label>
                        <div class="flex gap-2 mb-2">
                            <input type="text" id="add_maps_link" name="maps_link" placeholder="Contoh: https://maps.google.com/?q=-6.12,106.12" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                            <button type="button" id="add_btn_extract" data-coordinate-extract data-map-input="add_maps_link" data-lat-input="add_latitude" data-lng-input="add_longitude" onclick="extractCoordinatesBtn('add_maps_link', 'add_latitude', 'add_longitude', 'add_btn_extract')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-2.5 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1 whitespace-nowrap shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Ekstrak</span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="add_latitude" name="latitude" placeholder="Latitude (-6.xxx)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                            <input type="text" id="add_longitude" name="longitude" placeholder="Longitude (106.xxx)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase font-bold text-slate-400">Alamat Rumah Lengkap</label>
                        <textarea name="address" rows="2" placeholder="Contoh: RT 03 / RW 04, Blok A No. 12" required class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors"></textarea>
                    </div>
                </div>

                <!-- Bagian 4: Dokumentasi Foto -->
                <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-800">
                    <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider border-b border-slate-855 pb-2">4. Upload Foto Dokumentasi</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Foto 1: KTP -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-semibold text-slate-400 uppercase">Foto KTP / Identitas</label>
                            <div class="relative border border-dashed border-slate-800 hover:border-indigo-500 rounded-xl p-3 flex flex-col items-center justify-center bg-slate-950/40 aspect-video transition-colors group overflow-hidden">
                                <input type="file" name="foto_ktp" accept="image/*" onchange="previewImage(this, 'add-preview-ktp', 'add-placeholder-ktp')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <img id="add-preview-ktp" src="" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl z-20">
                                <div class="text-center group-hover:text-indigo-400 transition-colors z-0" id="add-placeholder-ktp">
                                    <svg class="w-6 h-6 text-slate-500 group-hover:text-indigo-400 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-[10px] text-slate-450 font-semibold">Pilih Foto</span>
                                </div>
                            </div>
                        </div>

                        <!-- Foto 2: Rumah -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-semibold text-slate-400 uppercase">Foto Depan Rumah</label>
                            <div class="relative border border-dashed border-slate-800 hover:border-indigo-500 rounded-xl p-3 flex flex-col items-center justify-center bg-slate-950/40 aspect-video transition-colors group overflow-hidden">
                                <input type="file" name="foto_rumah" accept="image/*" onchange="previewImage(this, 'add-preview-rumah', 'add-placeholder-rumah')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <img id="add-preview-rumah" src="" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl z-20">
                                <div class="text-center group-hover:text-indigo-400 transition-colors z-0" id="add-placeholder-rumah">
                                    <svg class="w-6 h-6 text-slate-500 group-hover:text-indigo-400 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-[10px] text-slate-450 font-semibold">Pilih Foto</span>
                                </div>
                            </div>
                        </div>

                        <!-- Foto 3: ONT -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-semibold text-slate-400 uppercase">Foto ONT / Modem</label>
                            <div class="relative border border-dashed border-slate-800 hover:border-indigo-500 rounded-xl p-3 flex flex-col items-center justify-center bg-slate-950/40 aspect-video transition-colors group overflow-hidden">
                                <input type="file" name="foto_perangkat" accept="image/*" onchange="previewImage(this, 'add-preview-perangkat', 'add-placeholder-perangkat')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <img id="add-preview-perangkat" src="" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl z-20">
                                <div class="text-center group-hover:text-indigo-400 transition-colors z-0" id="add-placeholder-perangkat">
                                    <svg class="w-6 h-6 text-slate-500 group-hover:text-indigo-400 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-[10px] text-slate-450 font-semibold">Pilih Foto</span>
                                </div>
                            </div>
                        </div>

                        <!-- Foto 4: ODP -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-semibold text-slate-400 uppercase">Foto Port ODP / Dropcore</label>
                            <div class="relative border border-dashed border-slate-800 hover:border-indigo-500 rounded-xl p-3 flex flex-col items-center justify-center bg-slate-950/40 aspect-video transition-colors group overflow-hidden">
                                <input type="file" name="foto_odp" accept="image/*" onchange="previewImage(this, 'add-preview-odp', 'add-placeholder-odp')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <img id="add-preview-odp" src="" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl z-20">
                                <div class="text-center group-hover:text-indigo-400 transition-colors z-0" id="add-placeholder-odp">
                                    <svg class="w-6 h-6 text-slate-500 group-hover:text-indigo-400 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-[10px] text-slate-450 font-semibold">Pilih Foto</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            </div>

            <div class="px-6 py-5 border-t border-slate-800/80 bg-slate-900/60 flex items-center justify-end gap-3 shrink-0">
                <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden'); document.getElementById('add-modal').classList.remove('flex')" class="bg-slate-800 hover:bg-slate-750 border border-slate-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-all duration-300 text-sm">Batal</button>
                <button type="submit" class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold px-5 py-2.5 rounded-xl hover:from-indigo-650 hover:to-purple-700 shadow-lg shadow-indigo-600/10 transition-all duration-300 text-sm">Simpan Pelanggan</button>
            </div>
        </form>
    </div>
</div>


<!-- DETAIL & EDIT MODALS FOR EACH CUSTOMER (RENDERED OUTSIDE TABLE FOR VALID HTML AND LAYOUT ROBUSTNESS) -->
@foreach ($customers as $customer)


                        <!-- DETAIL MODAL FOR THIS CUSTOMER -->
                        <div id="detail-modal-{{ $customer->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 overflow-y-auto">
                            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full relative shadow-2xl shadow-slate-950 my-8 overflow-hidden flex flex-col max-h-[90vh]">
                                <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800/80 shrink-0">
                                    <h3 class="text-xl font-bold text-white">Detail Pelanggan: <span class="text-indigo-400">{{ $customer->name }}</span></h3>
                                    <button onclick="document.getElementById('detail-modal-{{ $customer->id }}').classList.add('hidden'); document.getElementById('detail-modal-{{ $customer->id }}').classList.remove('flex')" class="text-slate-400 hover:text-white">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <div class="p-6 sm:p-8 overflow-y-auto space-y-6 text-left">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Kelompok 1: Data Diri & Layanan -->
                                        <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-850">
                                            <h4 class="text-sm font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">Data Diri & Layanan</h4>
                                            <div class="grid grid-cols-3 gap-2 text-xs">
                                                <div class="text-slate-400 font-medium">Nama</div>
                                                <div class="col-span-2 text-white font-semibold">{{ $customer->name }}</div>
                                                
                                                <div class="text-slate-400 font-medium">No. Telepon</div>
                                                <div class="col-span-2 text-white font-semibold">{{ $customer->phone }}</div>
                                                
                                                <div class="text-slate-400 font-medium">Email</div>
                                                <div class="col-span-2 text-white">{{ $customer->email ?? '-' }}</div>
                                                
                                                <div class="text-slate-400 font-medium">Paket Layanan</div>
                                                <div class="col-span-2 text-emerald-400 font-bold">
                                                    @if($customer->package)
                                                        {{ $customer->package->name }} (Rp {{ number_format($customer->package->price, 0, ',', '.') }}/bln)
                                                    @else
                                                        -
                                                    @endif
                                                </div>

                                                <div class="text-slate-400 font-medium">Tanggal Pasang</div>
                                                <div class="col-span-2 text-white">
                                                    {{ $customer->tanggal_pasang ? \Carbon\Carbon::parse($customer->tanggal_pasang)->translatedFormat('d F Y') : '-' }}
                                                </div>
                                                
                                                <div class="text-slate-400 font-medium">Siklus Tagihan</div>
                                                <div class="col-span-2 text-white font-semibold">Tiap tanggal {{ $customer->billing_day }}</div>

                                                <div class="text-slate-400 font-medium">Status Akun</div>
                                                <div class="col-span-2">
                                                    @php
                                                        $detailStatus = $customer->status ?? 'active';
                                                        $detailLatestStatusHistory = $customer->statusHistories->first();
                                                    @endphp
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $customerStatusClasses[$detailStatus] ?? $customerStatusClasses['inactive'] }}">
                                                        <span class="w-1.5 h-1.5 rounded-full {{ $customerStatusDots[$detailStatus] ?? $customerStatusDots['inactive'] }}"></span>
                                                        {{ $customerStatusLabels[$detailStatus] ?? ucfirst($detailStatus) }}
                                                    </span>
                                                    <div class="text-[10px] text-slate-500 mt-1">
                                                        {{ $detailLatestStatusHistory ? 'Update: ' . $detailLatestStatusHistory->changed_at->translatedFormat('d F Y H:i') : 'Belum ada riwayat status' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Kelompok 2: Akun PPPoE & Lokasi -->
                                        <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-850">
                                            <h4 class="text-sm font-bold text-purple-400 uppercase tracking-wider border-b border-slate-800 pb-2">Detail PPPoE & Rumah</h4>
                                            <div class="grid grid-cols-3 gap-2 text-xs">
                                                <div class="text-slate-400 font-medium">User PPPoE</div>
                                                <div class="col-span-2 text-purple-300 font-mono font-bold">{{ $customer->pppoe_username ?? '-' }}</div>
                                                
                                                <div class="text-slate-400 font-medium">Password PPPoE</div>
                                                <div class="col-span-2 text-purple-300 font-mono">{{ $customer->pppoe_password ?? '-' }}</div>

                                                <div class="text-slate-400 font-medium">Jalur Koneksi</div>
                                                <div class="col-span-2 text-white font-semibold">{{ $customer->jalur ?? '-' }}</div>

                                                <div class="text-slate-400 font-medium">Model Perangkat</div>
                                                <div class="col-span-2 text-white font-semibold">{{ $customer->perangkat ?? '-' }}</div>
                                                
                                                <div class="text-slate-400 font-medium">Status Rumah</div>
                                                <div class="col-span-2 text-white capitalize">
                                                    {{ $customer->status_rumah === 'ngontrak' ? 'Ngontrak / Sewa' : 'Milik Sendiri' }}
                                                </div>
                                                
                                                @if($customer->status_rumah === 'ngontrak')
                                                    <div class="text-slate-400 font-medium">Pemilik Rumah</div>
                                                    <div class="col-span-2 text-white font-semibold">{{ $customer->pemilik_rumah ?? '-' }}</div>
                                                @endif

                                                <div class="text-slate-400 font-medium">ODP Terhubung</div>
                                                <div class="col-span-2 text-indigo-300 font-semibold">{{ $customer->odp ? $customer->odp->name : 'Tidak Terhubung' }}</div>
                                                
                                                <div class="text-slate-400 font-medium">Koordinat</div>
                                                <div class="col-span-2 text-slate-300 font-mono text-[11px]">
                                                    @if($customer->latitude)
                                                        {{ $customer->latitude }}, {{ $customer->longitude }}
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                                
                                                <div class="text-slate-400 font-medium">Alamat</div>
                                                <div class="col-span-2 text-white break-words">{{ $customer->address }}</div>
                                            </div>
                                            @if($customer->latitude)
                                                <div class="pt-2">
                                                    <a href="{{ route('network-map') }}?customer_id={{ $customer->id }}" class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 font-bold transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                                        </svg>
                                                        <span>Lacak Titik & Jalur Kabel di Peta</span>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Dokumentasi Foto -->
                                    <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-850">
                                        <h4 class="text-sm font-bold text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2">Dokumentasi Foto</h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                            @php
                                                $photos = [
                                                    ['label' => 'Foto KTP / Identitas', 'field' => 'foto_ktp'],
                                                    ['label' => 'Foto Depan Rumah', 'field' => 'foto_rumah'],
                                                    ['label' => 'Foto Alat ONT / Modem', 'field' => 'foto_perangkat'],
                                                    ['label' => 'Foto Port ODP / Dropcore', 'field' => 'foto_odp']
                                                ];
                                            @endphp

                                            @foreach($photos as $photo)
                                                <div class="bg-slate-950/40 p-3 rounded-xl border border-slate-800/80 text-center space-y-2">
                                                    <span class="block text-[10px] font-bold text-slate-400 uppercase truncate" title="{{ $photo['label'] }}">{{ $photo['label'] }}</span>
                                                    @if($customer->{$photo['field']})
                                                        <a href="{{ asset('storage/' . $customer->{$photo['field']}) }}" target="_blank" class="block relative overflow-hidden rounded-lg group aspect-video bg-slate-900 border border-slate-800">
                                                            <img src="{{ asset('storage/' . $customer->{$photo['field']}) }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                                                            <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                                </svg>
                                                            </div>
                                                        </a>
                                                    @else
                                                        <div class="aspect-video bg-slate-900 border border-slate-800 rounded-lg flex flex-col items-center justify-center text-slate-600">
                                                            <svg class="w-6 h-6 mb-1 text-slate-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.008-.008a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                                            </svg>
                                                            <span class="text-[9px] font-semibold text-slate-500 italic">Belum diunggah</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-900/60 flex justify-end shrink-0">
                                    <button onclick="document.getElementById('detail-modal-{{ $customer->id }}').classList.add('hidden'); document.getElementById('detail-modal-{{ $customer->id }}').classList.remove('flex')" class="bg-slate-800 hover:bg-slate-750 text-white font-semibold px-5 py-2.5 rounded-xl transition-all duration-300 text-sm">Tutup</button>
                                </div>
                            </div>
                        </div>

                        <!-- STATUS HISTORY MODAL FOR THIS CUSTOMER -->
                        <div id="status-history-modal-{{ $customer->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 overflow-y-auto">
                            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full relative shadow-2xl shadow-slate-950 my-8 overflow-hidden flex flex-col max-h-[90vh]">
                                <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800/80 shrink-0">
                                    <div>
                                        <h3 class="text-xl font-bold text-white">Riwayat Status</h3>
                                        <p class="text-xs text-slate-400 mt-1">{{ $customer->name }}</p>
                                    </div>
                                    <button onclick="document.getElementById('status-history-modal-{{ $customer->id }}').classList.add('hidden'); document.getElementById('status-history-modal-{{ $customer->id }}').classList.remove('flex')" class="text-slate-400 hover:text-white">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <div class="p-6 overflow-y-auto space-y-3 text-left">
                                    @forelse($customer->statusHistories as $history)
                                        <div class="rounded-2xl border border-slate-800 bg-slate-950/30 p-4">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                                        {{ $customerStatusLabels[$history->old_status] ?? ($history->old_status ? ucfirst($history->old_status) : '-') }}
                                                    </span>
                                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-bold border {{ $customerStatusClasses[$history->new_status] ?? $customerStatusClasses['inactive'] }}">
                                                        {{ $customerStatusLabels[$history->new_status] ?? ucfirst($history->new_status) }}
                                                    </span>
                                                </div>
                                                <span class="text-xs text-slate-400 font-semibold">
                                                    {{ $history->changed_at->translatedFormat('d F Y H:i') }}
                                                </span>
                                            </div>
                                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                                <div>
                                                    <span class="text-slate-500">Aksi:</span>
                                                    <span class="text-white font-semibold">{{ $customerActionLabels[$history->action] ?? ucfirst(str_replace('_', ' ', $history->action)) }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-500">Oleh:</span>
                                                    <span class="text-white font-semibold">{{ $history->user->name ?? 'Sistem' }}</span>
                                                </div>
                                            </div>
                                            @if($history->reason)
                                                <div class="mt-3 text-xs text-slate-300 bg-slate-900/70 border border-slate-800 rounded-xl p-3">
                                                    {{ $history->reason }}
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-950/20 p-8 text-center">
                                            <p class="text-sm font-semibold text-slate-300">Belum ada riwayat perubahan status.</p>
                                            <p class="text-xs text-slate-500 mt-1">Riwayat akan muncul setelah tombol Isolir, Berhenti, atau Aktif Kembali digunakan.</p>
                                        </div>
                                    @endforelse
                                </div>
                                <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-900/60 flex justify-end shrink-0">
                                    <button onclick="document.getElementById('status-history-modal-{{ $customer->id }}').classList.add('hidden'); document.getElementById('status-history-modal-{{ $customer->id }}').classList.remove('flex')" class="bg-slate-800 hover:bg-slate-750 text-white font-semibold px-5 py-2.5 rounded-xl transition-all duration-300 text-sm">Tutup</button>
                                </div>
                            </div>
                        </div>

                        <!-- EDIT MODAL FOR THIS CUSTOMER -->
                        <div id="edit-modal-{{ $customer->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 overflow-y-auto">
                            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full relative shadow-2xl shadow-slate-950 my-8 overflow-hidden flex flex-col max-h-[90vh]">
                                <form action="{{ route('customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800/80 shrink-0">
                                        <h3 class="text-xl font-bold text-white">Edit Pelanggan</h3>
                                        <button type="button" onclick="document.getElementById('edit-modal-{{ $customer->id }}').classList.add('hidden'); document.getElementById('edit-modal-{{ $customer->id }}').classList.remove('flex')" class="text-slate-400 hover:text-white">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <div class="flex-1 overflow-y-auto p-6 sm:p-8 space-y-6 text-left">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Bagian 1: Data Diri Pelanggan -->
                                        <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-800">
                                            <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-850 pb-2">1. Data Diri Pelanggan</h4>
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-350 mb-1.5 uppercase">Nama Lengkap</label>
                                                <input type="text" name="name" value="{{ $customer->name }}" required class="w-full bg-slate-950 border border-slate-850 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">No. Telepon</label>
                                                <input type="text" name="phone" value="{{ $customer->phone }}" required class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Email (Opsional)</label>
                                                <input type="email" name="email" value="{{ $customer->email }}" placeholder="budi@example.com" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                            </div>
                                        </div>

                                        <!-- Bagian 2: Layanan & PPPoE -->
                                        <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-800">
                                            <h4 class="text-xs font-bold text-purple-400 uppercase tracking-wider border-b border-slate-855 pb-2">2. Informasi Layanan & PPPoE</h4>
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Paket Layanan</label>
                                                <select name="package_id" required class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                    @foreach ($packages as $pkg)
                                                        <option value="{{ $pkg->id }}" {{ $customer->package_id == $pkg->id ? 'selected' : '' }}>
                                                            {{ $pkg->name }} (Rp {{ number_format($pkg->price, 0, ',', '.') }}/bln)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Tanggal Tagih</label>
                                                    <input type="number" name="billing_day" value="{{ $customer->billing_day }}" required min="1" max="28" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Tanggal Pasang</label>
                                                    <input type="date" name="tanggal_pasang" value="{{ $customer->tanggal_pasang }}" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Status Tagihan</label>
                                                    <select name="billing_type" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                        @foreach ($billingTypeLabels as $value => $label)
                                                            <option value="{{ $value }}" {{ ($customer->billing_type ?? 'paid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Mode Pembayaran</label>
                                                    <select name="billing_mode" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                        @foreach ($billingModeLabels as $value => $label)
                                                            <option value="{{ $value }}" {{ ($customer->billing_mode ?? 'postpaid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <p class="mt-1 text-[10px] text-slate-500">Prabayar dibayar melalui Bayar di Muka.</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Catatan Gratis</label>
                                                    <input type="text" name="billing_note" value="{{ $customer->billing_note }}" placeholder="Contoh: jatah RT / keluarga" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">User PPPoE</label>
                                                    <input type="text" name="pppoe_username" value="{{ $customer->pppoe_username }}" placeholder="pppoe-budi" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Password PPPoE</label>
                                                    <input type="text" name="pppoe_password" value="{{ $customer->pppoe_password }}" placeholder="pass123" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono">
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Jalur Koneksi</label>
                                                    <select name="jalur" id="edit-jalur-{{ $customer->id }}" onchange="updateDeviceOptions(this, 'edit-perangkat-{{ $customer->id }}')" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                        <option value="">-- Pilih Jalur --</option>
                                                        <option value="FO" {{ $customer->jalur === 'FO' ? 'selected' : '' }}>FO (Fiber Optic)</option>
                                                        <option value="LAN" {{ $customer->jalur === 'LAN' ? 'selected' : '' }}>LAN</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Model Perangkat</label>
                                                    <select name="perangkat" id="edit-perangkat-{{ $customer->id }}" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                        <option value="">-- Pilih Perangkat --</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Bagian 3: Lokasi & Rumah -->
                                        <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-800">
                                            <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider border-b border-slate-855 pb-2">3. Lokasi & Status Rumah</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Status Rumah</label>
                                                    <select name="status_rumah" onchange="togglePemilikRumah(this, 'edit-pemilik-container-{{ $customer->id }}')" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                        <option value="milik_sendiri" {{ $customer->status_rumah === 'milik_sendiri' ? 'selected' : '' }}>Milik Sendiri</option>
                                                        <option value="ngontrak" {{ $customer->status_rumah === 'ngontrak' ? 'selected' : '' }}>Ngontrak / Sewa</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase font-bold text-indigo-400">Hubungkan Ke ODP</label>
                                                    <select name="odp_id" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                        <option value="">-- Pilih ODP (Opsional) --</option>
                                                        @foreach ($odps as $odp)
                                                            <option value="{{ $odp->id }}" {{ $customer->odp_id == $odp->id ? 'selected' : '' }}>
                                                                {{ $odp->name }} (Terpakai: {{ $odp->customers_count }} / {{ $odp->fo_ports + $odp->lan_ports }} Port)
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div id="edit-pemilik-container-{{ $customer->id }}" class="{{ $customer->status_rumah === 'ngontrak' ? '' : 'hidden' }}">
                                                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Nama Pemilik Rumah</label>
                                                <input type="text" name="pemilik_rumah" value="{{ $customer->pemilik_rumah }}" placeholder="Nama pemilik" class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                            </div>
                                            <div class="rounded-2xl border border-purple-500/15 bg-purple-500/5 p-4 space-y-3">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <label class="block text-xs font-bold text-purple-300 uppercase">Link Google Maps</label>
                                                        <p class="text-[11px] text-slate-500 mt-0.5">Tempel link Google Maps, lalu klik ekstrak untuk mengisi koordinat.</p>
                                                    </div>
                                                    @if($customer->latitude && $customer->longitude)
                                                        <a href="https://www.google.com/maps?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-3 py-2 text-[11px] font-bold text-emerald-300 hover:bg-emerald-600 hover:text-white transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                                                            Buka Maps
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="flex flex-col sm:flex-row gap-2">
                                                    <input type="text" id="edit_maps_link_{{ $customer->id }}" name="maps_link" value="{{ $customer->maps_link }}" placeholder="Contoh: https://maps.google.com/?q=-6.12,106.12" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-purple-500 transition-colors">
                                                    <button type="button" id="edit_btn_extract_{{ $customer->id }}" data-coordinate-extract data-map-input="edit_maps_link_{{ $customer->id }}" data-lat-input="edit_latitude_{{ $customer->id }}" data-lng-input="edit_longitude_{{ $customer->id }}" onclick="extractCoordinatesBtn('edit_maps_link_{{ $customer->id }}', 'edit_latitude_{{ $customer->id }}', 'edit_longitude_{{ $customer->id }}', 'edit_btn_extract_{{ $customer->id }}')" class="bg-purple-600 hover:bg-purple-500 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1.5 whitespace-nowrap shrink-0">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                        <span>Ekstrak Koordinat</span>
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <label class="space-y-1">
                                                        <span class="text-[10px] font-bold uppercase text-slate-500">Latitude</span>
                                                        <input type="text" id="edit_latitude_{{ $customer->id }}" name="latitude" value="{{ $customer->latitude }}" placeholder="-6.xxxxxxx" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 font-mono focus:outline-none focus:border-purple-500 transition-colors">
                                                    </label>
                                                    <label class="space-y-1">
                                                        <span class="text-[10px] font-bold uppercase text-slate-500">Longitude</span>
                                                        <input type="text" id="edit_longitude_{{ $customer->id }}" name="longitude" value="{{ $customer->longitude }}" placeholder="106.xxxxxxx" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 font-mono focus:outline-none focus:border-purple-500 transition-colors">
                                                    </label>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Alamat Rumah Lengkap</label>
                                                <textarea name="address" rows="2" required class="w-full bg-slate-950 border border-slate-855 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">{{ $customer->address }}</textarea>
                                            </div>
                                        </div>

                                        <!-- Bagian 4: Dokumentasi Foto -->
                                        <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-800">
                                            <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider border-b border-slate-855 pb-2">4. Unggah Foto Dokumentasi</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <!-- Foto 1: KTP -->
                                                <div class="space-y-1">
                                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase">Foto KTP / Identitas</label>
                                                    <div class="relative border border-dashed border-slate-800 hover:border-indigo-500 rounded-xl p-3 flex flex-col items-center justify-center bg-slate-950/40 aspect-video transition-colors group overflow-hidden">
                                                        <input type="file" name="foto_ktp" accept="image/*" onchange="previewImage(this, 'edit-preview-ktp-{{ $customer->id }}', 'edit-placeholder-ktp-{{ $customer->id }}')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                        <img id="edit-preview-ktp-{{ $customer->id }}" src="{{ $customer->foto_ktp ? asset('storage/' . $customer->foto_ktp) : '' }}" class="{{ $customer->foto_ktp ? '' : 'hidden' }} absolute inset-0 w-full h-full object-cover rounded-xl z-20">
                                                        <div class="text-center group-hover:text-indigo-400 transition-colors z-0 {{ $customer->foto_ktp ? 'hidden' : '' }}" id="edit-placeholder-ktp-{{ $customer->id }}">
                                                            <svg class="w-6 h-6 text-slate-500 group-hover:text-indigo-400 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            <span class="text-[10px] text-slate-450 font-semibold">Ubah Foto</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Foto 2: Rumah -->
                                                <div class="space-y-1">
                                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase">Foto Depan Rumah</label>
                                                    <div class="relative border border-dashed border-slate-800 hover:border-indigo-500 rounded-xl p-3 flex flex-col items-center justify-center bg-slate-950/40 aspect-video transition-colors group overflow-hidden">
                                                        <input type="file" name="foto_rumah" accept="image/*" onchange="previewImage(this, 'edit-preview-rumah-{{ $customer->id }}', 'edit-placeholder-rumah-{{ $customer->id }}')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                        <img id="edit-preview-rumah-{{ $customer->id }}" src="{{ $customer->foto_rumah ? asset('storage/' . $customer->foto_rumah) : '' }}" class="{{ $customer->foto_rumah ? '' : 'hidden' }} absolute inset-0 w-full h-full object-cover rounded-xl z-20">
                                                        <div class="text-center group-hover:text-indigo-400 transition-colors z-0 {{ $customer->foto_rumah ? 'hidden' : '' }}" id="edit-placeholder-rumah-{{ $customer->id }}">
                                                            <svg class="w-6 h-6 text-slate-500 group-hover:text-indigo-400 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            <span class="text-[10px] text-slate-450 font-semibold">Ubah Foto</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Foto 3: ONT -->
                                                <div class="space-y-1">
                                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase">Foto ONT / Modem</label>
                                                    <div class="relative border border-dashed border-slate-800 hover:border-indigo-500 rounded-xl p-3 flex flex-col items-center justify-center bg-slate-950/40 aspect-video transition-colors group overflow-hidden">
                                                        <input type="file" name="foto_perangkat" accept="image/*" onchange="previewImage(this, 'edit-preview-perangkat-{{ $customer->id }}', 'edit-placeholder-perangkat-{{ $customer->id }}')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                        <img id="edit-preview-perangkat-{{ $customer->id }}" src="{{ $customer->foto_perangkat ? asset('storage/' . $customer->foto_perangkat) : '' }}" class="{{ $customer->foto_perangkat ? '' : 'hidden' }} absolute inset-0 w-full h-full object-cover rounded-xl z-20">
                                                        <div class="text-center group-hover:text-indigo-400 transition-colors z-0 {{ $customer->foto_perangkat ? 'hidden' : '' }}" id="edit-placeholder-perangkat-{{ $customer->id }}">
                                                            <svg class="w-6 h-6 text-slate-500 group-hover:text-indigo-400 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            <span class="text-[10px] text-slate-450 font-semibold">Ubah Foto</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Foto 4: ODP -->
                                                <div class="space-y-1">
                                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase">Foto Port ODP / Dropcore</label>
                                                    <div class="relative border border-dashed border-slate-800 hover:border-indigo-500 rounded-xl p-3 flex flex-col items-center justify-center bg-slate-950/40 aspect-video transition-colors group overflow-hidden">
                                                        <input type="file" name="foto_odp" accept="image/*" onchange="previewImage(this, 'edit-preview-odp-{{ $customer->id }}', 'edit-placeholder-odp-{{ $customer->id }}')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                        <img id="edit-preview-odp-{{ $customer->id }}" src="{{ $customer->foto_odp ? asset('storage/' . $customer->foto_odp) : '' }}" class="{{ $customer->foto_odp ? '' : 'hidden' }} absolute inset-0 w-full h-full object-cover rounded-xl z-20">
                                                        <div class="text-center group-hover:text-indigo-400 transition-colors z-0 {{ $customer->foto_odp ? 'hidden' : '' }}" id="edit-placeholder-odp-{{ $customer->id }}">
                                                            <svg class="w-6 h-6 text-slate-500 group-hover:text-indigo-400 mx-auto mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            <span class="text-[10px] text-slate-455 font-semibold">Ubah Foto</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    </div>

                                    <div class="px-6 py-5 border-t border-slate-800/80 bg-slate-900/60 flex items-center justify-end gap-3 shrink-0">
                                        <button type="button" onclick="document.getElementById('edit-modal-{{ $customer->id }}').classList.add('hidden'); document.getElementById('edit-modal-{{ $customer->id }}').classList.remove('flex')" class="bg-slate-800 hover:bg-slate-750 border border-slate-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-all duration-300 text-sm">Batal</button>
                                        <button type="submit" class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold px-5 py-2.5 rounded-xl hover:from-indigo-650 hover:to-purple-700 shadow-lg shadow-indigo-600/10 transition-all duration-300 text-sm">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    
@endforeach

<!-- GLOBAL WHATSAPP MESSAGE MODAL -->
<div id="wa-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full relative shadow-2xl shadow-slate-950 my-8 overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800/80 shrink-0">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.416 1.46 5.867 0 10.642-4.77 10.646-10.643.002-2.846-1.104-5.523-3.11-7.533C17.593 4.432 14.92 3.328 12.073 3.328 6.205 3.328 1.43 8.098 1.426 13.97c-.001 1.936.505 3.826 1.466 5.425l-.962 3.516 3.6-.945zm11.367-7.46c-.327-.164-1.938-.956-2.238-1.066-.3-.11-.519-.164-.738.164-.219.328-.847 1.066-1.039 1.284-.192.219-.383.246-.71.082-.327-.164-1.383-.51-2.634-1.627-.973-.867-1.63-1.939-1.821-2.266-.192-.328-.02-.505.143-.668.147-.146.327-.383.49-.574.164-.191.219-.328.328-.546.11-.219.055-.41-.027-.574-.082-.164-.738-1.777-1.011-2.434-.266-.64-.539-.554-.738-.564-.19-.01-.41-.01-.628-.01-.219 0-.574.082-.875.41-.3.328-1.148 1.12-1.148 2.73 0 1.61 1.175 3.167 1.339 3.385.164.218 2.31 3.528 5.597 4.949.781.338 1.39.54 1.866.69.784.249 1.498.214 2.062.129.629-.094 1.938-.792 2.211-1.558.273-.766.273-1.422.191-1.558-.082-.137-.3-.218-.627-.382z"/>
                </svg>
                <span>Kirim Pesan WhatsApp</span>
            </h3>
            <button type="button" onclick="closeWaModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto space-y-4 text-left">
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1 uppercase">Penerima</label>
                <div class="text-white font-bold text-sm bg-slate-950 px-4 py-2.5 rounded-xl border border-slate-800">
                    <span id="wa-recipient-name">-</span> (<span id="wa-recipient-phone" class="text-indigo-400">-</span>)
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Pilih Template Pesan</label>
                <select id="wa-template-select" onchange="handleTemplateChange(this)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    <option value="" selected>-- Tulis Pesan Kustom --</option>
                    @foreach ($waTemplates as $tmpl)
                        <option value="{{ $tmpl->id }}">{{ $tmpl->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-450 mb-1.5 uppercase font-bold text-slate-400">Isi Pesan</label>
                <textarea id="wa-message-text" rows="6" required placeholder="Tulis isi pesan WhatsApp di sini..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors font-mono"></textarea>
            </div>
            <div class="bg-indigo-500/5 border border-indigo-500/10 rounded-xl p-3 text-[11px] text-slate-400 space-y-1">
                <span class="font-bold text-indigo-400 block mb-0.5">Placeholders yang didukung template:</span>
                <div><code class="text-indigo-355 font-bold">{nama_pelanggan}</code> - Nama Lengkap</div>
                <div><code class="text-indigo-355 font-bold">{nominal}</code> - Tarif Paket / Tagihan Terbaru</div>
                <div><code class="text-indigo-355 font-bold">{nomor_tagihan}</code> - Nomor Invoice Terbaru</div>
                <div><code class="text-indigo-355 font-bold">{bulan}</code> - Periode Bulan</div>
                <div><code class="text-indigo-355 font-bold">{nama_odp}</code> - ODP Terhubung</div>
            </div>
        </div>
        <div class="px-6 py-5 border-t border-slate-800/80 bg-slate-900/60 flex items-center justify-end gap-3 shrink-0">
            <button type="button" onclick="closeWaModal()" class="bg-slate-800 hover:bg-slate-750 border border-slate-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-all duration-300 text-sm">Batal</button>
            <button type="button" id="btn-send-wa-gateway" onclick="sendWaMessage()" class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold px-5 py-2.5 rounded-xl hover:from-emerald-650 hover:to-teal-700 shadow-lg shadow-emerald-600/10 transition-all duration-300 text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.416 1.46 5.867 0 10.642-4.77 10.646-10.643.002-2.846-1.104-5.523-3.11-7.533C17.593 4.432 14.92 3.328 12.073 3.328 6.205 3.328 1.43 8.098 1.426 13.97c-.001 1.936.505 3.826 1.466 5.425l-.962 3.516 3.6-.945zm11.367-7.46c-.327-.164-1.938-.956-2.238-1.066-.3-.11-.519-.164-.738.164-.219.328-.847 1.066-1.039 1.284-.192.219-.383.246-.71.082-.327-.164-1.383-.51-2.634-1.627-.973-.867-1.63-1.939-1.821-2.266-.192-.328-.02-.505.143-.668.147-.146.327-.383.49-.574.164-.191.219-.328.328-.546.11-.219.055-.41-.027-.574-.082-.164-.738-1.777-1.011-2.434-.266-.64-.539-.554-.738-.564-.19-.01-.41-.01-.628-.01-.219 0-.574.082-.875.41-.3.328-1.148 1.12-1.148 2.73 0 1.61 1.175 3.167 1.339 3.385.164.218 2.31 3.528 5.597 4.949.781.338 1.39.54 1.866.69.784.249 1.498.214 2.062.129.629-.094 1.938-.792 2.211-1.558.273-.766.273-1.422.191-1.558-.082-.137-.3-.218-.627-.382z"/>
                </svg>
                <span>Kirim WhatsApp</span>
            </button>
        </div>
    </div>
</div>


<script>
    // JS Image Preview Handler

    function confirmDeleteAllCustomers(form) {
        const typed = prompt('Ketik HAPUS untuk menghapus SEMUA data pelanggan. Data invoice, transaksi wallet, PPOB, dan riwayat status terkait pelanggan juga dapat ikut terhapus sesuai relasi database.');
        if (typed !== 'HAPUS') {
            alert('Penghapusan dibatalkan. Ketik HAPUS dengan huruf besar jika benar-benar ingin menghapus semua pelanggan.');
            return false;
        }

        form.querySelector('input[name="confirmation"]').value = typed;
        return confirm('Konfirmasi terakhir: semua data pelanggan akan dihapus. Lanjutkan?');
    }

    function selectedCustomerCheckboxes() {
        return Array.from(document.querySelectorAll('.customer-select-checkbox:checked'));
    }

    function updateSelectedCustomersCount() {
        const selectedCount = selectedCustomerCheckboxes().length;
        const countLabel = document.getElementById('selected-customers-count');
        const selectAll = document.getElementById('select-all-customers');
        const allCheckboxes = Array.from(document.querySelectorAll('.customer-select-checkbox'));

        if (countLabel) {
            countLabel.textContent = selectedCount;
        }

        if (selectAll) {
            selectAll.checked = allCheckboxes.length > 0 && selectedCount === allCheckboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < allCheckboxes.length;
        }
    }

    function toggleAllCustomerSelection(source) {
        setAllCustomerSelection(source.checked);
    }

    function setAllCustomerSelection(checked) {
        document.querySelectorAll('.customer-select-checkbox').forEach((checkbox) => {
            checkbox.checked = checked;
        });
        updateSelectedCustomersCount();
    }

    function submitSelectedCustomersDelete() {
        const selected = selectedCustomerCheckboxes();
        if (selected.length === 0) {
            alert('Centang minimal satu pelanggan dulu.');
            return false;
        }

        const typed = prompt('Ketik HAPUS untuk menghapus pelanggan yang dicentang.');
        if (typed !== 'HAPUS') {
            alert('Penghapusan dibatalkan. Ketik HAPUS dengan huruf besar jika benar-benar ingin menghapus pelanggan terpilih.');
            return false;
        }

        if (!confirm(`Konfirmasi terakhir: hapus ${selected.length} pelanggan terpilih?`)) {
            return false;
        }

        const form = document.getElementById('bulk-delete-customers-form');
        const inputs = document.getElementById('bulk-delete-customer-inputs');
        inputs.innerHTML = '';

        selected.forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'customer_ids[]';
            input.value = checkbox.value;
            inputs.appendChild(input);
        });

        form.querySelector('input[name="confirmation"]').value = typed;
        form.submit();
        return true;
    }

    function submitBulkBillingMode(event) {
        const selected = selectedCustomerCheckboxes();
        if (selected.length === 0) {
            event.preventDefault();
            alert('Centang minimal satu pelanggan dulu.');
            return false;
        }

        const select = document.getElementById('bulk-billing-mode-select');
        const label = select.options[select.selectedIndex].text;
        if (!confirm(`Ubah ${selected.length} pelanggan menjadi ${label}?`)) {
            event.preventDefault();
            return false;
        }

        const inputs = document.getElementById('bulk-billing-mode-inputs');
        inputs.innerHTML = '';
        selected.forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'customer_ids[]';
            input.value = checkbox.value;
            inputs.appendChild(input);
        });

        return true;
    }
    
    // WhatsApp Send & Template Pre-fill Engine
    let currentCustomer = null;
    const waTemplates = @json($waTemplates);

    function openWaModal(customer) {
        currentCustomer = customer;
        document.getElementById('wa-recipient-name').textContent = customer.name;
        document.getElementById('wa-recipient-phone').textContent = customer.phone;
        
        // Select first template or reset
        const templateSelect = document.getElementById('wa-template-select');
        templateSelect.value = "";
        
        document.getElementById('wa-message-text').value = "";
        
        // Show modal
        const modal = document.getElementById('wa-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // Bridge: read JSON from data-wa-customer attribute → call openWaModal
    function openWaModalFromBtn(btn) {
        try {
            const data = JSON.parse(btn.getAttribute('data-wa-customer'));
            openWaModal(data);
        } catch(e) {
            console.error('WA customer modal parse error:', e);
        }
    }

    function closeWaModal() {
        const modal = document.getElementById('wa-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentCustomer = null;
    }

    function handleTemplateChange(selectEl) {
        if (!currentCustomer) return;
        const templateId = selectEl.value;
        if (!templateId) {
            document.getElementById('wa-message-text').value = "";
            return;
        }
        
        const template = waTemplates.find(t => t.id == templateId);
        if (!template) return;
        
        let content = template.content;
        
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const currentMonthName = months[new Date().getMonth()];

        // Replace placeholders dynamically
        content = content.replace(/{nama_pelanggan}/g, currentCustomer.name);
        content = content.replace(/{nama_odp}/g, currentCustomer.odp_name || 'Tidak Terhubung');
        content = content.replace(/{nomor_tagihan}/g, currentCustomer.latest_invoice_number || 'INV-xxxx');
        content = content.replace(/{bulan}/g, currentCustomer.latest_invoice_month || currentMonthName);
        content = content.replace(/{nominal}/g, currentCustomer.latest_invoice_amount || currentCustomer.package_price || '0');
        
        document.getElementById('wa-message-text').value = content;
    }

    async function sendWaMessage() {
        if (!currentCustomer) return;
        const message = document.getElementById('wa-message-text').value;
        if (!message.trim()) {
            alert('Pesan tidak boleh kosong!');
            return;
        }

        const sendButton = document.getElementById('btn-send-wa-gateway');
        const originalButtonHtml = sendButton.innerHTML;
        sendButton.disabled = true;
        sendButton.classList.add('opacity-60', 'cursor-wait');
        sendButton.innerHTML = `<svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Mengirim via Gateway...</span>`;
        
        try {
            const response = await fetch("{{ route('wa.send', [], false) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    phone: currentCustomer.phone,
                    message: message
                })
            });

            const responseText = await response.text();
            let data = {};

            try {
                data = responseText ? JSON.parse(responseText) : {};
            } catch (parseError) {
                data = {
                    success: false,
                    message: response.status === 419
                        ? 'Session/CSRF kedaluwarsa. Refresh halaman lalu login ulang.'
                        : `Server membalas bukan JSON (HTTP ${response.status}). Cek log Laravel.`
                };
            }

            if (response.ok && data.success) {
                alert(data.message || 'Pesan berhasil dikirim via WA Gateway.');
                closeWaModal();
            } else {
                alert(data.message || `Gagal kirim via WA Gateway (HTTP ${response.status}).`);
            }
        } catch (err) {
            alert(err.message || 'Error jaringan saat mengirim via WA Gateway.');
        } finally {
            sendButton.disabled = false;
            sendButton.classList.remove('opacity-60', 'cursor-wait');
            sendButton.innerHTML = originalButtonHtml;
        }
    }


    function previewImage(input, previewId, placeholderId) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);
        const placeholder = document.getElementById(placeholderId);
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
            }
            reader.readAsDataURL(file);
        }
    }

    // Toggle Container Pemilik Rumah (Jika status Sewa/Ngontrak)
    function togglePemilikRumah(selectEl, containerId) {
        const container = document.getElementById(containerId);
        if (selectEl.value === 'ngontrak') {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }

    // Dynamic Device Options Data & Handler
    const deviceOptions = {
        'FO': [
            { value: 'ZTE F640', label: 'ZTE F640' },
            { value: 'ZTE F609', label: 'ZTE F609' },
            { value: 'Huawei HG8245H', label: 'Huawei HG8245H' }
        ],
        'LAN': [
            { value: 'Tenda', label: 'Tenda' },
            { value: 'Tenda F3', label: 'Tenda F3' },
            { value: 'Tenda N301', label: 'Tenda N301' },
            { value: 'Totolink N200RE', label: 'Totolink N200RE' }
        ]
    };

    function updateDeviceOptions(jalurSelect, deviceSelectId, selectedValue = '') {
        const deviceSelect = document.getElementById(deviceSelectId);
        const jalurVal = jalurSelect.value;
        
        // Clear previous options
        deviceSelect.innerHTML = '<option value="">-- Pilih Perangkat --</option>';
        
        if (jalurVal && deviceOptions[jalurVal]) {
            deviceOptions[jalurVal].forEach(option => {
                const optEl = document.createElement('option');
                optEl.value = option.value;
                optEl.textContent = option.label;
                if (selectedValue === option.value) {
                    optEl.selected = true;
                }
                deviceSelect.appendChild(optEl);
            });
            
            // Auto-select first option if none is selected
            if (!selectedValue && deviceOptions[jalurVal].length > 0) {
                deviceSelect.options[1].selected = true;
            }
        }
    }

    // Run on page load to initialize edit forms
    document.addEventListener('DOMContentLoaded', function() {
        @foreach($customers as $customer)
            @if($customer->jalur)
                const editJalurSelect = document.getElementById('edit-jalur-{{ $customer->id }}');
                if (editJalurSelect) {
                    updateDeviceOptions(editJalurSelect, 'edit-perangkat-{{ $customer->id }}', '{{ $customer->perangkat }}');
                }
            @endif
        @endforeach
    });
</script>

{{-- ====== IMPORT MODAL ====== --}}
<div id="import-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-700/60 rounded-2xl w-full max-w-md shadow-2xl shadow-slate-950/60 overflow-hidden">

        {{-- Header --}}
        <div class="relative bg-gradient-to-r from-amber-600/20 to-orange-600/10 border-b border-slate-800/80 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white leading-tight">Import Pelanggan</h3>
                    <p class="text-[10px] text-amber-400/80 font-medium">Upload file .xlsx, .xls, atau .csv</p>
                </div>
            </div>
            <button type="button"
                onclick="document.getElementById('import-modal').classList.add('hidden'); document.getElementById('import-modal').classList.remove('flex')"
                class="text-slate-500 hover:text-white hover:bg-slate-800 p-1.5 rounded-lg transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf

            {{-- Info box --}}
            <div class="bg-indigo-500/5 border border-indigo-500/20 rounded-xl p-3.5 text-xs text-slate-400 space-y-1.5 leading-relaxed">
                <div class="font-bold text-indigo-400 text-[11px] uppercase tracking-wide mb-1">Petunjuk Import</div>
                <div class="flex items-start gap-1.5">
                    <span class="text-indigo-400 shrink-0">•</span>
                    Gunakan file <code class="text-amber-300 font-mono">.xlsx</code> Excel biasa, file <code class="text-amber-300 font-mono">.xls</code> yang diekspor dari sistem ini, atau file <code class="text-amber-300 font-mono">.csv</code>.
                </div>
                <div class="flex items-start gap-1.5">
                    <span class="text-indigo-400 shrink-0">•</span>
                    Kolom wajib: <strong class="text-slate-300">Nama, No. Telepon, Alamat, Paket</strong>.
                </div>
                <div class="flex items-start gap-1.5">
                    <span class="text-indigo-400 shrink-0">•</span>
                    Urutan minimal juga boleh: <strong class="text-slate-300">Nama | No. Telepon | Alamat | Paket</strong>.
                </div>
                <div class="flex items-start gap-1.5">
                    <span class="text-indigo-400 shrink-0">•</span>
                    Jika No. Telepon sudah ada, data akan <strong class="text-slate-300">diperbarui</strong> (bukan duplikasi).
                </div>
                <div class="flex items-start gap-1.5">
                    <span class="text-amber-400 shrink-0">↓</span>
                    <a href="{{ route('customers.export') }}" class="text-amber-400 hover:text-amber-300 font-semibold underline">Download template contoh</a> terlebih dahulu.
                </div>
            </div>

            {{-- File Input --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-2 uppercase tracking-wider">Pilih File</label>
                <label for="import-file-input" class="flex flex-col items-center justify-center gap-2 w-full h-28 border-2 border-dashed border-slate-700 hover:border-amber-500/50 bg-slate-950/60 rounded-xl cursor-pointer transition-all group">
                    <svg class="w-7 h-7 text-slate-500 group-hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span id="import-file-label" class="text-xs text-slate-500 group-hover:text-slate-300 transition-colors">Klik untuk pilih file .xlsx / .xls / .csv</span>
                    <input id="import-file-input" type="file" name="import_file" accept=".xls,.csv,.txt,.xlsx" class="hidden"
                        onchange="document.getElementById('import-file-label').textContent = this.files[0] ? this.files[0].name : 'Klik untuk pilih file .xlsx / .xls / .csv'">
                </label>
            </div>

            {{-- Footer actions --}}
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button"
                    onclick="document.getElementById('import-modal').classList.add('hidden'); document.getElementById('import-modal').classList.remove('flex')"
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
</div>

<!-- Modal Broadcast Customers -->
<div id="modal-broadcast" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl animate-scale-up">
        <div class="px-6 py-5 border-b border-slate-800/80 flex items-center justify-between bg-slate-900/50">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                    Broadcast Pesan WA
                </h3>
                <p class="text-xs text-slate-400 mt-1">Kirim pesan WhatsApp massal ke semua pelanggan di halaman ini.</p>
            </div>
            <button onclick="closeBroadcastModal()" class="text-slate-400 hover:text-white transition-colors" id="btn-close-modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
            <!-- WA Template Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wider">Pilih Template Pesan</label>
                <select id="broadcast-wa-template-select" onchange="applyWaTemplate()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-green-500 transition-colors appearance-none">
                    <option value="">-- Pilih Template Pesan --</option>
                    @foreach($waTemplates as $t)
                        <option value="{{ $t->id }}">{{ $t->title }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Message Content -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Isi Pesan Peringatan</label>
                    <span class="text-[10px] text-slate-500 bg-slate-800/50 px-2 py-0.5 rounded">Gunakan tag {nama_pelanggan}</span>
                </div>
                <textarea id="broadcast-wa-message-text" rows="6" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-green-500 transition-colors font-mono resize-none" placeholder="Tulis isi pesan gangguan di sini..."></textarea>
            </div>

            <!-- Customers List -->
            <div>
                <div class="flex items-center justify-between mb-2 border-b border-slate-800/60 pb-2">
                    <div class="flex items-center gap-4">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Target Penerima</label>
                        <div class="flex items-center gap-2 border-l border-slate-700/60 pl-4">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Jeda per Pesan:</label>
                            <div class="flex items-center gap-1.5">
                                <input type="number" id="wa-delay-input" value="2" min="1" max="60" class="w-16 bg-slate-950 border border-slate-800 rounded px-2 py-1 text-xs text-slate-200 focus:outline-none focus:border-green-500 transition-colors text-center">
                                <span class="text-[10px] text-slate-500">Detik</span>
                            </div>
                        </div>
                    </div>
                    <span id="target-count" class="text-xs font-bold text-indigo-400 bg-indigo-500/10 px-2.5 py-1 rounded-lg">0 Pelanggan</span>
                </div>
                <div class="bg-slate-950 border border-slate-800 rounded-xl max-h-48 overflow-y-auto">
                    <ul id="customers-list" class="divide-y divide-slate-800/50 text-sm">
                        <!-- Customers will be populated here -->
                    </ul>
                </div>
            </div>

            <!-- Progress Bar -->
            <div id="broadcast-progress-container" class="hidden space-y-2">
                <div class="flex items-center justify-between text-xs font-bold">
                    <span class="text-slate-300">Progres Pengiriman</span>
                    <span id="broadcast-progress-text" class="text-green-400">0%</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                    <div id="broadcast-progress-bar" class="bg-gradient-to-r from-green-500 to-emerald-400 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-800/80 flex justify-end gap-3 bg-slate-900/50">
            <button type="button" id="btn-cancel-broadcast" onclick="closeBroadcastModal()" class="px-5 py-2.5 rounded-xl font-bold text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">Batal</button>
            <button type="button" id="btn-start-broadcast" onclick="startBroadcast()" class="px-5 py-2.5 bg-green-600 hover:bg-green-500 rounded-xl font-bold text-sm text-white shadow-lg shadow-green-600/20 transition-all duration-300 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                <span>Mulai Broadcast</span>
            </button>
        </div>
    </div>
</div>

<script>
    let broadcastWaTemplates = @json($waTemplates ?? []);
    let targetCustomers = @json($customers->items());
    let isBroadcasting = false;

    function confirmCustomerStatus(form, message) {
        if (!confirm(message)) {
            return false;
        }

        const reason = prompt('Catatan/alasan perubahan status (opsional):');
        if (reason === null) {
            return false;
        }

        const reasonInput = form.querySelector('input[name="reason"]');
        if (reasonInput) {
            reasonInput.value = reason;
        }

        if (form.action && form.action.includes('/stop-service')) {
            const now = new Date();
            const pad = value => String(value).padStart(2, '0');
            const defaultStoppedAt = [
                now.getFullYear(),
                pad(now.getMonth() + 1),
                pad(now.getDate())
            ].join('-') + ' ' + [
                pad(now.getHours()),
                pad(now.getMinutes())
            ].join(':');

            const stoppedAt = prompt('Tanggal berhenti manual (format: YYYY-MM-DD HH:MM). Kosongkan untuk waktu sekarang:', defaultStoppedAt);
            if (stoppedAt === null) {
                return false;
            }

            let stoppedAtInput = form.querySelector('input[name="stopped_at"]');
            if (!stoppedAtInput) {
                stoppedAtInput = document.createElement('input');
                stoppedAtInput.type = 'hidden';
                stoppedAtInput.name = 'stopped_at';
                form.appendChild(stoppedAtInput);
            }
            stoppedAtInput.value = stoppedAt.trim();
        }

        return true;
    }

    function openBroadcastModal() {
        document.getElementById('target-count').textContent = targetCustomers.length + ' Pelanggan';
        
        const list = document.getElementById('customers-list');
        list.innerHTML = '';
        
        if (targetCustomers.length === 0) {
            list.innerHTML = '<li class="px-4 py-4 text-center text-slate-500 italic">Tidak ada pelanggan untuk dikirimi pesan.</li>';
        } else {
            targetCustomers.forEach((c, index) => {
                list.innerHTML += `
                    <li class="px-4 py-2.5 flex items-center justify-between hover:bg-slate-900/50 transition-colors" id="customer-row-${index}">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-400">${index + 1}</div>
                            <div>
                                <p class="text-slate-200 font-semibold">${c.name}</p>
                                <p class="text-[10px] text-slate-500 font-mono" id="customer-message-${index}">${c.phone}</p>
                            </div>
                        </div>
                        <div id="customer-status-${index}" class="text-xs font-semibold px-2 py-1 rounded bg-slate-800 text-slate-400">Menunggu</div>
                    </li>
                `;
            });
        }

        // Reset states
        document.getElementById('broadcast-wa-template-select').value = "";
        document.getElementById('broadcast-wa-message-text').value = "";
        document.getElementById('broadcast-progress-container').classList.add('hidden');
        document.getElementById('broadcast-progress-bar').style.width = '0%';
        document.getElementById('broadcast-progress-text').textContent = '0%';
        
        const btnStart = document.getElementById('btn-start-broadcast');
        btnStart.disabled = targetCustomers.length === 0;
        btnStart.classList.toggle('opacity-50', targetCustomers.length === 0);
        btnStart.classList.toggle('cursor-not-allowed', targetCustomers.length === 0);
        btnStart.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg><span>Mulai Broadcast</span>`;

        document.getElementById('modal-broadcast').classList.remove('hidden');
    }

    function closeBroadcastModal() {
        if (isBroadcasting) {
            if(!confirm('Proses pengiriman sedang berjalan. Yakin ingin menutup? (Pesan yang belum terkirim akan dibatalkan)')) return;
        }
        document.getElementById('modal-broadcast').classList.add('hidden');
    }

    function applyWaTemplate() {
        const templateId = document.getElementById('broadcast-wa-template-select').value;
        if (!templateId) {
            document.getElementById('broadcast-wa-message-text').value = "";
            return;
        }
        
        const template = broadcastWaTemplates.find(t => t.id == templateId);
        if (!template) return;
        
        document.getElementById('broadcast-wa-message-text').value = template.content;
    }

    async function startBroadcast() {
        if (targetCustomers.length === 0) return;
        
        const templateMessage = document.getElementById('broadcast-wa-message-text').value;
        if (!templateMessage.trim()) {
            alert('Isi pesan tidak boleh kosong! Silakan pilih template atau ketik manual.');
            return;
        }

        if (!confirm('Peringatan: Sistem akan mengirim pesan ke ' + targetCustomers.length + ' pelanggan menggunakan API Fonnte. Lanjutkan?')) return;

        isBroadcasting = true;
        
        // Disable UI
        document.getElementById('btn-start-broadcast').disabled = true;
        document.getElementById('btn-start-broadcast').classList.add('opacity-50', 'cursor-wait');
        document.getElementById('btn-start-broadcast').innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Mengirim...</span>`;
        document.getElementById('broadcast-wa-message-text').disabled = true;
        document.getElementById('broadcast-wa-template-select').disabled = true;
        
        const progressContainer = document.getElementById('broadcast-progress-container');
        const progressBar = document.getElementById('broadcast-progress-bar');
        const progressText = document.getElementById('broadcast-progress-text');
        progressContainer.classList.remove('hidden');

        let successCount = 0;
        let failCount = 0;
        const customerIds = targetCustomers.map(c => c.id).filter(Boolean);

        if (customerIds.length === 0) {
            alert('Data pelanggan pada halaman ini belum valid untuk broadcast. Refresh halaman lalu coba lagi.');
            isBroadcasting = false;
            document.getElementById('broadcast-wa-message-text').disabled = false;
            document.getElementById('broadcast-wa-template-select').disabled = false;
            document.getElementById('btn-start-broadcast').disabled = false;
            return;
        }

        targetCustomers.forEach((c, index) => {
            const statusBadge = document.getElementById(`customer-status-${index}`);
            const row = document.getElementById(`customer-row-${index}`);
            statusBadge.className = 'text-[10px] font-bold px-2 py-1 rounded bg-amber-500/20 text-amber-400 uppercase tracking-wide flex items-center gap-1';
            statusBadge.innerHTML = `<svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mengirim`;
            if (index === 0 && row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });

        try {
            const response = await fetch("{{ route('wa.broadcast', [], false) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    customer_ids: customerIds,
                    message: templateMessage,
                    delay: parseInt(document.getElementById('wa-delay-input').value) || 2
                })
            });

            const responseText = await response.text();
            let data = {};

            try {
                data = responseText ? JSON.parse(responseText) : {};
            } catch (parseError) {
                data = {
                    success: false,
                    message: response.status === 419
                        ? 'Session/CSRF kedaluwarsa. Refresh halaman lalu login ulang.'
                        : `Server membalas bukan JSON (HTTP ${response.status}). Cek log Laravel.`
                };
            }

            if (response.ok && data.success) {
                successCount = data.sent_count || customerIds.length;
                failCount = Math.max(targetCustomers.length - successCount, 0);

                targetCustomers.forEach((c, index) => {
                    const statusBadge = document.getElementById(`customer-status-${index}`);
                    statusBadge.className = 'text-[10px] font-bold px-2 py-1 rounded bg-emerald-500/20 text-emerald-400 uppercase tracking-wide';
                    statusBadge.textContent = 'Diproses';
                    document.getElementById(`customer-message-${index}`).textContent = c.phone + ' - ' + data.message;
                });
            } else {
                const errorMessage = data.message || `Gagal mengirim broadcast (HTTP ${response.status})`;
                failCount = targetCustomers.length;

                targetCustomers.forEach((c, index) => {
                    const statusBadge = document.getElementById(`customer-status-${index}`);
                    statusBadge.className = 'text-[10px] font-bold px-2 py-1 rounded bg-rose-500/20 text-rose-400 uppercase tracking-wide';
                    statusBadge.textContent = 'Gagal';
                    statusBadge.title = errorMessage;
                    document.getElementById(`customer-message-${index}`).textContent = c.phone + ' - ' + errorMessage;
                });
            }
        } catch (err) {
            const errorMessage = err.message || 'Error jaringan';
            failCount = targetCustomers.length;

            targetCustomers.forEach((c, index) => {
                const statusBadge = document.getElementById(`customer-status-${index}`);
                statusBadge.className = 'text-[10px] font-bold px-2 py-1 rounded bg-rose-500/20 text-rose-400 uppercase tracking-wide';
                statusBadge.textContent = 'Error Jaringan';
                statusBadge.title = errorMessage;
                document.getElementById(`customer-message-${index}`).textContent = c.phone + ' - ' + errorMessage;
            });
        }

        progressBar.style.width = '100%';
        progressText.textContent = '100%';

        isBroadcasting = false;
        
        document.getElementById('broadcast-wa-message-text').disabled = false;
        document.getElementById('broadcast-wa-template-select').disabled = false;
        
        const btnStart = document.getElementById('btn-start-broadcast');
        btnStart.disabled = false;
        btnStart.classList.remove('opacity-50', 'cursor-wait');
        btnStart.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg><span>Selesai (${successCount} Terkirim)</span>`;
        btnStart.classList.remove('bg-green-600', 'hover:bg-green-500');
        btnStart.classList.add('bg-slate-700', 'hover:bg-slate-600', 'cursor-not-allowed');
        
        alert(`Selesai! Berhasil terkirim: ${successCount}, Gagal: ${failCount}`);
    }
    window.parseCoordinatesFromText = function(text) {
        const source = String(text || '').trim();
        let decoded = source.replace(/\+/g, ' ');
        try {
            decoded = decodeURIComponent(decoded);
        } catch (error) {
            decoded = source;
        }

        const isValidLatLng = function(lat, lng) {
            const latNum = Number(lat);
            const lngNum = Number(lng);
            return Number.isFinite(latNum)
                && Number.isFinite(lngNum)
                && latNum >= -90
                && latNum <= 90
                && lngNum >= -180
                && lngNum <= 180;
        };

        const patterns = [
            /@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/,
            /(?:q|query|center|ll)=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/,
            /!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/,
            /(?:place|dir|search)\/.*?\/?(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/,
            /[?&]destination=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/,
            /[?&]saddr=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/,
            /[?&]daddr=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/,
            /^(-?\d+(?:\.\d+)?)(?:\s*,\s*|\s+)(-?\d+(?:\.\d+)?)$/
        ];

        for (const pattern of patterns) {
            const match = decoded.match(pattern) || source.match(pattern);
            if (match && isValidLatLng(match[1], match[2])) {
                return {
                    lat: match[1],
                    lng: match[2]
                };
            }
        }

        const looseMatch = decoded.match(/(-?\d{1,2}\.\d{4,})\s*,\s*(-?\d{2,3}\.\d{4,})/);
        if (looseMatch && isValidLatLng(looseMatch[1], looseMatch[2])) {
            return {
                lat: looseMatch[1],
                lng: looseMatch[2]
            };
        }

        return null;
    };

    window.applyExtractedCoordinates = function(latId, lngId, btn, originalClasses, originalHtml, lat, lng) {
        document.getElementById(latId).value = lat;
        document.getElementById(lngId).value = lng;
        
        document.getElementById(latId).classList.add('bg-emerald-500/20', 'border-emerald-500');
        document.getElementById(lngId).classList.add('bg-emerald-500/20', 'border-emerald-500');
        
        btn.className = 'bg-emerald-600 text-white px-3 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1 shrink-0';
        btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Sukses!</span>`;
        
        setTimeout(() => {
            document.getElementById(latId).classList.remove('bg-emerald-500/20', 'border-emerald-500');
            document.getElementById(lngId).classList.remove('bg-emerald-500/20', 'border-emerald-500');
            btn.className = originalClasses;
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }, 2000);
    };

    window.extractCoordinatesBtn = async function(inputId, latId, lngId, btnId) {
        const mapInput = document.getElementById(inputId);
        const latInput = document.getElementById(latId);
        const lngInput = document.getElementById(lngId);
        const btn = document.getElementById(btnId);

        if (!mapInput || !latInput || !lngInput || !btn) {
            alert('Kolom link maps atau koordinat tidak ditemukan. Muat ulang halaman lalu coba lagi.');
            return;
        }

        const link = mapInput.value.trim();
        const originalHtml = btn.innerHTML;
        const originalClasses = btn.className;

        if (!link) {
            btn.className = 'bg-red-600 text-white px-3 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1 shrink-0';
            btn.innerHTML = `<span>Link Kosong!</span>`;
            setTimeout(() => {
                btn.className = originalClasses;
                btn.innerHTML = originalHtml;
            }, 2000);
            return;
        }

        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Mencari...</span>`;

        const localCoordinates = parseCoordinatesFromText(link);
        if (localCoordinates) {
            applyExtractedCoordinates(latId, lngId, btn, originalClasses, originalHtml, localCoordinates.lat, localCoordinates.lng);
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "{{ csrf_token() }}";
            const response = await fetch("{{ route('api.extract-coordinates', [], false) }}", {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ link: link })
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : null;

            if (!response.ok) {
                throw new Error(data?.message || `HTTP ${response.status}`);
            }
            
            if (data.success) {
                applyExtractedCoordinates(latId, lngId, btn, originalClasses, originalHtml, data.lat, data.lng);
            } else {
                btn.className = 'bg-red-600 text-white px-3 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1 shrink-0';
                btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg><span>${data?.message || 'Gagal'}</span>`;
                
                setTimeout(() => {
                    btn.className = originalClasses;
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }, 2500);
            }
        } catch (error) {
            console.error('Failed to extract coordinates via API', error);
            btn.className = 'bg-orange-600 text-white px-3 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1 shrink-0';
            btn.innerHTML = `<span>${error.message || 'Error Server'}</span>`;
            
            setTimeout(() => {
                btn.className = originalClasses;
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }, 2500);
        }
    };

    document.addEventListener('click', function(event) {
        const btn = event.target.closest('[data-coordinate-extract]');
        if (!btn) return;

        event.preventDefault();
        event.stopPropagation();

        btn.disabled = false;
        window.extractCoordinatesBtn(
            btn.dataset.mapInput,
            btn.dataset.latInput,
            btn.dataset.lngInput,
            btn.id
        );
    }, true);
</script>

@endsection
