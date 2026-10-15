@extends('layouts.app')

@section('content')
@php
    $statusClasses = [
        'new' => 'bg-sky-500/10 text-sky-300 border-sky-500/20',
        'survey' => 'bg-amber-500/10 text-amber-300 border-amber-500/20',
        'scheduled' => 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20',
        'installed' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
        'cancelled' => 'bg-rose-500/10 text-rose-300 border-rose-500/20',
    ];
@endphp

<div class="space-y-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Calon Pelanggan Mau Pasang</h1>
            <p class="mt-1 text-slate-400">Antrian pemasangan dari pendaftaran, survey, jadwal teknisi, sampai jadi pelanggan aktif.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" /></svg>
                Pelanggan Aktif
            </a>
            <button type="button" onclick="openModal('add-request-modal')" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Tambah Calon Pasang
            </button>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach($statuses as $key => $label)
            <a href="{{ route('installation-requests.index', ['status' => $key]) }}" class="rounded-2xl border border-slate-800/70 bg-slate-900/40 p-4 transition hover:border-indigo-500/40 hover:bg-slate-900/70">
                <div class="text-xs font-bold uppercase tracking-widest text-slate-500">{{ $label }}</div>
                <div class="mt-2 text-2xl font-extrabold text-white">{{ $statusCounts[$key] ?? 0 }}</div>
            </a>
        @endforeach
    </div>

    <div class="rounded-2xl border border-slate-800/70 bg-slate-900/30 p-4">
        <form action="{{ route('installation-requests.index') }}" method="GET" class="grid gap-3 md:grid-cols-[1fr_220px_auto_auto]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, telepon, alamat..." class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-indigo-500">
            <select name="status" class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-indigo-500">
                <option value="">Semua status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">Filter</button>
            @if(request('search') || request('status'))
                <a href="{{ route('installation-requests.index') }}" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-indigo-300 transition hover:text-indigo-200">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-800/70 bg-slate-900/40">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left">
                <thead>
                    <tr class="border-b border-slate-800 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-4">Calon Pelanggan</th>
                        <th class="px-5 py-4">Alamat</th>
                        <th class="px-5 py-4">Paket / ODP</th>
                        <th class="px-5 py-4">Jadwal</th>
                        <th class="px-5 py-4">Teknisi</th>
                        <th class="px-5 py-4 text-center">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-sm">
                    @forelse($requests as $item)
                        <tr class="text-slate-200 transition hover:bg-slate-800/20">
                            <td class="px-5 py-4">
                                <div class="font-bold text-white">{{ $item->name }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $item->phone }}</div>
                                <div class="text-xs text-slate-500">{{ $item->email ?: 'no email' }}</div>
                            </td>
                            <td class="max-w-xs px-5 py-4">
                                <div class="truncate text-slate-300" title="{{ $item->address }}">{{ $item->address }}</div>
                                @if($item->latitude && $item->longitude)
                                    <a href="https://maps.google.com/?q={{ $item->latitude }},{{ $item->longitude }}" target="_blank" class="mt-1 inline-flex text-xs font-semibold text-indigo-300 hover:text-indigo-200">Buka Maps</a>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-white">{{ $item->package?->name ?? 'Belum pilih paket' }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $item->odp?->name ?? 'ODP belum ditentukan' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-200">{{ $item->scheduled_at ? $item->scheduled_at->translatedFormat('d M Y H:i') : 'Belum dijadwalkan' }}</div>
                                <div class="mt-1 text-xs text-slate-500">Preferensi: {{ $item->preferred_installation_date ? $item->preferred_installation_date->translatedFormat('d M Y') : '-' }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-300">{{ $item->assignee?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses[$item->status] ?? $statusClasses['new'] }}">
                                    {{ $statuses[$item->status] ?? ucfirst($item->status) }}
                                </span>
                                @if($item->customer)
                                    <a href="{{ route('customers.index', ['search' => $item->customer->phone]) }}" class="mt-2 block text-xs font-semibold text-emerald-300 hover:text-emerald-200">Sudah jadi pelanggan</a>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" onclick="openModal('status-modal-{{ $item->id }}')" class="rounded-lg border border-amber-500/20 bg-amber-500/10 p-2 text-amber-300 transition hover:bg-amber-500 hover:text-white" title="Update status">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-8 4h5M5 3h14a2 2 0 012 2v14l-4-3H5a2 2 0 01-2-2V5a2 2 0 012-2z" /></svg>
                                    </button>
                                    <button type="button" onclick="openModal('edit-modal-{{ $item->id }}')" class="rounded-lg border border-blue-500/20 bg-blue-500/10 p-2 text-blue-300 transition hover:bg-blue-500 hover:text-white" title="Edit">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" /></svg>
                                    </button>
                                    @if(!$item->customer_id)
                                        <button type="button" onclick="openModal('convert-modal-{{ $item->id }}')" class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-2 text-emerald-300 transition hover:bg-emerald-500 hover:text-white" title="Jadikan pelanggan">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                        <form action="{{ route('installation-requests.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus calon pelanggan ini?')" class="inline-flex">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-500/20 bg-rose-500/10 p-2 text-rose-300 transition hover:bg-rose-500 hover:text-white" title="Hapus">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">Belum ada calon pelanggan mau pasang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-800 px-5 py-4">
            {{ $requests->links() }}
        </div>
    </div>
</div>

<div id="add-request-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
    <div class="w-full max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-lg font-bold text-white">Tambah Calon Pelanggan</h3>
            <button type="button" onclick="closeModal('add-request-modal')" class="text-slate-400 hover:text-white">x</button>
        </div>
        <form action="{{ route('installation-requests.store') }}" method="POST" class="p-6">
            @csrf
            @include('installation_requests.partials.form', ['item' => null])
        </form>
    </div>
</div>

@foreach($requests as $item)
    <div id="edit-modal-{{ $item->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Edit Calon Pelanggan</h3>
                <button type="button" onclick="closeModal('edit-modal-{{ $item->id }}')" class="text-slate-400 hover:text-white">x</button>
            </div>
            <form action="{{ route('installation-requests.update', $item) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')
                @include('installation_requests.partials.form', ['item' => $item])
            </form>
        </div>
    </div>

    <div id="status-modal-{{ $item->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-xl rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Update Status Pemasangan</h3>
                <button type="button" onclick="closeModal('status-modal-{{ $item->id }}')" class="text-slate-400 hover:text-white">x</button>
            </div>
            <form action="{{ route('installation-requests.status', $item) }}" method="POST" class="space-y-4 p-6">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Status</span>
                        <select name="status" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Jadwal</span>
                        <input type="datetime-local" name="scheduled_at" value="{{ $item->scheduled_at?->format('Y-m-d\TH:i') }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                    </label>
                </div>
                <label class="space-y-1 block">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Teknisi</span>
                    <select name="assigned_to" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                        <option value="">Belum ditugaskan</option>
                        @foreach($technicians as $technician)
                            <option value="{{ $technician->id }}" @selected($item->assigned_to === $technician->id)>{{ $technician->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 block">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Catatan Survey</span>
                    <textarea name="survey_notes" rows="3" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">{{ old('survey_notes', $item->survey_notes) }}</textarea>
                </label>
                <label class="space-y-1 block">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Alasan Batal</span>
                    <textarea name="cancel_reason" rows="2" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">{{ old('cancel_reason', $item->cancel_reason) }}</textarea>
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('status-modal-{{ $item->id }}')" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Batal</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Simpan Status</button>
                </div>
            </form>
        </div>
    </div>

    <div id="convert-modal-{{ $item->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Jadikan Pelanggan Aktif</h3>
                <button type="button" onclick="closeModal('convert-modal-{{ $item->id }}')" class="text-slate-400 hover:text-white">x</button>
            </div>
            <form action="{{ route('installation-requests.convert', $item) }}" method="POST" class="space-y-4 p-6">
                @csrf
                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    Data nama, telepon, alamat, dan titik maps akan dibawa dari antrian pemasangan.
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Paket</span>
                        <select name="package_id" required class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                            <option value="">Pilih paket</option>
                            @foreach($packages as $package)
                                <option value="{{ $package->id }}" @selected($item->package_id === $package->id)>{{ $package->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">ODP</span>
                        <select name="odp_id" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                            <option value="">Belum pilih ODP</option>
                            @foreach($odps as $odp)
                                <option value="{{ $odp->id }}" @selected($item->odp_id === $odp->id)>{{ $odp->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal Pasang</span>
                        <input type="date" name="tanggal_pasang" required value="{{ now()->toDateString() }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal Tagih</span>
                        <input type="number" name="billing_day" min="1" max="28" value="5" required class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">PPPoE Username</span>
                        <input type="text" name="pppoe_username" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">PPPoE Password</span>
                        <input type="text" name="pppoe_password" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Rumah</span>
                        <select name="status_rumah" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                            <option value="milik_sendiri">Milik Sendiri</option>
                            <option value="ngontrak">Ngontrak</option>
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pemilik Rumah</span>
                        <input type="text" name="pemilik_rumah" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Jalur</span>
                        <select name="jalur" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                            <option value="">Pilih jalur</option>
                            <option value="FO">FO</option>
                            <option value="LAN">LAN</option>
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Perangkat</span>
                        <input type="text" name="perangkat" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('convert-modal-{{ $item->id }}')" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Batal</button>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">Jadikan Pelanggan</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
