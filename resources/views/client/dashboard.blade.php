@extends('layouts.client')

@section('content')
<!-- Header Area (Curved Background) -->
<div class="bg-indigo-600 rounded-b-[40px] pt-12 pb-24 px-6 text-white relative shadow-lg">
    <!-- User Info -->
    <div class="flex justify-between items-center relative z-10">
        <div>
            <p class="text-indigo-200 text-sm font-medium">Selamat datang kembali,</p>
            <h1 class="text-2xl font-bold mt-1 tracking-tight">{{ auth()->user()->name ?? 'Pelanggan Setia' }}</h1>
        </div>
        <!-- Profile Picture / Initial -->
        <div class="w-12 h-12 rounded-full bg-white/20 border-2 border-white/50 flex items-center justify-center backdrop-blur-md">
            <span class="font-bold text-lg">P</span>
        </div>
    </div>
</div>

<!-- Wallet Card (Floating) -->
<div class="px-6 -mt-16 relative z-20">
    <div class="bg-white rounded-3xl p-6 shadow-xl shadow-slate-200/50 border border-slate-100 relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute -right-6 -top-6 w-24 h-24 bg-gradient-to-br from-indigo-100 to-purple-50 rounded-full blur-2xl"></div>
        
        <p class="text-slate-500 text-sm font-medium">Saldo Dompet Anda</p>
        <div class="flex items-end gap-2 mt-1">
            <span class="text-3xl font-bold text-slate-800 tracking-tight">Rp {{ number_format($customer->balance ?? 0, 0, ',', '.') }}</span>
        </div>
        
        <div class="flex gap-4 mt-6">
            <form action="{{ route('client.wallet.topup') }}" method="POST" class="flex-1" id="form-topup">
                @csrf
                <input type="hidden" name="amount" value="50000"> <!-- Default simulasi TopUp 50k -->
                <button type="button" onclick="confirmTopup()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl py-3.5 text-sm font-semibold shadow-md shadow-indigo-600/20 active:scale-95 transition-all">
                    + Top Up (Simulasi 50k)
                </button>
            </form>
            <a href="{{ route('client.ppob.index') }}" class="w-14 bg-slate-50 hover:bg-slate-100 text-indigo-600 rounded-2xl flex items-center justify-center border border-indigo-100 active:scale-95 transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </a>
        </div>
    </div>
</div>

<script>
    function confirmTopup() {
        if(confirm("Apakah Anda yakin ingin simulasi Top-Up sebesar Rp 50.000?")) {
            document.getElementById('form-topup').submit();
        }
    }
</script>

<!-- Main Services (PPOB Grid) -->
<div class="px-6 mt-8">
    <h2 class="text-lg font-bold text-slate-800 mb-4">Layanan Digital</h2>
    <div class="grid grid-cols-4 gap-4">
        <!-- Menu 1 -->
        <a href="{{ route('client.ppob.index') }}" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-500 group-hover:bg-blue-100 group-active:scale-90 transition-all shadow-sm border border-blue-100/50">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <span class="text-xs font-semibold text-slate-600">Pulsa</span>
        </a>
        <!-- Menu 2 -->
        <a href="{{ route('client.ppob.index') }}" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center text-green-500 group-hover:bg-green-100 group-active:scale-90 transition-all shadow-sm border border-green-100/50">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-xs font-semibold text-slate-600">Data</span>
        </a>
        <!-- Menu 3 -->
        <a href="{{ route('client.ppob.index') }}" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-14 h-14 bg-yellow-50 rounded-2xl flex items-center justify-center text-yellow-500 group-hover:bg-yellow-100 group-active:scale-90 transition-all shadow-sm border border-yellow-100/50">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <span class="text-xs font-semibold text-slate-600">Token PLN</span>
        </a>
        <!-- Menu 4 -->
        <a href="{{ route('client.ppob.index') }}" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-14 h-14 bg-cyan-50 rounded-2xl flex items-center justify-center text-cyan-500 group-hover:bg-cyan-100 group-active:scale-90 transition-all shadow-sm border border-cyan-100/50">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
            </div>
            <span class="text-xs font-semibold text-slate-600">Lainnya</span>
        </a>
    </div>
</div>

<!-- Promo/Info Banner -->
<div class="px-6 mt-8 mb-4">
    <div class="bg-gradient-to-r from-purple-500 to-indigo-500 rounded-3xl p-5 text-white shadow-lg shadow-indigo-500/30 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-lg">Promo Akhir Bulan!</h3>
            <p class="text-sm text-indigo-100 mt-1">Diskon 5% untuk Token PLN.</p>
        </div>
        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>
</div>

<!-- Tagihan Berjalan (Mini List) -->
<div class="px-6 mt-6 mb-8">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-slate-800">Tagihan Internet</h2>
        <a href="#" class="text-sm font-semibold text-indigo-600">Lihat Semua</a>
    </div>
    
    <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100 flex items-center gap-4 active:bg-slate-50 transition-colors cursor-pointer">
        <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1">
            <h4 class="font-bold text-slate-800">Tagihan Bulan Juni</h4>
            <p class="text-xs text-red-500 font-medium mt-0.5">Jatuh Tempo: 20 Jun 2026</p>
        </div>
        <div class="text-right">
            <span class="block font-bold text-slate-800">Rp 150.000</span>
            <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded-md font-bold mt-1 inline-block">Belum Lunas</span>
        </div>
    </div>
</div>
@endsection
