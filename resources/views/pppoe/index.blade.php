@extends('layouts.app')

@section('content')
<style>
    .notebook-table {
        border-collapse: collapse;
        font-size: 12px;
        line-height: 1.25;
    }
    .notebook-table thead tr {
        background: color-mix(in oklab, var(--neutral-900) 92%, transparent);
    }
    .notebook-table th,
    .notebook-table td {
        border: 1px solid color-mix(in oklab, var(--neutral-700) 72%, transparent);
        padding: 7px 9px !important;
        vertical-align: middle;
    }
    .notebook-table th {
        color: var(--neutral-300);
        font-size: 10px;
        letter-spacing: 0;
        white-space: nowrap;
        padding-bottom: 6px !important;
        padding-top: 6px !important;
    }
    .notebook-table tbody tr:nth-child(even) {
        background: color-mix(in oklab, var(--neutral-900) 22%, transparent);
    }
    .notebook-table tbody tr:hover {
        background: color-mix(in oklab, var(--brand-600) 12%, transparent);
    }
    .notebook-table td:last-child button,
    .notebook-table td:last-child a {
        border-radius: 7px;
        padding: 5px !important;
    }
    .notebook-table td:last-child svg {
        height: 14px;
        width: 14px;
    }
    .notebook-table .inline-flex {
        border-radius: 6px;
    }
    .notebook-table td {
        height: 38px;
    }
    .notebook-table td .font-semibold,
    .notebook-table td .font-bold {
        line-height: 1.15;
    }
    .notebook-table td [class*="rounded-full"],
    .notebook-table td [class*="rounded-lg"] {
        padding-bottom: 3px;
        padding-top: 3px;
    }
</style>
<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                Server PPPoE
                @if($router)
                <span class="px-3 py-1 text-xs font-bold bg-emerald-500/10 text-emerald-400 rounded-full border border-emerald-500/20">Terkoneksi ke {{ $router->name }}</span>
                @else
                <span class="px-3 py-1 text-xs font-bold bg-rose-500/10 text-rose-400 rounded-full border border-rose-500/20">Router Offline</span>
                @endif
            </h1>
            <p class="text-slate-400 mt-1">
                Daftar akun PPPoE rahasia yang ada di dalam MikroTik Anda secara live.
                <span class="text-indigo-400 font-bold ml-1">Total: {{ $totalCustomers }} akun.</span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if($router)
            <div class="flex items-center gap-2 border border-slate-700 p-1.5 rounded-xl bg-slate-800/50">
                <form action="{{ route('pppoe.import') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Tarik semua akun dari MikroTik via API? (Bisa timeout jika data > 100)')" class="px-4 py-2 rounded-lg font-bold text-sm bg-slate-800 hover:bg-slate-700 text-white shadow-lg flex items-center gap-2 transition-all border border-slate-700">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        API Sync
                    </button>
                </form>
                
                <div class="h-6 w-px bg-slate-700"></div>
                
                <form action="{{ route('pppoe.importFile') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                    @csrf
                    <label class="cursor-pointer px-4 py-2 rounded-lg font-bold text-sm bg-slate-800 hover:bg-slate-700 text-slate-300 shadow-lg flex items-center gap-2 transition-all border border-slate-700" title="Upload file .rsc dari export MikroTik">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Upload .rsc
                        <input type="file" name="rsc_file" class="hidden" accept=".rsc,.txt" onchange="if(confirm('Upload file export ini?')) this.form.submit()">
                    </label>
                </form>
            </div>
            @endif
            <a href="{{ route('pppoe.create') }}" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/25 flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Akun Baru
            </a>
            <form action="{{ route('pppoe.reset-table') }}" method="POST" onsubmit="return confirmResetPppoeTable(this)">
                @csrf
                @method('DELETE')
                <input type="hidden" name="confirmation">
                <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-rose-500/10 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/25 shadow-lg shadow-rose-500/10 flex items-center gap-2 transition-all" title="Reset tabel PPPoE lokal">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0119 5m0 0h-4m4 0v4" /></svg>
                    Reset PPPoE
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="text-sm font-medium text-emerald-400">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl flex items-start gap-3">
            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="text-sm font-medium text-rose-400">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Filter, Sort, Group Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl p-4 mb-6">
        <form action="{{ route('pppoe.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-grow min-w-[200px]">
                <label class="block text-xs font-medium text-slate-400 mb-1">Cari Akun</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama username..." class="bg-slate-800 border border-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 text-white">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Filter Status</label>
                <select name="filter" class="bg-slate-800 border border-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 text-white">
                    <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>Semua Akun</option>
                    <option value="online" {{ $filter == 'online' ? 'selected' : '' }}>Online Saja</option>
                    <option value="offline" {{ $filter == 'offline' ? 'selected' : '' }}>Offline Saja</option>
                    <option value="disabled" {{ $filter == 'disabled' ? 'selected' : '' }}>Dinonaktifkan</option>
                    <optgroup label="Paket Layanan">
                        @foreach($packages as $pkg)
                        <option value="pkg_{{ $pkg->id }}" {{ $filter == 'pkg_'.$pkg->id ? 'selected' : '' }}>{{ $pkg->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Urutkan</label>
                <select name="sort" class="bg-slate-800 border border-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 text-white">
                    <option value="newest" {{ $sort == 'newest' ? 'selected' : '' }}>Terbaru Ditambahkan</option>
                    <option value="online_new" {{ $sort == 'online_new' ? 'selected' : '' }}>Baru Online</option>
                    <option value="online_old" {{ $sort == 'online_old' ? 'selected' : '' }}>Lama Online</option>
                    <option value="az" {{ $sort == 'az' ? 'selected' : '' }}>Nama (A-Z)</option>
                    <option value="za" {{ $sort == 'za' ? 'selected' : '' }}>Nama (Z-A)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Kelompokkan</label>
                <select name="group" class="bg-slate-800 border border-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 text-white">
                    <option value="none" {{ $group == 'none' ? 'selected' : '' }}>Tanpa Kelompok</option>
                    <option value="package" {{ $group == 'package' ? 'selected' : '' }}>Berdasarkan Paket</option>
                    <option value="odp" {{ $group == 'odp' ? 'selected' : '' }}>Berdasarkan ODP</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2.5 rounded-lg font-bold text-sm bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg transition-all ml-auto">
                Terapkan
            </button>
            <div class="text-[11px] text-slate-500 font-semibold px-3 py-2 rounded-lg bg-slate-950/60 border border-slate-800">
                Auto refresh <span id="pppoe-refresh-countdown" class="text-emerald-400">15</span> dtk
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="notebook-table w-full text-left">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 font-semibold">
                        <th class="px-6 py-4">Username (Secret)</th>
                        <th class="px-6 py-4">Pelanggan Billing</th>
                        <th class="px-6 py-4">Password</th>
                        <th class="px-6 py-4">Profile</th>
                        <th class="px-6 py-4">Address</th>
                        <th class="px-6 py-4">Uptime</th>
                        <th class="px-6 py-4">Status Koneksi</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @if(in_array($group, ['package', 'odp']))
                        @forelse($customers as $groupName => $groupCustomers)
                            <tr class="bg-slate-800/80">
                                <td colspan="8" class="px-6 py-3 text-sm font-bold text-indigo-400">
                                    {{ $group === 'odp' ? 'ODP' : 'Paket' }}: {{ $groupName }} ({{ count($groupCustomers) }} Pelanggan)
                                </td>
                            </tr>
                            @foreach($groupCustomers as $customer)
                                @php $isActive = isset($activeConnections[$customer->pppoe_username]); @endphp
                                @include('pppoe._row')
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                    <svg class="w-12 h-12 mx-auto text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    <p>Tidak ada data Pelanggan PPPoE di Database.</p>
                                </td>
                            </tr>
                        @endforelse
                    @else
                        @forelse($customers as $customer)
                            @php $isActive = isset($activeConnections[$customer->pppoe_username]); @endphp
                            @include('pppoe._row')
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                <p>Tidak ada data Pelanggan PPPoE di Database.</p>
                            </td>
                        </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function confirmResetPppoeTable(form) {
        const typed = prompt('Ketik HAPUS untuk reset tabel PPPoE lokal. Pelanggan asli tetap ada, hanya username/password PPPoE dikosongkan. Data import sementara akan dihapus. Secret di MikroTik tidak akan dihapus.');
        if (typed !== 'HAPUS') {
            alert('Reset dibatalkan. Ketik HAPUS dengan huruf besar jika benar-benar ingin reset tabel PPPoE.');
            return false;
        }

        if (!confirm('Konfirmasi terakhir: tabel PPPoE lokal akan dikosongkan. Lanjutkan?')) {
            return false;
        }

        form.querySelector('input[name="confirmation"]').value = typed;
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        let seconds = 15;
        const countdownEl = document.getElementById('pppoe-refresh-countdown');
        const formControls = document.querySelectorAll('input, select, textarea');
        let userIsEditing = false;

        formControls.forEach((control) => {
            control.addEventListener('focus', () => userIsEditing = true);
            control.addEventListener('blur', () => {
                setTimeout(() => userIsEditing = false, 1500);
            });
        });

        setInterval(() => {
            if (userIsEditing) {
                return;
            }

            seconds -= 1;
            if (countdownEl) countdownEl.textContent = seconds;

            if (seconds <= 0) {
                window.location.reload();
            }
        }, 1000);
    });
</script>
@endsection
