@extends('layouts.app')

@section('content')
<div class="px-6 py-8 mx-auto max-w-7xl">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm font-medium text-slate-500 mb-6">
        <a href="#" class="hover:text-indigo-400">Master Produk PPOB</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-300">Mapping Harga Suplier</span>
    </div>

    <!-- Header -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8 flex items-center justify-between shadow-xl">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-red-500/20 text-red-500 rounded-2xl flex items-center justify-center font-bold text-xl">
                T
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-100">Telkomsel Data 10GB</h1>
                <div class="flex gap-4 mt-1 text-sm">
                    <p class="text-slate-400">Kategori: <span class="text-slate-300 font-semibold">Paket Data</span></p>
                    <p class="text-slate-400">Kode Master: <span class="text-indigo-400 font-mono">TSEL-DATA-10</span></p>
                </div>
            </div>
        </div>
        <div class="text-right">
            <p class="text-slate-400 text-sm">Harga Jual Klien</p>
            <p class="text-2xl font-bold text-green-400">Rp 45.000</p>
        </div>
    </div>

    <!-- Mapping Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Auto-Routing Status & Rules -->
        <div class="space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-indigo-500/20 text-indigo-400 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-100">Status Auto-Routing</h2>
                </div>
                <p class="text-slate-400 text-sm mb-4 leading-relaxed">
                    Sistem saat ini disetel untuk selalu memilih <strong class="text-slate-200">Harga Modal Terendah</strong> dari suplier yang berstatus Aktif.
                </p>
                <div class="p-4 bg-slate-950 rounded-xl border border-slate-800">
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-2">Suplier Terpilih Saat Ini</p>
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-indigo-400">Mobilepulsa</span>
                        <span class="font-bold text-slate-200">Rp 42.150</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-800 flex justify-between text-sm">
                        <span class="text-slate-400">Estimasi Margin:</span>
                        <span class="font-bold text-green-400">Rp 2.850</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Supplier Pricing Table -->
        <div class="lg:col-span-2">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-lg">
                <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-slate-100">Data Harga Suplier</h2>
                    <button class="text-sm font-medium text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Tambah SKU Suplier
                    </button>
                </div>
                
                <table class="w-full text-left text-sm text-slate-400">
                    <thead class="text-xs uppercase bg-slate-950/50">
                        <tr>
                            <th class="px-6 py-4 font-medium">Suplier</th>
                            <th class="px-6 py-4 font-medium">Kode SKU Suplier</th>
                            <th class="px-6 py-4 font-medium">Harga Modal</th>
                            <th class="px-6 py-4 font-medium text-center">Status SKU</th>
                            <th class="px-6 py-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <!-- Row 1: Digiflazz -->
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-200">Digiflazz</td>
                            <td class="px-6 py-4 font-mono text-indigo-400">tsel_data10</td>
                            <td class="px-6 py-4 text-slate-300">Rp 42.500</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 text-[10px] font-bold uppercase rounded-md bg-green-500/10 text-green-400 border border-green-500/20">Aktif</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-white transition-colors">Edit</button>
                            </td>
                        </tr>
                        
                        <!-- Row 2: Mobilepulsa (Lowest Price = Highlighted) -->
                        <tr class="bg-indigo-900/10 hover:bg-indigo-900/20 transition-colors">
                            <td class="px-6 py-4 font-bold text-indigo-300 flex items-center gap-2">
                                Mobilepulsa
                                <span title="Harga Termurah" class="text-green-400">★</span>
                            </td>
                            <td class="px-6 py-4 font-mono text-indigo-400">MP-TD10</td>
                            <td class="px-6 py-4 font-bold text-green-400">Rp 42.150</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 text-[10px] font-bold uppercase rounded-md bg-green-500/10 text-green-400 border border-green-500/20">Aktif</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-white transition-colors">Edit</button>
                            </td>
                        </tr>

                        <!-- Row 3: Tripay PPOB (Gangguan) -->
                        <tr class="hover:bg-slate-800/50 transition-colors opacity-60">
                            <td class="px-6 py-4 font-bold text-slate-400">Tripay PPOB</td>
                            <td class="px-6 py-4 font-mono text-slate-500">TRP-DATA-10</td>
                            <td class="px-6 py-4 text-slate-500">Rp 42.000</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 text-[10px] font-bold uppercase rounded-md bg-red-500/10 text-red-400 border border-red-500/20">Gangguan</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-white transition-colors">Edit</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
