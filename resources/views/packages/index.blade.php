@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Paket Layanan</h1>
            <p class="text-slate-400 mt-1">Kelola daftar pilihan paket internet atau iuran bulanan untuk pelanggan Anda.</p>
        </div>
        <button onclick="document.getElementById('add-modal').classList.remove('hidden'); document.getElementById('add-modal').classList.add('flex')" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-650 hover:to-purple-700 text-white font-semibold px-5 py-3 rounded-xl shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-300 flex items-center gap-2 self-start sm:self-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Paket Baru</span>
        </button>
    </div>

    <!-- Packages Table -->
    <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl p-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs uppercase font-bold">
                        <th class="py-4 px-4">Nama Paket</th>
                        <th class="py-4 px-4">Kecepatan</th>
                        <th class="py-4 px-4">Harga Bulanan</th>
                        <th class="py-4 px-4 text-center">Pengguna Aktif</th>
                        <th class="py-4 px-4">Deskripsi</th>
                        <th class="py-4 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 text-sm text-slate-200">
                    @forelse ($packages as $package)
                        <tr class="hover:bg-slate-800/10 transition-colors">
                            <td class="py-4 px-4 font-semibold text-white">{{ $package->name }}</td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                    {{ $package->speed ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="py-4 px-4 font-bold text-indigo-400">Rp {{ number_format($package->price, 0, ',', '.') }}</td>
                            <td class="py-4 px-4 text-center font-bold">{{ $package->customers_count }} Pelanggan</td>
                            <td class="py-4 px-4 text-slate-400 max-w-xs truncate" title="{{ $package->description }}">{{ $package->description ?? '-' }}</td>
                            <td class="py-4 px-4 text-right space-x-2 whitespace-nowrap">
                                <!-- Edit Button -->
                                <button onclick="document.getElementById('edit-modal-{{ $package->id }}').classList.remove('hidden'); document.getElementById('edit-modal-{{ $package->id }}').classList.add('flex')" class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500 hover:text-white transition-all duration-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                
                                <!-- Delete Button / Form -->
                                <form action="{{ route('packages.destroy', $package->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket ini? Pelanggan yang terikat mungkin akan terpengaruh.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all duration-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- EDIT MODAL FOR THIS PACKAGE -->
                        <div id="edit-modal-{{ $package->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
                            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full relative shadow-2xl shadow-slate-950">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-bold text-white">Edit Paket</h3>
                                    <button onclick="document.getElementById('edit-modal-{{ $package->id }}').classList.add('hidden'); document.getElementById('edit-modal-{{ $package->id }}').classList.remove('flex')" class="text-slate-400 hover:text-white">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <form action="{{ route('packages.update', $package->id) }}" method="POST" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Nama Paket</label>
                                        <input type="text" name="name" value="{{ $package->name }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Kecepatan Internet</label>
                                        <input type="text" name="speed" value="{{ $package->speed }}" placeholder="Contoh: 10 Mbps (opsional)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Harga Bulanan (Rp)</label>
                                        <input type="number" name="price" value="{{ $package->price }}" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Deskripsi</label>
                                        <textarea name="description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">{{ $package->description }}</textarea>
                                    </div>
                                    <div class="pt-2">
                                        <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold py-3 rounded-xl hover:from-indigo-650 hover:to-purple-700 shadow-lg shadow-indigo-600/10 transition-all duration-300">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 font-medium">Belum ada paket layanan. Silakan tambah paket baru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div id="add-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full relative shadow-2xl shadow-slate-950 animate-fade-in">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-white">Tambah Paket Baru</h3>
            <button onclick="document.getElementById('add-modal').classList.add('hidden'); document.getElementById('add-modal').classList.remove('flex')" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form action="{{ route('packages.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Nama Paket</label>
                <input type="text" name="name" placeholder="Contoh: Paket Basic" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Kecepatan Internet</label>
                <input type="text" name="speed" placeholder="Contoh: 10 Mbps (opsional)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Harga Bulanan (Rp)</label>
                <input type="number" name="price" placeholder="Contoh: 150000" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Deskripsi</label>
                <textarea name="description" rows="3" placeholder="Keterangan singkat paket..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors"></textarea>
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold py-3 rounded-xl hover:from-indigo-650 hover:to-purple-700 shadow-lg shadow-indigo-600/10 transition-all duration-300">Simpan Paket</button>
            </div>
        </form>
    </div>
</div>
@endsection
