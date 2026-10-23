@extends('layouts.app')

@section('content')
<style>
    .odp-notebook-table {
        border-collapse: collapse;
        font-size: 12px;
        line-height: 1.25;
    }
    .odp-notebook-table thead tr {
        background: color-mix(in oklab, var(--neutral-900) 96%, transparent);
    }
    .odp-notebook-table th,
    .odp-notebook-table td {
        border: 1px solid color-mix(in oklab, var(--neutral-700) 72%, transparent);
        padding: 7px 9px !important;
        vertical-align: middle;
    }
    .odp-notebook-table th {
        color: var(--neutral-300);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0;
        position: sticky;
        top: 0;
        white-space: nowrap;
        z-index: 2;
    }
    .odp-notebook-table tbody tr:nth-child(even) {
        background: color-mix(in oklab, var(--neutral-900) 24%, transparent);
    }
    .odp-notebook-table tbody tr:hover {
        background: color-mix(in oklab, var(--brand-600) 12%, transparent);
    }
    .odp-notebook-table .odp-chip {
        border-radius: 6px;
        font-size: 10px;
        line-height: 1;
        padding: 4px 6px;
    }
    .odp-notebook-table .odp-action {
        align-items: center;
        border-radius: 7px;
        display: inline-flex;
        height: 28px;
        justify-content: center;
        padding: 0 !important;
        width: 28px;
    }
    .odp-notebook-table .odp-action svg {
        height: 14px;
        width: 14px;
    }
    .odp-notebook-table .odp-photo {
        height: 28px;
        width: 28px;
    }
</style>

<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Manajemen ODP</h1>
            <p class="text-slate-400 mt-1">Pantau dan kelola titik Optical Distribution Point (ODP) jaringan fiber optik Anda.</p>
        </div>
        
        <a href="{{ route('odps.create') }}" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-650 hover:to-purple-700 text-white font-semibold px-5 py-3 rounded-xl shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-300 flex items-center gap-2 self-start sm:self-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah ODP Baru</span>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <!-- Total ODP -->
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-6 relative overflow-hidden group hover:border-slate-700/60 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Box ODP</span>
                <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-bold tracking-tight text-white font-mono">{{ $totalOdps }}</span>
                <span class="text-xs text-slate-400 block mt-1">Total titik distribusi terpasang</span>
            </div>
        </div>

        <!-- Total Port FO -->
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-6 relative overflow-hidden group hover:border-slate-700/60 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Kapasitas Port FO</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-bold tracking-tight text-emerald-400 font-mono">{{ $totalFoPorts }}</span>
                <span class="text-xs text-slate-400 block mt-1">Total port Fiber Optik</span>
            </div>
        </div>

        <!-- Total Port LAN -->
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-6 relative overflow-hidden group hover:border-slate-700/60 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Kapasitas Port LAN</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-bold tracking-tight text-purple-400 font-mono">{{ $totalLanPorts }}</span>
                <span class="text-xs text-slate-400 block mt-1">Total port LAN ethernet</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Section -->
    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between bg-slate-900/20 border border-slate-800/60 p-4 rounded-2xl backdrop-blur-md">
        <form action="{{ route('odps.index') }}" method="GET" class="w-full sm:w-96 flex items-center gap-2">
            <div class="relative w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama ODP, alamat, atau keterangan..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                <div class="absolute left-3.5 top-3 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>
            <button type="submit" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors shrink-0">Cari</button>
            @if (request('search'))
                <a href="{{ route('odps.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold px-2 shrink-0">Reset</a>
            @endif
        </form>
    </div>

    <!-- ODP Table -->
    <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-3">
        <div class="mb-3 flex items-center justify-between gap-3 px-1">
            <div>
                <h2 class="text-sm font-extrabold text-white uppercase tracking-wide">Daftar ODP</h2>
                <p class="text-[11px] text-slate-500">Format padat untuk pengecekan port, lokasi, foto, dan aksi.</p>
            </div>
            <span class="rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1 text-[11px] font-bold text-slate-400">{{ $odps->total() }} data</span>
        </div>
        <div class="overflow-x-auto rounded-xl border border-slate-800">
            <table class="odp-notebook-table w-full min-w-[1080px] text-left">
                <thead>
                    <tr>
                        <th>Nama/Kode ODP</th>
                        <th>Perangkat</th>
                        <th class="text-center">FO</th>
                        <th class="text-center">LAN</th>
                        <th>Alamat ODP</th>
                        <th>Maps</th>
                        <th>Keterangan</th>
                        <th>Foto</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-200">
                    @forelse ($odps as $odp)
                        <tr class="transition-colors">
                            <td class="font-bold text-indigo-300 tracking-wide font-mono whitespace-nowrap">
                                <div>{{ $odp->name }}</div>
                                <div class="text-[10px] font-medium text-slate-500">{{ $odp->customers->count() }} pelanggan</div>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1 max-w-[220px]">
                                    @foreach ($odp->devices as $device)
                                        <span class="odp-chip inline-flex items-center font-bold {{ $device->device_type === 'FO' ? 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20' : 'bg-purple-500/10 text-purple-300 border border-purple-500/20' }}">
                                            {{ $device->device_type }} ({{ $device->device_spec }})
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="odp-chip inline-flex items-center justify-center bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono font-bold">
                                    {{ $odp->fo_ports }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="odp-chip inline-flex items-center justify-center bg-purple-500/10 text-purple-400 border border-purple-500/20 font-mono font-bold">
                                    {{ $odp->lan_ports }}
                                </span>
                            </td>
                            <td class="text-slate-300 max-w-[240px] truncate" title="{{ $odp->address }}">{{ $odp->address }}</td>
                            <td class="whitespace-nowrap">
                                @if ($odp->maps_link)
                                    <a href="{{ $odp->maps_link }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400 hover:text-emerald-300 transition-colors">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>Buka Maps</span>
                                    </a>
                                @else
                                    <span class="text-[11px] text-slate-500 italic">Tidak ada</span>
                                @endif
                            </td>
                            <td class="text-slate-400 max-w-[220px] truncate" title="{{ $odp->description }}">{{ $odp->description ?? '-' }}</td>
                            <td>
                                <!-- Photo Thumbnails Grid -->
                                <div class="flex gap-1 items-center">
                                    @php $hasPhoto = false; @endphp
                                    @for ($i = 1; $i <= 4; $i++)
                                        @php $key = 'photo_' . $i; @endphp
                                        @if ($odp->$key)
                                            @php $hasPhoto = true; @endphp
                                            <a href="{{ asset('storage/' . $odp->$key) }}" target="_blank" class="block shrink-0 transform hover:scale-105 transition-transform" title="Buka foto {{ $i }} ukuran penuh">
                                                <img src="{{ asset('storage/' . $odp->$key) }}" class="odp-photo rounded-md object-cover border border-slate-800 hover:border-indigo-500 transition-colors">
                                            </a>
                                        @endif
                                    @endfor
                                    @if (!$hasPhoto)
                                        <span class="text-[11px] text-slate-500 italic">Belum ada</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <!-- Broadcast WA Button -->
                                @if($odp->customers->count() > 0)
                                    <button type="button" onclick="openBroadcastModal({{ $odp->id }}, '{{ addslashes($odp->name) }}', {{ json_encode($odp->customers->map(function($c){ return ['id' => $c->id, 'name' => $c->name, 'phone' => $c->phone]; })) }})" class="odp-action bg-green-500/10 text-green-400 border border-green-500/20 hover:bg-green-500 hover:text-white transition-all duration-300" title="Broadcast Pesan WA">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </button>
                                @endif

                                <!-- Edit Button -->
                                <a href="{{ route('odps.edit', $odp->id) }}" class="odp-action bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500 hover:text-white transition-all duration-300" title="Edit ODP">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                
                                <!-- Delete Button -->
                                <form action="{{ route('odps.destroy', $odp->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ODP ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="odp-action bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all duration-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-500 font-medium">Belum ada ODP terdaftar. Silakan tambah ODP baru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($odps->hasPages())
            <div class="mt-8 border-t border-slate-800/60 pt-6 flex items-center justify-between">
                <div class="text-xs text-slate-400">
                    Menampilkan {{ $odps->firstItem() ?? 0 }} - {{ $odps->lastItem() ?? 0 }} dari {{ $odps->total() }} ODP
                </div>
                <div class="flex items-center gap-1.5">
                    @if ($odps->onFirstPage())
                        <span class="px-3.5 py-2 rounded-xl bg-slate-850/40 border border-slate-800 text-slate-500 text-xs font-semibold select-none cursor-not-allowed">Sebelumnya</span>
                    @else
                        <a href="{{ $odps->previousPageUrl() }}" class="px-3.5 py-2 rounded-xl bg-slate-800/60 border border-slate-700 text-slate-200 hover:bg-slate-700 text-xs font-semibold transition-colors">Sebelumnya</a>
                    @endif

                    @if ($odps->hasMorePages())
                        <a href="{{ $odps->nextPageUrl() }}" class="px-3.5 py-2 rounded-xl bg-slate-800/60 border border-slate-700 text-slate-200 hover:bg-slate-700 text-xs font-semibold transition-colors">Berikutnya</a>
                    @else
                        <span class="px-3.5 py-2 rounded-xl bg-slate-850/40 border border-slate-800 text-slate-500 text-xs font-semibold select-none cursor-not-allowed">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>

<!-- Modal Broadcast ODP -->
<div id="modal-broadcast" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl animate-scale-up">
        <div class="px-6 py-5 border-b border-slate-800/80 flex items-center justify-between bg-slate-900/50">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                    Broadcast Peringatan Gangguan
                </h3>
                <p class="text-xs text-slate-400 mt-1">Kirim pesan WhatsApp massal ke pelanggan di <strong id="broadcast-odp-name" class="text-indigo-300"></strong></p>
            </div>
            <button onclick="closeBroadcastModal()" class="text-slate-400 hover:text-white transition-colors" id="btn-close-modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
            <!-- WA Template Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wider">Pilih Template Pesan</label>
                <select id="wa-template-select" onchange="applyWaTemplate()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-green-500 transition-colors appearance-none">
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
                <textarea id="wa-message-text" rows="6" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-green-500 transition-colors font-mono resize-none" placeholder="Tulis isi pesan gangguan di sini..."></textarea>
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
    let waTemplates = @json($waTemplates ?? []);
    let targetCustomers = [];
    let currentOdpName = '';
    let isBroadcasting = false;

    function openBroadcastModal(odpId, odpName, customers) {
        currentOdpName = odpName;
        targetCustomers = customers;
        
        document.getElementById('broadcast-odp-name').textContent = odpName;
        document.getElementById('target-count').textContent = customers.length + ' Pelanggan';
        
        const list = document.getElementById('customers-list');
        list.innerHTML = '';
        
        if (customers.length === 0) {
            list.innerHTML = '<li class="px-4 py-4 text-center text-slate-500 italic">Tidak ada pelanggan terhubung ke ODP ini.</li>';
        } else {
            customers.forEach((c, index) => {
                list.innerHTML += `
                    <li class="px-4 py-2.5 flex items-center justify-between hover:bg-slate-900/50 transition-colors" id="customer-row-${index}">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-400">${index + 1}</div>
                            <div>
                                <p class="text-slate-200 font-semibold">${c.name}</p>
                                <p class="text-[10px] text-slate-500 font-mono">${c.phone}</p>
                            </div>
                        </div>
                        <div id="customer-status-${index}" class="text-xs font-semibold px-2 py-1 rounded bg-slate-800 text-slate-400">Menunggu</div>
                    </li>
                `;
            });
        }

        // Reset states
        document.getElementById('wa-template-select').value = "";
        document.getElementById('wa-message-text').value = "";
        document.getElementById('broadcast-progress-container').classList.add('hidden');
        document.getElementById('broadcast-progress-bar').style.width = '0%';
        document.getElementById('broadcast-progress-text').textContent = '0%';
        
        const btnStart = document.getElementById('btn-start-broadcast');
        btnStart.disabled = customers.length === 0;
        btnStart.classList.toggle('opacity-50', customers.length === 0);
        btnStart.classList.toggle('cursor-not-allowed', customers.length === 0);
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
        const templateId = document.getElementById('wa-template-select').value;
        if (!templateId) {
            document.getElementById('wa-message-text').value = "";
            return;
        }
        
        const template = waTemplates.find(t => t.id == templateId);
        if (!template) return;
        
        // Let the user see the raw template with {nama_pelanggan} intact
        document.getElementById('wa-message-text').value = template.content;
    }

    async function startBroadcast() {
        if (targetCustomers.length === 0) return;
        
        const templateMessage = document.getElementById('wa-message-text').value;
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
        document.getElementById('wa-message-text').disabled = true;
        document.getElementById('wa-template-select').disabled = true;
        
        const progressContainer = document.getElementById('broadcast-progress-container');
        const progressBar = document.getElementById('broadcast-progress-bar');
        const progressText = document.getElementById('broadcast-progress-text');
        progressContainer.classList.remove('hidden');

        let successCount = 0;
        let failCount = 0;

        for (let i = 0; i < targetCustomers.length; i++) {
            // Check if user cancelled by closing modal
            if(!isBroadcasting) break;

            const c = targetCustomers[i];
            const statusBadge = document.getElementById(`customer-status-${i}`);
            const row = document.getElementById(`customer-row-${i}`);
            
            // Highlight current row
            statusBadge.className = 'text-[10px] font-bold px-2 py-1 rounded bg-amber-500/20 text-amber-400 uppercase tracking-wide flex items-center gap-1';
            statusBadge.innerHTML = `<svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mengirim`;
            row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Personalize message
            let msg = templateMessage.replace(/{nama_pelanggan}/g, c.name);
            msg = msg.replace(/{nama_odp}/g, currentOdpName);
            
            try {
                const response = await fetch("{{ route('wa.send', [], false) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        phone: c.phone,
                        message: msg
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    statusBadge.className = 'text-[10px] font-bold px-2 py-1 rounded bg-emerald-500/20 text-emerald-400 uppercase tracking-wide';
                    statusBadge.textContent = 'Berhasil';
                    successCount++;
                } else {
                    statusBadge.className = 'text-[10px] font-bold px-2 py-1 rounded bg-rose-500/20 text-rose-400 uppercase tracking-wide';
                    statusBadge.textContent = 'Gagal';
                    statusBadge.title = data.message || 'Unknown error';
                    failCount++;
                }
            } catch (err) {
                statusBadge.className = 'text-[10px] font-bold px-2 py-1 rounded bg-rose-500/20 text-rose-400 uppercase tracking-wide';
                statusBadge.textContent = 'Error Jaringan';
                failCount++;
            }

            // Update Progress
            let pct = Math.round(((i + 1) / targetCustomers.length) * 100);
            progressBar.style.width = pct + '%';
            progressText.textContent = pct + '%';

            // Respect Delay Configured
            const delaySeconds = parseInt(document.getElementById('wa-delay-input').value) || 2;
            const delayMs = delaySeconds * 1000;
            if (i < targetCustomers.length - 1) { // No need to delay after the last message
                await new Promise(r => setTimeout(r, delayMs));
            }
        }

        isBroadcasting = false;
        
        // Re-enable UI
        document.getElementById('wa-message-text').disabled = false;
        document.getElementById('wa-template-select').disabled = false;
        
        const btnStart = document.getElementById('btn-start-broadcast');
        btnStart.disabled = false;
        btnStart.classList.remove('opacity-50', 'cursor-wait');
        btnStart.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg><span>Selesai (${successCount} Terkirim)</span>`;
        btnStart.classList.remove('bg-green-600', 'hover:bg-green-500');
        btnStart.classList.add('bg-slate-700', 'hover:bg-slate-600', 'cursor-not-allowed');
        
        alert(`Selesai! Berhasil terkirim: ${successCount}, Gagal: ${failCount}`);
    }
</script>
@endsection
