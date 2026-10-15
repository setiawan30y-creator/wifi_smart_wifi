@extends('layouts.app')

@section('content')
@php
    $brands = ['Huawei', 'ZTE', 'FiberHome', 'VSOL', 'BDCOM', 'C-Data', 'Generic'];
    $protocols = ['telnet' => 'Telnet', 'ssh' => 'SSH', 'snmp' => 'SNMP'];
@endphp

<div class="space-y-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Manajemen OLT</h1>
            <p class="mt-1 text-slate-400">Kelola koneksi OLT, port PON, dan akses dasar perangkat fiber.</p>
        </div>
        <button type="button" onclick="openOltModal('add-olt-modal')" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Tambah OLT
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Total OLT</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $olts->count() }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Online</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $olts->where('status', 'online')->count() }}</p>
        </div>
        <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-rose-300">Offline</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $olts->where('status', 'offline')->count() }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/10 p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-base font-bold text-white">Panduan Pengisian OLT</h2>
                <p class="mt-1 text-sm text-indigo-100/80">Isi data sesuai akses manajemen OLT yang biasa dipakai teknisi.</p>
            </div>
            <div class="grid gap-3 text-xs text-slate-300 md:grid-cols-2 lg:w-3/4">
                <div class="rounded-xl border border-slate-800/70 bg-slate-950/50 p-3">
                    <p class="font-bold text-indigo-300">IP / Host</p>
                    <p class="mt-1">Alamat manajemen OLT, contoh: 192.168.10.2 atau olt-pop-a.local.</p>
                </div>
                <div class="rounded-xl border border-slate-800/70 bg-slate-950/50 p-3">
                    <p class="font-bold text-indigo-300">Protocol</p>
                    <p class="mt-1">Pilih Telnet/SSH untuk akses command. Pilih SNMP untuk monitoring status via community.</p>
                </div>
                <div class="rounded-xl border border-slate-800/70 bg-slate-950/50 p-3">
                    <p class="font-bold text-indigo-300">Port</p>
                    <p class="mt-1">Umum: Telnet 23, SSH 22, SNMP 161. Sesuaikan jika OLT memakai port custom.</p>
                </div>
                <div class="rounded-xl border border-slate-800/70 bg-slate-950/50 p-3">
                    <p class="font-bold text-indigo-300">SNMP Community</p>
                    <p class="mt-1">Diisi jika SNMP aktif di OLT, contoh: public, private, atau community khusus jaringan.</p>
                </div>
                <div class="rounded-xl border border-slate-800/70 bg-slate-950/50 p-3">
                    <p class="font-bold text-indigo-300">Jumlah Port PON</p>
                    <p class="mt-1">Jumlah port PON fisik di OLT, misalnya 4, 8, 16, atau 32.</p>
                </div>
                <div class="rounded-xl border border-slate-800/70 bg-slate-950/50 p-3">
                    <p class="font-bold text-indigo-300">Test Koneksi</p>
                    <p class="mt-1">Untuk Telnet/SSH mengecek port TCP. Untuk SNMP butuh extension PHP SNMP aktif.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="border-b border-slate-800 bg-slate-950/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-6 py-4">Nama OLT</th>
                        <th class="px-6 py-4">Brand / Model</th>
                        <th class="px-6 py-4">Host</th>
                        <th class="px-6 py-4">Protocol</th>
                        <th class="px-6 py-4">PON</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($olts as $olt)
                        <tr class="transition hover:bg-slate-800/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-700 bg-slate-800">
                                        <svg class="h-5 w-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M9 18h6" /></svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white">{{ $olt->name }}</p>
                                        <p class="text-xs text-slate-500">{{ Str::limit($olt->description, 42) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-white">{{ $olt->brand }}</p>
                                <p class="text-xs text-slate-500">{{ $olt->model ?: '-' }}</p>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">{{ $olt->host }}:{{ $olt->protocol === 'snmp' ? $olt->snmp_port : $olt->port }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-300">{{ $olt->protocol }}</span>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-200">{{ $olt->pon_ports }}</td>
                            <td class="px-6 py-4">
                                @if($olt->status === 'online')
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Online
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-rose-500/20 bg-rose-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-rose-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Offline
                                    </span>
                                @endif
                                <p class="mt-1 text-[10px] text-slate-500">{{ $olt->last_checked_at ? $olt->last_checked_at->translatedFormat('d M Y H:i') : 'Belum dites' }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('olts.show', $olt) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-indigo-500">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 19h14" /></svg>
                                        Monitor
                                    </a>
                                    <a href="{{ route('olts.test', $olt) }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-slate-700">
                                        <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        Test
                                    </a>
                                    <button type="button" onclick="openOltModal('edit-olt-modal-{{ $olt->id }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800 text-slate-300 transition hover:bg-slate-700 hover:text-white">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <form action="{{ route('olts.destroy', $olt) }}" method="POST" onsubmit="return confirm('Hapus OLT ini?')" class="inline-flex">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/10 text-rose-400 transition hover:bg-rose-500/20">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">Belum ada OLT yang ditambahkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="add-olt-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
    <div class="w-full max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-lg font-bold text-white">Tambah OLT</h3>
            <button type="button" onclick="closeOltModal('add-olt-modal')" class="text-slate-400 hover:text-white">x</button>
        </div>
        <form action="{{ route('olts.store') }}" method="POST" class="p-6">
            @csrf
            @include('olts.partials.form', ['olt' => null, 'brands' => $brands, 'protocols' => $protocols])
        </form>
    </div>
</div>

@foreach($olts as $olt)
    <div id="edit-olt-modal-{{ $olt->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Edit OLT</h3>
                <button type="button" onclick="closeOltModal('edit-olt-modal-{{ $olt->id }}')" class="text-slate-400 hover:text-white">x</button>
            </div>
            <form action="{{ route('olts.update', $olt) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')
                @include('olts.partials.form', ['olt' => $olt, 'brands' => $brands, 'protocols' => $protocols])
            </form>
        </div>
    </div>
@endforeach

<script>
    function openOltModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeOltModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
