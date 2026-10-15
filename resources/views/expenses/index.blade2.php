@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Pengeluaran Kas</h1>
            <p class="text-slate-400 mt-1">Catat semua arus kas keluar untuk operasional, perbaikan, gaji, hingga pembelian perangkat internet.</p>
        </div>
        <div>
            <button onclick="document.getElementById('modal-add-expense').classList.remove('hidden')" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-white shadow-lg shadow-indigo-600/20 transition-all duration-300 transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Catat Pengeluaran</span>
            </button>
        </div>
    </div>

    <!-- Expenses Table Card -->
    <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-800/60 flex items-center justify-between flex-wrap gap-4">
            <h3 class="font-bold text-slate-200">Riwayat Pengeluaran Kas</h3>
            <span class="text-xs text-slate-400 font-semibold bg-slate-950/40 border border-slate-850 px-3 py-1.5 rounded-lg">Total Pengeluaran: Rp {{ number_format($expenses->sum('amount'), 0, ',', '.') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800/60 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-950/20">
                        <th class="py-4 px-6">Tanggal</th>
                        <th class="py-4 px-6">Deskripsi & Keperluan</th>
                        <th class="py-4 px-6">Kategori</th>
                        <th class="py-4 px-6">Kabel/Perangkat</th>
                        <th class="py-4 px-6 text-right">Nominal</th>
                        <th class="py-4 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-850">
                    @forelse($expenses as $expense)
                        <tr class="hover:bg-slate-850/20 transition-colors">
                            <!-- Date -->
                            <td class="py-4 px-6 text-sm text-slate-300 font-mono whitespace-nowrap">
                                {{ $expense->date->translatedFormat('d F Y') }}
                            </td>
                            
                            <!-- Title & Description -->
                            <td class="py-4 px-6">
                                <div class="text-sm font-semibold text-slate-200">{{ $expense->title }}</div>
                                @if($expense->description)
                                    <div class="text-xs text-slate-450 mt-0.5 line-clamp-1">{{ $expense->description }}</div>
                                @endif
                            </td>

                            <!-- Category Badge -->
                            <td class="py-4 px-6">
                                @php
                                    $catColor = match($expense->category) {
                                        'Pembelian Perangkat' => 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20',
                                        'Operasional' => 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20',
                                        'Gaji/Honor' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                                        'Perbaikan' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                                        'ISP' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
                                        'Listrik' => 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20',
                                        'Perlengkapan' => 'bg-purple-500/10 text-purple-400 border border-purple-500/20',
                                        default => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $catColor }}">
                                    {{ $expense->category }}
                                </span>
                            </td>

                            <!-- Device integration info -->
                            <td class="py-4 px-6">
                                @if($expense->deviceStock)
                                    <div class="text-xs font-semibold text-slate-250">{{ $expense->deviceStock->name }}</div>
                                    <div class="text-[10px] text-slate-450 font-mono mt-0.5">{{ $expense->quantity }} Unit @ Rp {{ number_format($expense->amount / $expense->quantity, 0, ',', '.') }}</div>
                                @else
                                    <span class="text-xs text-slate-500">-</span>
                                @endif
                            </td>

                            <!-- Amount -->
                            <td class="py-4 px-6 text-right text-sm font-bold text-rose-450">
                                - Rp {{ number_format($expense->amount, 0, ',', '.') }}
                            </td>

                            <!-- Action Buttons -->
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="document.getElementById('modal-edit-expense-{{ $expense->id }}').classList.remove('hidden'); updateEditDevicePreview('{{ $expense->id }}')" class="w-8 h-8 rounded-lg bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 flex items-center justify-center text-slate-300 hover:text-white transition-colors" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button onclick="document.getElementById('modal-delete-expense-{{ $expense->id }}').classList.remove('hidden')" class="w-8 h-8 rounded-lg bg-rose-500/10 hover:bg-rose-500 border border-rose-500/20 flex items-center justify-center text-rose-450 hover:text-white transition-colors" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- MODAL: Edit Expense -->
                        <div id="modal-edit-expense-{{ $expense->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 overflow-y-auto">
                            <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                                <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                                    <h3 class="text-base font-bold text-white">Edit Catatan Pengeluaran</h3>
                                    <button onclick="document.getElementById('modal-edit-expense-{{ $expense->id }}').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <form action="{{ route('expenses.update', $expense->id) }}" method="POST" class="p-5 space-y-3.5">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Keperluan / Keterangan Singkat</label>
                                        <input type="text" name="title" value="{{ $expense->title }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: Pembelian Ulang ZTE F640">
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Kategori</label>
                                            <select name="category" id="edit-category-{{ $expense->id }}" onchange="handleCategoryChange(this, 'edit-device-fields-{{ $expense->id }}', 'edit-amount-{{ $expense->id }}', 'edit-qty-{{ $expense->id }}', 'edit-price-{{ $expense->id }}'); updateEditDevicePreview('{{ $expense->id }}')" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                <option value="Pembelian Perangkat" {{ $expense->category === 'Pembelian Perangkat' ? 'selected' : '' }}>Pembelian Perangkat</option>
                                                <option value="Operasional" {{ $expense->category === 'Operasional' ? 'selected' : '' }}>Operasional</option>
                                                <option value="Gaji/Honor" {{ $expense->category === 'Gaji/Honor' ? 'selected' : '' }}>Gaji/Honor</option>
                                                <option value="Perbaikan" {{ $expense->category === 'Perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                                                <option value="ISP" {{ $expense->category === 'ISP' ? 'selected' : '' }}>ISP</option>
                                                <option value="Listrik" {{ $expense->category === 'Listrik' ? 'selected' : '' }}>Listrik</option>
                                                <option value="Perlengkapan" {{ $expense->category === 'Perlengkapan' ? 'selected' : '' }}>Perlengkapan</option>
                                                <option value="Lain-lain" {{ $expense->category === 'Lain-lain' ? 'selected' : '' }}>Lain-lain</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Tanggal</label>
                                            <input type="date" name="date" value="{{ $expense->date->format('Y-m-d') }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                        </div>
                                    </div>

                                     <!-- Purchase subfields -->
                                     <div id="edit-device-fields-{{ $expense->id }}" class="{{ $expense->category === 'Pembelian Perangkat' ? '' : 'hidden' }} space-y-4 p-4 rounded-2xl bg-slate-950/50 border border-slate-850">
                                         <div>
                                             <label class="block text-xs font-semibold text-indigo-400 mb-1.5 uppercase">Perangkat yang dibeli</label>
                                             <select name="device_stock_id" id="edit-device-select-{{ $expense->id }}" onchange="handleDeviceSelectChange(this, 'edit-new-device-subfields-{{ $expense->id }}', 'edit-new-brand-{{ $expense->id }}', 'edit-new-name-{{ $expense->id }}', 'edit-new-type-{{ $expense->id }}'); updateEditDevicePreview('{{ $expense->id }}')" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                                 <option value="">-- Pilih Perangkat --</option>
                                                 @foreach($deviceStocks as $device)
                                                     <option value="{{ $device->id }}" data-type="{{ $device->type }}" data-image="{{ $device->image ? asset('storage/' . $device->image) : asset('images/device_' . strtolower($device->type) . '.png') }}" {{ $expense->device_stock_id == $device->id ? 'selected' : '' }}>{{ $device->brand }} - {{ $device->name }} (Sisa: {{ $device->stock }} unit)</option>
                                                 @endforeach
                                                 <option value="NEW_DEVICE">+ Tambah Perangkat Baru...</option>
                                             </select>
                                         </div>

                                         <!-- Product Photo Preview -->
                                         <div id="edit-device-preview-container-{{ $expense->id }}" class="hidden my-2 aspect-[16/10] w-full rounded-xl overflow-hidden bg-slate-950/40 border border-slate-800/80 relative flex items-center justify-center p-2">
                                             <img id="edit-device-preview-img-{{ $expense->id }}" src="" class="w-full h-full object-contain opacity-95 transition-all duration-300">
                                             <div class="absolute bottom-2 left-2 bg-slate-950/70 border border-slate-800 px-2 py-0.5 rounded text-[10px] font-bold text-indigo-400 uppercase tracking-wider" id="edit-device-preview-type-{{ $expense->id }}">FO</div>
                                         </div>

                                         <!-- New device inline fields -->
                                         <div id="edit-new-device-subfields-{{ $expense->id }}" class="hidden space-y-3 p-3.5 rounded-xl bg-slate-900 border border-slate-800">
                                             <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block">Spesifikasi Perangkat Baru</span>
                                             <div class="grid grid-cols-2 gap-3">
                                                 <div>
                                                     <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-1">Merk</label>
                                                     <input type="text" name="new_device_brand" id="edit-new-brand-{{ $expense->id }}" class="w-full bg-slate-950 border border-slate-850 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500" placeholder="Contoh: ZTE">
                                                 </div>
                                                 <div>
                                                     <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-1">Model / Nama</label>
                                                     <input type="text" name="new_device_name" id="edit-new-name-{{ $expense->id }}" class="w-full bg-slate-950 border border-slate-850 rounded-lg px-3 py-2 text-xs text-slate-250 focus:outline-none focus:border-indigo-500" placeholder="Contoh: F609">
                                                 </div>
                                             </div>
                                             <div>
                                                 <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-1">Jenis Jalur</label>
                                                 <select name="new_device_type" id="edit-new-type-{{ $expense->id }}" onchange="updateEditDevicePreview('{{ $expense->id }}')" class="w-full bg-slate-950 border border-slate-850 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500">
                                                     <option value="FO">Fiber Optic (FO)</option>
                                                     <option value="LAN">LAN RJ45</option>
                                                 </select>
                                             </div>
                                         </div>

                                         <div class="grid grid-cols-2 gap-4">
                                             <div>
                                                 <label class="block text-xs font-semibold text-indigo-455 mb-1.5 uppercase">Jumlah Beli (Unit)</label>
                                                 <input type="number" name="quantity" id="edit-qty-{{ $expense->id }}" value="{{ $expense->quantity }}" min="1" oninput="calculateAmount('edit-qty-{{ $expense->id }}', 'edit-price-{{ $expense->id }}', 'edit-amount-{{ $expense->id }}')" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                             </div>
                                             <div>
                                                 <label class="block text-xs font-semibold text-indigo-455 mb-1.5 uppercase">Harga Satuan (Rp)</label>
                                                 <input type="number" name="unit_price_temp" id="edit-price-{{ $expense->id }}" value="{{ $expense->quantity ? ($expense->amount / $expense->quantity) : 0 }}" min="0" oninput="calculateAmount('edit-qty-{{ $expense->id }}', 'edit-price-{{ $expense->id }}', 'edit-amount-{{ $expense->id }}')" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                             </div>
                                         </div>
                                     </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Total Nominal Pengeluaran (Rp)</label>
                                        <input type="number" name="amount" id="edit-amount-{{ $expense->id }}" value="{{ $expense->amount }}" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" {{ $expense->category === 'Pembelian Perangkat' ? 'readonly' : '' }}>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Detail Tambahan</label>
                                        <textarea name="description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Catatan opsional mengenai transaksi...">{{ $expense->description }}</textarea>
                                    </div>

                                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                                        <button type="button" onclick="document.getElementById('modal-edit-expense-{{ $expense->id }}').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-xs font-semibold">Batal</button>
                                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all text-xs">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- MODAL: Delete Expense -->
                        <div id="modal-delete-expense-{{ $expense->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
                            <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
                                <div class="p-6 text-center space-y-4">
                                    <div class="w-16 h-16 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center mx-auto">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-white">Hapus Catatan Pengeluaran?</h3>
                                        <p class="text-sm text-slate-400 mt-2">Menghapus pengeluaran <strong>{{ $expense->title }}</strong> akan mengembalikan/menyesuaikan kembali nilai stok perangkat jika terkait pembelian alat.</p>
                                    </div>
                                    <div class="flex justify-center gap-3 pt-2">
                                        <button onclick="document.getElementById('modal-delete-expense-{{ $expense->id }}').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-sm font-semibold">Batal</button>
                                        <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold transition-colors">Hapus Sekarang</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-slate-500">
                                <svg class="w-12 h-12 text-slate-650 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h4 class="font-bold text-slate-350">Belum Ada Pengeluaran Dicatat</h4>
                                <p class="text-xs text-slate-500 mt-1">Gunakan tombol di atas untuk mencatat pengeluaran operasional baru.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: Add Expense -->
<div id="modal-add-expense" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-800/80 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
        <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
            <h3 class="text-base font-bold text-white">Catat Pengeluaran Baru</h3>
            <button onclick="document.getElementById('modal-add-expense').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form action="{{ route('expenses.store') }}" method="POST" class="p-5 space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Keperluan / Keterangan Singkat</label>
                <input type="text" name="title" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: Belanja Router Tenda 10 Pcs">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Kategori</label>
                    <select name="category" onchange="handleCategoryChange(this, 'add-device-fields', 'add-amount', 'add-qty', 'add-price'); updateAddDevicePreview()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="Operasional">Operasional</option>
                        <option value="Pembelian Perangkat">Pembelian Perangkat</option>
                        <option value="Gaji/Honor">Gaji/Honor</option>
                        <option value="Perbaikan">Perbaikan</option>
                        <option value="ISP">ISP</option>
                        <option value="Listrik">Listrik</option>
                        <option value="Perlengkapan">Perlengkapan</option>
                        <option value="Lain-lain">Lain-lain</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Tanggal Transaksi</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                </div>
            </div>

            <!-- Purchase subfields (Hidden by default since 'Operasional' is pre-selected) -->
            <div id="add-device-fields" class="hidden space-y-4 p-4 rounded-2xl bg-slate-950/50 border border-slate-850">
                <div>
                    <label class="block text-xs font-semibold text-indigo-400 mb-1.5 uppercase">Perangkat yang dibeli</label>
                    <select name="device_stock_id" id="add-device-select" onchange="handleDeviceSelectChange(this, 'add-new-device-subfields', 'add-new-brand', 'add-new-name', 'add-new-type'); updateAddDevicePreview()" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="">-- Pilih Perangkat --</option>
                        @foreach($deviceStocks as $device)
                            <option value="{{ $device->id }}" data-type="{{ $device->type }}" data-image="{{ $device->image ? asset('storage/' . $device->image) : asset('images/device_' . strtolower($device->type) . '.png') }}">{{ $device->brand }} - {{ $device->name }} (Sisa: {{ $device->stock }} unit)</option>
                        @endforeach
                        <option value="NEW_DEVICE">+ Tambah Perangkat Baru...</option>
                    </select>
                </div>

                <!-- Product Photo Preview -->
                <div id="add-device-preview-container" class="hidden my-2 aspect-[16/10] w-full rounded-xl overflow-hidden bg-slate-955/40 border border-slate-800/80 relative flex items-center justify-center p-2">
                    <img id="add-device-preview-img" src="" class="w-full h-full object-contain opacity-95 transition-all duration-300">
                    <div class="absolute bottom-2 left-2 bg-slate-950/70 border border-slate-800 px-2 py-0.5 rounded text-[10px] font-bold text-indigo-400 uppercase tracking-wider" id="add-device-preview-type">FO</div>
                </div>

                <!-- New device inline fields -->
                <div id="add-new-device-subfields" class="hidden space-y-3 p-3.5 rounded-xl bg-slate-900 border border-slate-800">
                    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block">Spesifikasi Perangkat Baru</span>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-1">Merk</label>
                            <input type="text" name="new_device_brand" id="add-new-brand" class="w-full bg-slate-950 border border-slate-850 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500" placeholder="Contoh: ZTE">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-1">Model / Nama</label>
                            <input type="text" name="new_device_name" id="add-new-name" class="w-full bg-slate-950 border border-slate-850 rounded-lg px-3 py-2 text-xs text-slate-250 focus:outline-none focus:border-indigo-500" placeholder="Contoh: F609">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-1">Jenis Jalur</label>
                        <select name="new_device_type" id="add-new-type" onchange="updateAddDevicePreview()" class="w-full bg-slate-950 border border-slate-850 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500">
                            <option value="FO">Fiber Optic (FO)</option>
                            <option value="LAN">LAN RJ45</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-indigo-455 mb-1.5 uppercase">Jumlah Beli (Unit)</label>
                        <input type="number" name="quantity" id="add-qty" value="1" min="1" oninput="calculateAmount('add-qty', 'add-price', 'add-amount')" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-indigo-455 mb-1.5 uppercase">Harga Satuan (Rp)</label>
                        <input type="number" name="unit_price_temp" id="add-price" value="0" min="0" oninput="calculateAmount('add-qty', 'add-price', 'add-amount')" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Total Nominal Pengeluaran (Rp)</label>
                <input type="number" name="amount" id="add-amount" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Ketik nominal atau hitung otomatis...">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-355 mb-1.5 uppercase">Detail Tambahan</label>
                <textarea name="description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Catatan opsional mengenai transaksi..."></textarea>
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-add-expense').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-400 hover:text-white transition-colors text-xs font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all text-xs">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // JS dinamis untuk menyembunyikan/menampilkan subfields device purchase & mengunci field Nominal
    function handleCategoryChange(selectEl, subfieldsId, amountId, qtyId, priceId) {
        const subfields = document.getElementById(subfieldsId);
        const amountInput = document.getElementById(amountId);
        
        if (selectEl.value === 'Pembelian Perangkat') {
            subfields.classList.remove('hidden');
            amountInput.setAttribute('readonly', 'readonly');
            amountInput.classList.add('bg-slate-900/60');
            calculateAmount(qtyId, priceId, amountId);
        } else {
            subfields.classList.add('hidden');
            amountInput.removeAttribute('readonly');
            amountInput.classList.remove('bg-slate-900/60');
        }
    }

    // JS dinamis untuk mengalikan kuantitas x harga unit
    function calculateAmount(qtyId, priceId, amountId) {
        const qty = parseFloat(document.getElementById(qtyId).value) || 0;
        const price = parseFloat(document.getElementById(priceId).value) || 0;
        document.getElementById(amountId).value = qty * price;
    }

    // JS dinamis untuk toggle input registrasi perangkat baru
    function handleDeviceSelectChange(selectEl, subfieldsId, brandId, nameId, typeId) {
        const subfields = document.getElementById(subfieldsId);
        const brandInput = document.getElementById(brandId);
        const nameInput = document.getElementById(nameId);
        const typeInput = document.getElementById(typeId);

        if (selectEl.value === 'NEW_DEVICE') {
            subfields.classList.remove('hidden');
            brandInput.setAttribute('required', 'required');
            nameInput.setAttribute('required', 'required');
            typeInput.setAttribute('required', 'required');
        } else {
            subfields.classList.add('hidden');
            brandInput.removeAttribute('required');
            nameInput.removeAttribute('required');
            typeInput.removeAttribute('required');
        }
    }

    // JS dinamis untuk memutakhirkan highlight foto produk perangkat di Add Modal
    function updateAddDevicePreview() {
        const selectEl = document.getElementById('add-device-select');
        const newTypeEl = document.getElementById('add-new-type');
        const previewContainer = document.getElementById('add-device-preview-container');
        const previewImg = document.getElementById('add-device-preview-img');
        const previewTypeBadge = document.getElementById('add-device-preview-type');

        if (!selectEl || !previewContainer || !previewImg) return;

        let selectedType = '';
        let imageUrl = '';

        if (selectEl.value === 'NEW_DEVICE') {
            selectedType = newTypeEl.value;
            imageUrl = selectedType === 'FO' ? '{{ asset('images/device_fo.png') }}' : '{{ asset('images/device_lan.png') }}';
        } else if (selectEl.value !== '') {
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            selectedType = selectedOption.getAttribute('data-type');
            imageUrl = selectedOption.getAttribute('data-image');
        }

        if (imageUrl) {
            previewImg.src = imageUrl;
            previewTypeBadge.textContent = selectedType === 'FO' ? 'Fiber Optic (FO)' : 'LAN RJ45';
            previewContainer.classList.remove('hidden');
        } else {
            previewContainer.classList.add('hidden');
        }
    }

    // JS dinamis untuk memutakhirkan highlight foto produk perangkat di Edit Modal
    function updateEditDevicePreview(expenseId) {
        const selectEl = document.getElementById('edit-device-select-' + expenseId);
        const newTypeEl = document.getElementById('edit-new-type-' + expenseId);
        const previewContainer = document.getElementById('edit-device-preview-container-' + expenseId);
        const previewImg = document.getElementById('edit-device-preview-img-' + expenseId);
        const previewTypeBadge = document.getElementById('edit-device-preview-type-' + expenseId);

        if (!selectEl || !previewContainer || !previewImg) return;

        let selectedType = '';
        let imageUrl = '';

        if (selectEl.value === 'NEW_DEVICE') {
            selectedType = newTypeEl.value;
            imageUrl = selectedType === 'FO' ? '{{ asset('images/device_fo.png') }}' : '{{ asset('images/device_lan.png') }}';
        } else if (selectEl.value !== '') {
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            selectedType = selectedOption.getAttribute('data-type');
            imageUrl = selectedOption.getAttribute('data-image');
        }

        if (imageUrl) {
            previewImg.src = imageUrl;
            previewTypeBadge.textContent = selectedType === 'FO' ? 'Fiber Optic (FO)' : 'LAN RJ45';
            previewContainer.classList.remove('hidden');
        } else {
            previewContainer.classList.add('hidden');
        }
    }
</script>
@endsection
