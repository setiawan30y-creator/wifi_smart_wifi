@extends('layouts.app')

@section('title', 'Katalog & Harga Jual PPOB')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Katalog Produk PPOB</h1>
        <p class="text-slate-400 text-sm mt-1">Atur keuntungan (Markup) Anda untuk setiap produk yang dijual ke pelanggan.</p>
    </div>
    
    <!-- Info Saldo Deposit -->
    <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-2xl px-5 py-3 flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Saldo Deposit Anda</p>
            <p class="text-lg font-bold text-white">Rp {{ number_format(auth()->user()->tenant->balance, 0, ',', '.') }}</p>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl mb-6 text-sm font-semibold flex items-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('admin.ppob.markup.store') }}" method="POST">
    @csrf
    
    <div class="flex justify-end mb-4 sticky top-4 z-10">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-lg shadow-indigo-500/20 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            Simpan Perubahan Harga
        </button>
    </div>

    <div class="space-y-8">
        @forelse($products as $category => $items)
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl shadow-slate-950">
                <div class="px-6 py-4 border-b border-slate-800 bg-slate-800/50">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        {{ $category }}
                        <span class="text-xs font-medium bg-slate-700 text-slate-300 px-2 py-0.5 rounded-full">{{ $items->count() }} Produk</span>
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-900/50">
                                <th class="py-3 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider">Produk</th>
                                <th class="py-3 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Harga Modal Dasar</th>
                                <th class="py-3 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Tambah Keuntungan (Markup)</th>
                                <th class="py-3 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Harga Jual Akhir</th>
                                <th class="py-3 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-center">Tampilkan di Klien?</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/50">
                            @foreach($items as $product)
                                <tr class="hover:bg-slate-800/20 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div class="text-sm font-bold text-indigo-400">{{ $product->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $product->provider }}</div>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <span class="text-sm font-medium text-slate-300">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="text-slate-400 font-medium">+ Rp</span>
                                            <input type="number" 
                                                   name="markups[{{ $product->id }}][amount]" 
                                                   value="{{ $product->tenant_markup }}" 
                                                   min="0"
                                                   class="w-24 bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-1.5 text-sm text-right focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-mono"
                                                   oninput="updateFinalPrice(this, {{ $product->sell_price }}, 'final-price-{{ $product->id }}')">
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <span id="final-price-{{ $product->id }}" class="text-sm font-bold text-emerald-400">
                                            Rp {{ number_format($product->final_price, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="markups[{{ $product->id }}][is_active]" value="1" class="sr-only peer" {{ $product->is_active_for_tenant ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-12 text-center">
                <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Katalog Kosong</h3>
                <p class="text-slate-400 text-sm">Penyedia layanan (SaaS) belum menambahkan produk PPOB.</p>
            </div>
        @endforelse
    </div>
</form>

<script>
    function updateFinalPrice(input, basePrice, targetId) {
        let markup = parseFloat(input.value) || 0;
        let finalPrice = basePrice + markup;
        
        document.getElementById(targetId).innerText = 'Rp ' + finalPrice.toLocaleString('id-ID');
    }
</script>
@endsection
