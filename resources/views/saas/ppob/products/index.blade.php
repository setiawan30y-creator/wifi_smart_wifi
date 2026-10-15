@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-white mb-1">Master Produk PPOB</h2>
        <p class="text-slate-400 text-sm">Kelola daftar produk dan pemetaan harga (Mapping) dari berbagai Suplier.</p>
    </div>
    <button onclick="document.getElementById('modal-add').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Tambah Produk
    </button>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-slate-950/50 border-b border-slate-800 text-slate-400">
                <tr>
                    <th class="px-6 py-4 font-medium uppercase tracking-wider text-xs">Produk</th>
                    <th class="px-6 py-4 font-medium uppercase tracking-wider text-xs">Harga Jual (Ke Klien)</th>
                    <th class="px-6 py-4 font-medium uppercase tracking-wider text-xs">Mapping Suplier</th>
                    <th class="px-6 py-4 font-medium uppercase tracking-wider text-xs">Status</th>
                    <th class="px-6 py-4 font-medium uppercase tracking-wider text-xs text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($products as $product)
                <tr class="hover:bg-slate-800/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-200">{{ $product->name }}</div>
                        <div class="text-[10px] uppercase font-bold text-indigo-400 mt-1">{{ $product->category }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-emerald-400 font-bold">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1.5">
                            @foreach($product->supplierProducts as $mapping)
                                <div class="flex items-center gap-2 text-xs bg-slate-800/50 rounded-md px-2 py-1 w-max border border-slate-700/50">
                                    <span class="text-slate-300 font-medium">{{ $mapping->supplier->name }}</span>
                                    <span class="text-slate-500">|</span>
                                    <span class="text-rose-400 font-mono">Modal: Rp {{ number_format($mapping->buy_price, 0, ',', '.') }}</span>
                                    <span class="text-slate-500">|</span>
                                    <span class="text-slate-400 font-mono">SKU: {{ $mapping->supplier_sku }}</span>
                                    
                                    <form action="{{ route('saas.ppob.mapping.destroy', $mapping->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus mapping ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-rose-500 hover:text-rose-400 ml-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                            
                            <button onclick="addMapping({{ $product->id }}, '{{ $product->name }}')" class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1 mt-1 w-max">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Tambah Mapping
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($product->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                Nonaktif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editProduct({{ $product->toJson() }})" class="p-2 text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            <form action="{{ route('saas.ppob.products.destroy', $product->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus produk ini beserta seluruh mappingnya?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">Belum ada Master Produk.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Product -->
<div id="modal-add" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="document.getElementById('modal-add').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-white">Tambah Produk PPOB</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="{{ route('saas.ppob.products.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Kategori</label>
                <select name="category" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                    <option value="Pulsa">Pulsa Reguler</option>
                    <option value="Data">Paket Data</option>
                    <option value="Token Listrik">Token Listrik</option>
                    <option value="E-Money">Topup E-Money</option>
                    <option value="Games">Voucher Game</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Nama Produk</label>
                <input type="text" name="name" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Contoh: Telkomsel 10.000">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Harga Jual (Rp)</label>
                <input type="number" name="sell_price" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="12000">
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-medium transition-colors">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Product -->
<div id="modal-edit" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="document.getElementById('modal-edit').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-white">Edit Produk PPOB</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="form-edit" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Kategori</label>
                <select name="category" id="edit-category" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
                    <option value="Pulsa">Pulsa Reguler</option>
                    <option value="Data">Paket Data</option>
                    <option value="Token Listrik">Token Listrik</option>
                    <option value="E-Money">Topup E-Money</option>
                    <option value="Games">Voucher Game</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Nama Produk</label>
                <input type="text" name="name" id="edit-name" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Harga Jual (Rp)</label>
                <input type="number" name="sell_price" id="edit-price" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
            </div>
            <label class="flex items-center gap-3 p-3 bg-slate-950 rounded-xl border border-slate-800 cursor-pointer">
                <input type="checkbox" name="is_active" id="edit-active" value="1" class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-indigo-500">
                <span class="text-sm font-medium text-slate-300">Aktifkan Produk</span>
            </label>
            <div class="pt-2">
                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-medium transition-colors">Perbarui Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Mapping -->
<div id="modal-mapping" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="document.getElementById('modal-mapping').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-bold text-white">Tambah Mapping Suplier</h3>
                <p class="text-xs text-indigo-400" id="mapping-product-name"></p>
            </div>
            <button onclick="document.getElementById('modal-mapping').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="form-mapping" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Pilih Suplier</label>
                <select name="supplier_id" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
                    <option value="">-- Pilih Suplier --</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">SKU Suplier (Kode Produk)</label>
                <input type="text" name="supplier_sku" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200" placeholder="Misal: TSEL10">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Harga Modal (Rp)</label>
                <input type="number" name="buy_price" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200" placeholder="10500">
            </div>
            <label class="flex items-center gap-3 p-3 bg-slate-950 rounded-xl border border-slate-800 cursor-pointer">
                <input type="checkbox" name="status" value="1" checked class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-indigo-500">
                <span class="text-sm font-medium text-slate-300">Aktifkan Mapping Ini</span>
            </label>
            <div class="pt-2">
                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-medium transition-colors">Simpan Mapping</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editProduct(product) {
        document.getElementById('form-edit').action = '/admin/ppob/products/' + product.id;
        document.getElementById('edit-category').value = product.category;
        document.getElementById('edit-name').value = product.name;
        document.getElementById('edit-price').value = product.sell_price;
        document.getElementById('edit-active').checked = product.is_active == 1;
        document.getElementById('modal-edit').classList.remove('hidden');
    }

    function addMapping(productId, productName) {
        document.getElementById('form-mapping').action = '/admin/ppob/products/' + productId + '/mapping';
        document.getElementById('mapping-product-name').innerText = 'Produk: ' + productName;
        document.getElementById('modal-mapping').classList.remove('hidden');
    }
</script>
@endsection
