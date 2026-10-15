@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Stok Perangkat</h1>
            <p class="text-slate-400 mt-1">Kelola ketersediaan perangkat router (ONT FO / Router LAN AP) untuk dipasang di rumah pelanggan.</p>
        </div>
        <div>
            <button onclick="document.getElementById('modal-add-device').classList.remove('hidden')" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-white shadow-lg shadow-indigo-600/20 transition-all duration-300 transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Perangkat</span>
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider">Total Tipe Perangkat</span>
                <h3 class="text-2xl font-bold text-slate-100 mt-1">{{ $deviceStocks->count() }} Tipe</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
        </div>

        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider">Total Kuantitas Stok</span>
                <h3 class="text-2xl font-bold text-emerald-400 mt-1">{{ $deviceStocks->sum('stock') }} Unit</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
        </div>

        @php
            $lowStockCount = $deviceStocks->where('stock', '<=', 3)->count();
        @endphp
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider">Perangkat Hampir Habis</span>
                <h3 class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-amber-500 animate-pulse' : 'text-slate-100' }} mt-1">{{ $lowStockCount }} Tipe</h3>
            </div>
            <div class="w-12 h-12 rounded-xl {{ $lowStockCount > 0 ? 'bg-amber-500/10 border border-amber-500/20 text-amber-500' : 'bg-slate-800/60 border border-slate-700/60 text-slate-400' }} flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Devices Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($deviceStocks as $device)
            @php
                $isLowStock = $device->stock <= 3;
            @endphp
            <div class="bg-slate-900/30 border border-slate-800/80 hover:border-slate-700/80 backdrop-blur-md rounded-2xl p-6 flex flex-col justify-between transition-all duration-300 group hover:shadow-lg hover:shadow-slate-950/20 relative overflow-hidden">
                <div>
                    <!-- Header: Brand & Type Badge -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-1 rounded-lg uppercase tracking-wider font-mono">{{ $device->brand }}</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ $device->type === 'FO' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-fuchsia-500/10 text-fuchsia-400 border border-fuchsia-500/20' }}">
                            {{ $device->type === 'FO' ? 'Fiber Optic' : 'LAN RJ45' }}
                        </span>
                    </div>

                    <!-- Device Product Image -->
                    <div class="mb-4 aspect-[16/10] w-full rounded-xl overflow-hidden bg-slate-950/40 border border-slate-800/80 relative flex items-center justify-center group-hover:border-indigo-500/30 transition-all duration-300">
                        @if($device->image)
                            <img src="{{ asset('storage/' . $device->image) }}" alt="{{ $device->brand }} - {{ $device->name }}" class="w-full h-full object-cover opacity-85 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500">
                        @elseif($device->type === 'FO')
                            <img src="{{ asset('images/device_fo.png') }}" alt="GPON ONT Fiber Optic" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500">
                        @else
                            <img src="{{ asset('images/device_lan.png') }}" alt="Wireless LAN Router" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500">
                        @endif
                    </div>

                    <!-- Device Name -->
                    <h4 class="text-xl font-bold text-slate-100 group-hover:text-white transition-colors">{{ $device->name }}</h4>
                    <p class="text-xs text-slate-400 mt-1.5 min-h-[32px] line-clamp-2">{{ $device->description ?? 'Tidak ada deskripsi perangkat.' }}</p>

                    <!-- Stock Info -->
                    <div class="mt-6 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold tracking-tight {{ $isLowStock ? 'text-rose-500' : 'text-slate-100' }}">{{ $device->stock }}</span>
                        <span class="text-xs font-medium text-slate-450 uppercase">Unit Tersisa</span>
                    </div>

                    <!-- Low Stock Alert Warning -->
                    @if($isLowStock)
                        <div class="mt-2.5 flex items-center gap-1.5 text-xs text-amber-500 font-semibold bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded-lg w-max animate-pulse">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>Stok menipis, segera restock!</span>
                        </div>
                    @endif
                </div>

                <!-- Footer: Pricing & Actions -->
                <div class="mt-8 pt-4 border-t border-slate-800/60 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-slate-450 uppercase font-semibold block">Harga Referensi</span>
                        <span class="text-sm font-bold text-slate-200">Rp {{ number_format($device->unit_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Edit Button -->
                        <button onclick="document.getElementById('modal-edit-{{ $device->id }}').classList.remove('hidden')" class="w-9 h-9 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 flex items-center justify-center text-slate-300 hover:text-white transition-colors" title="Edit Perangkat">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <!-- Delete Button -->
                        <button onclick="document.getElementById('modal-delete-{{ $device->id }}').classList.remove('hidden')" class="w-9 h-9 rounded-xl bg-rose-500/10 hover:bg-rose-500 border border-rose-500/20 flex items-center justify-center text-rose-450 hover:text-white transition-colors" title="Hapus Perangkat">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL: Edit Device -->
            <div id="modal-edit-{{ $device->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 overflow-y-auto">
                <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-scale-up">
                    <div class="px-6 py-5 border-b border-slate-800/80 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">Edit Perangkat: {{ $device->name }}</h3>
                        <button onclick="document.getElementById('modal-edit-{{ $device->id }}').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <form action="{{ route('device-stocks.update', $device->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-350 mb-1.5 uppercase">Merk Perangkat</label>
                                <input type="text" name="brand" value="{{ $device->brand }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: ZTE, Tenda">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-350 mb-1.5 uppercase">Nama Model</label>
                                <input type="text" name="name" value="{{ $device->name }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: F640, N301">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-350 mb-1.5 uppercase">Jenis Jalur</label>
                                <select name="type" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                    <option value="FO" {{ $device->type === 'FO' ? 'selected' : '' }}>Fiber Optic (FO)</option>
                                    <option value="LAN" {{ $device->type === 'LAN' ? 'selected' : '' }}>LAN RJ45</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-350 mb-1.5 uppercase">Sisa Stok Manual</label>
                                <input type="number" name="stock" value="{{ $device->stock }}" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Jumlah unit">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-350 mb-1.5 uppercase">Harga Satuan Referensi (Rp)</label>
                            <input type="number" name="unit_price" value="{{ $device->unit_price }}" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: 150000">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-350 mb-1.5 uppercase">Foto Produk (Kustom)</label>
                            <input type="file" name="image" accept="image/*" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 file:hover:bg-slate-700 transition-all cursor-pointer">
                            @if($device->image)
                                <div class="mt-2 text-[10px] text-indigo-400 font-semibold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Sudah memiliki foto kustom. Unggah baru untuk mengganti.</span>
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-350 mb-1.5 uppercase">Deskripsi Spesifikasi</label>
                            <textarea name="description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Detail spesifikasi perangkat...">{{ $device->description }}</textarea>
                        </div>

                        <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                            <button type="button" onclick="document.getElementById('modal-edit-{{ $device->id }}').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-sm font-semibold">Batal</button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all text-sm">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Delete Device -->
            <div id="modal-delete-{{ $device->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
                <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                    <div class="p-6 text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center mx-auto">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Konfirmasi Hapus</h3>
                            <p class="text-sm text-slate-400 mt-2">Apakah Anda yakin ingin menghapus stok perangkat <strong>{{ $device->name }}</strong>? Data yang dihapus tidak dapat dipulihkan.</p>
                        </div>
                        <div class="flex justify-center gap-3 pt-2">
                            <button onclick="document.getElementById('modal-delete-{{ $device->id }}').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-sm font-semibold">Batal</button>
                            <form action="{{ route('device-stocks.destroy', $device->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold transition-colors">Hapus Sekarang</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900/20 border border-dashed border-slate-800 rounded-3xl py-16 text-center">
                <svg class="w-12 h-12 text-slate-650 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-lg font-bold text-slate-300">Belum Ada Stok Perangkat</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Daftar stok router dan media converter masih kosong. Silakan tambah perangkat baru untuk memulai.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- MODAL: Add Device -->
<div id="modal-add-device" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-scale-up">
        <div class="px-6 py-5 border-b border-slate-800/80 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white">Tambah Perangkat Baru</h3>
            <button onclick="document.getElementById('modal-add-device').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form action="{{ route('device-stocks.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Merk Perangkat</label>
                    <input type="text" name="brand" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: ZTE, Tenda">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Nama Model</label>
                    <input type="text" name="name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: F640, N301">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Jenis Jalur</label>
                    <select name="type" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="FO">Fiber Optic (FO)</option>
                        <option value="LAN">LAN RJ45</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Stok Awal (Unit)</label>
                    <input type="number" name="stock" value="0" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="0">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Harga Satuan Referensi (Rp)</label>
                <input type="number" name="unit_price" value="0" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: 150000">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Foto Produk (Opsional)</label>
                <input type="file" name="image" accept="image/*" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 file:hover:bg-slate-700 transition-all cursor-pointer">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Deskripsi Spesifikasi</label>
                <textarea name="description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Detail spesifikasi perangkat..."></textarea>
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-add-device').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-sm font-semibold">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all text-sm">Tambah Perangkat</button>
            </div>
        </form>
    </div>
</div>
@endsection
