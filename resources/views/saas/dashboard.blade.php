@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-extrabold text-white tracking-tight">Pusat Kendali SaaS</h2>
            <p class="text-slate-400 text-sm mt-1">Ringkasan penggunaan dan pertumbuhan seluruh perusahaan klien (Penyewa).</p>
        </div>
        <a href="{{ route('saas.tenants.index') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/20 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Kelola Penyewa
        </a>
    </div>

    <!-- Statistik Utama -->
    <div class="flex flex-col md:flex-row gap-6">
        
        <!-- Total Perusahaan -->
        <div class="flex-1 bg-gradient-to-br from-indigo-900/40 to-slate-900/80 border border-indigo-500/20 backdrop-blur-md rounded-3xl p-6 relative overflow-hidden group hover:border-indigo-500/40 transition-all duration-300 shadow-lg shadow-indigo-900/20">
            <div class="absolute right-0 bottom-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-all duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Total Penyewa</span>
                <span class="p-2.5 rounded-xl bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </span>
            </div>
            <div class="mt-5 relative z-10">
                <span class="text-4xl font-extrabold tracking-tight text-white">{{ $totalTenants }}</span>
            </div>
            <p class="text-xs text-indigo-200/70 mt-3 font-medium relative z-10">Seluruh RT/RW Net Terdaftar</p>
        </div>

        <!-- Perusahaan Aktif -->
        <div class="flex-1 bg-gradient-to-br from-emerald-900/40 to-slate-900/80 border border-emerald-500/20 backdrop-blur-md rounded-3xl p-6 relative overflow-hidden group hover:border-emerald-500/40 transition-all duration-300 shadow-lg shadow-emerald-900/10">
            <div class="absolute right-0 bottom-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-bold text-emerald-200 uppercase tracking-wider">Penyewa Aktif</span>
                <span class="p-2.5 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="mt-5 flex items-baseline gap-2 relative z-10">
                <span class="text-4xl font-extrabold tracking-tight text-white">{{ $activeTenants }}</span>
                <span class="text-sm font-medium text-emerald-300 pb-1">aktif</span>
            </div>
            <p class="text-xs text-emerald-300/70 mt-3 font-medium relative z-10">RT/RW Net tidak terkunci</p>
        </div>

        <!-- Total Admin/Staff -->
        <div class="flex-1 bg-gradient-to-br from-purple-900/40 to-slate-900/80 border border-purple-500/20 backdrop-blur-md rounded-3xl p-6 relative overflow-hidden group hover:border-purple-500/40 transition-all duration-300 shadow-lg shadow-purple-900/10">
            <div class="absolute right-0 bottom-0 w-32 h-32 bg-purple-500/10 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-bold text-purple-200 uppercase tracking-wider">Total Admin/Staff</span>
                <span class="p-2.5 rounded-xl bg-purple-500/20 text-purple-300 border border-purple-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </span>
            </div>
            <div class="mt-5 relative z-10">
                <span class="text-4xl font-extrabold tracking-tight text-white">{{ $totalUsers }}</span>
            </div>
            <p class="text-xs text-purple-300/70 mt-3 font-medium relative z-10">Pekerja di semua RT/RW Net</p>
        </div>

        <!-- Total Pelanggan -->
        <div class="flex-1 bg-gradient-to-br from-amber-900/40 to-slate-900/80 border border-amber-500/20 backdrop-blur-md rounded-3xl p-6 relative overflow-hidden group hover:border-amber-500/40 transition-all duration-300 shadow-lg shadow-amber-900/10">
            <div class="absolute right-0 bottom-0 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-bold text-amber-200 uppercase tracking-wider">Pelanggan End-User</span>
                <span class="p-2.5 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </span>
            </div>
            <div class="mt-5 relative z-10">
                <span class="text-4xl font-extrabold tracking-tight text-white">{{ $totalCustomers }}</span>
            </div>
            <p class="text-xs text-amber-300/70 mt-3 font-medium relative z-10">Rumah tangga terhubung</p>
        </div>

    </div>
    
    <!-- Bagian Bawah: Data & Aktivitas Terbaru -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Penyewa Baru Bergabung -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-3xl shadow-xl shadow-slate-950 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-800 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">5 Penyewa (Tenant) Terbaru</h3>
                <a href="{{ route('saas.tenants.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">Lihat Semua &rarr;</a>
            </div>
            <div class="p-0">
                <table class="w-full text-left whitespace-nowrap">
                    <tbody class="divide-y divide-slate-800/50">
                        @forelse($latestTenants as $tenant)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-indigo-400 font-bold border border-slate-700">
                                        {{ substr($tenant->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-white">{{ $tenant->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $tenant->domain }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-sm">
                                <span class="text-slate-400">Paket:</span> <span class="text-white font-bold uppercase">{{ $tenant->subscription_plan }}</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if($tenant->status === 'active')
                                    <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-full text-xs font-medium">Aktif</span>
                                @else
                                    <span class="px-2.5 py-1 bg-rose-500/10 text-rose-400 border border-rose-500/20 rounded-full text-xs font-medium">Terkunci</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-12 text-center text-slate-500">Belum ada penyewa yang mendaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- PPOB Wallet Stats (Ide Tambahan) -->
        <div class="bg-gradient-to-b from-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden flex flex-col justify-center text-center">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <svg class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            
            <div class="relative z-10 mb-4">
                <span class="inline-block p-4 rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </span>
                <h3 class="text-lg font-bold text-white mb-2">Pantau Ekosistem PPOB</h3>
                <p class="text-sm text-slate-400 leading-relaxed mb-6">Sebagai Induk SaaS, Anda dapat memantau setiap transaksi Pulsa & PPOB yang dilakukan oleh para Pelanggan RT/RW Net di seluruh Indonesia.</p>
                <a href="{{ route('saas.ppob.transactions.index') }}" class="inline-block w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-3 px-4 rounded-xl transition-colors border border-slate-700">
                    Lihat Transaksi PPOB
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
