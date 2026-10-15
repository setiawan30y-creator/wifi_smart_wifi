@extends('layouts.app')

@section('content')
@php
    $onuStatuses = [
        'online' => 'Online',
        'offline' => 'Offline',
        'los' => 'LOS',
        'unknown' => 'Unknown',
    ];
    $pppoeStatuses = [
        'active' => 'Aktif',
        'offline' => 'Offline',
        'disabled' => 'Disabled',
        'unknown' => 'Unknown',
    ];
    $badge = [
        'online' => 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
        'active' => 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
        'offline' => 'border-slate-600 bg-slate-800 text-slate-300',
        'disabled' => 'border-amber-500/20 bg-amber-500/10 text-amber-300',
        'los' => 'border-rose-500/20 bg-rose-500/10 text-rose-300',
        'unknown' => 'border-slate-700 bg-slate-900 text-slate-400',
    ];
@endphp

<div class="space-y-8">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <a href="{{ route('olts.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Kembali ke Manajemen OLT
            </a>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Monitoring ONU / PPPoE</h1>
            <p class="mt-1 text-slate-400">{{ $olt->name }} - {{ $olt->brand }} {{ $olt->model ?: '' }} - {{ $olt->host }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('olts.sync-customers', $olt) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-700">
                    <svg class="h-4 w-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19a9 9 0 0014-3M19 5A9 9 0 005 8" /></svg>
                    Sinkron Pelanggan
                </button>
            </form>
            <form action="{{ route('olts.sync-pppoe', $olt) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    Update PPPoE
                </button>
            </form>
            <button type="button" onclick="openOltModal('add-onu-modal')" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Tambah ONU
            </button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-5">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Total ONU</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">ONU Online</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['online'] }}</p>
        </div>
        <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-rose-300">LOS</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['los'] }}</p>
        </div>
        <div class="rounded-2xl border border-sky-500/20 bg-sky-500/10 p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-sky-300">PPPoE Aktif</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['pppoe_active'] }}</p>
        </div>
        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-amber-300">PPPoE Off</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['pppoe_offline'] }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/10 p-5">
        <div class="grid gap-4 lg:grid-cols-[1fr,2fr]">
            <div>
                <h2 class="text-base font-bold text-white">Cara pakai monitoring</h2>
                <p class="mt-1 text-sm text-indigo-100/80">Gunakan sinkron pelanggan untuk membuat daftar ONU dari username PPPoE, lalu update PPPoE untuk membaca sesi aktif dari MikroTik.</p>
            </div>
            <div class="grid gap-3 text-xs text-slate-300 md:grid-cols-3">
                <div class="rounded-xl border border-slate-800/70 bg-slate-950/50 p-3">
                    <p class="font-bold text-indigo-300">PON / ONU ID</p>
                    <p class="mt-1">Isi lokasi teknis, contoh: GPON 1/1/3 atau gpon-onu_1/1/3:12.</p>
                </div>
                <div class="rounded-xl border border-slate-800/70 bg-slate-950/50 p-3">
                    <p class="font-bold text-indigo-300">RX / TX Power</p>
                    <p class="mt-1">Nilai redaman optik dalam dBm. Untuk auto pull dibutuhkan driver brand OLT/SNMP.</p>
                </div>
                <div class="rounded-xl border border-slate-800/70 bg-slate-950/50 p-3">
                    <p class="font-bold text-indigo-300">PPPoE Status</p>
                    <p class="mt-1">Diambil dari MikroTik `/ppp active`. Aktif berarti username sedang login.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] text-left text-sm">
                <thead class="border-b border-slate-800 bg-slate-950/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-6 py-4">ONU</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">PON</th>
                        <th class="px-6 py-4">PPPoE</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Optik</th>
                        <th class="px-6 py-4">Polling</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($olt->onus as $onu)
                        <tr class="transition hover:bg-slate-800/50">
                            <td class="px-6 py-4">
                                <p class="font-bold text-white">{{ $onu->name ?: '-' }}</p>
                                <p class="font-mono text-xs text-slate-500">{{ $onu->serial_number ?: 'SN belum diisi' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-white">{{ $onu->customer?->name ?: '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $onu->customer?->package?->name ?: '' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-mono text-xs text-slate-200">{{ $onu->pon_port ?: '-' }}</p>
                                <p class="font-mono text-xs text-slate-500">{{ $onu->onu_id ?: '-' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-mono text-xs text-slate-200">{{ $onu->pppoe_username ?: '-' }}</p>
                                <span class="mt-1 inline-flex rounded-lg border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $badge[$onu->pppoe_status] ?? $badge['unknown'] }}">
                                    {{ $pppoeStatuses[$onu->pppoe_status] ?? $onu->pppoe_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-lg border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $badge[$onu->status] ?? $badge['unknown'] }}">
                                    {{ $onuStatuses[$onu->status] ?? $onu->status }}
                                </span>
                                <p class="mt-1 text-xs text-slate-500">{{ $onu->uptime ? 'Uptime '.$onu->uptime : '' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-mono text-xs text-slate-200">RX: {{ $onu->rx_power !== null ? $onu->rx_power.' dBm' : '-' }}</p>
                                <p class="font-mono text-xs text-slate-500">TX: {{ $onu->tx_power !== null ? $onu->tx_power.' dBm' : '-' }}</p>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <p class="text-slate-300">{{ $onu->last_polled_at ? $onu->last_polled_at->translatedFormat('d M Y H:i') : 'Belum polling' }}</p>
                                <p class="text-slate-500">{{ $onu->last_seen_at ? 'Last seen '.$onu->last_seen_at->translatedFormat('d M Y H:i') : '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="openOltModal('edit-onu-modal-{{ $onu->id }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800 text-slate-300 transition hover:bg-slate-700 hover:text-white">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <form action="{{ route('olts.onus.destroy', [$olt, $onu]) }}" method="POST" onsubmit="return confirm('Hapus data ONU ini?')" class="inline-flex">
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
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                Belum ada ONU. Klik Sinkron Pelanggan atau Tambah ONU manual.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('olts.partials.onu-form', ['modalId' => 'add-onu-modal', 'action' => route('olts.onus.store', $olt), 'method' => null, 'onu' => null, 'customers' => $customers, 'onuStatuses' => $onuStatuses, 'pppoeStatuses' => $pppoeStatuses])

@foreach($olt->onus as $onu)
    @include('olts.partials.onu-form', ['modalId' => 'edit-onu-modal-'.$onu->id, 'action' => route('olts.onus.update', [$olt, $onu]), 'method' => 'PUT', 'onu' => $onu, 'customers' => $customers, 'onuStatuses' => $onuStatuses, 'pppoeStatuses' => $pppoeStatuses])
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
