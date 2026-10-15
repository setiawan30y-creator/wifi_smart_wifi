@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('pppoe.index') }}" class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-white">Buat Akun PPPoE</h1>
                <p class="text-slate-400 mt-1">Akun ini akan otomatis ditambahkan ke MikroTik dan terdaftar sebagai Pelanggan di Billing.</p>
            </div>
        </div>
        @if($router)
            <span class="inline-flex items-center gap-2 px-3 py-1 text-xs font-bold bg-emerald-500/10 text-emerald-400 rounded-full border border-emerald-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                MikroTik: {{ $router->name }}
            </span>
        @else
            <span class="inline-flex items-center gap-2 px-3 py-1 text-xs font-bold bg-rose-500/10 text-rose-400 rounded-full border border-rose-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                Router Offline
            </span>
        @endif
    </div>

    @if(session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl flex items-start gap-3">
            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="text-sm font-medium text-rose-400">{{ session('error') }}</div>
        </div>
    @endif

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <form action="{{ route('pppoe.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Username PPPoE <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Contoh: agus_rt01">
                    @error('name') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Password PPPoE <span class="text-rose-500">*</span></label>
                    <input type="text" name="password" required class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Buat password yang kuat">
                    @error('password') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Paket Layanan / Profil Kecepatan</label>
                    <select name="package_id" required class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="" disabled selected>Pilih Paket / Profil</option>
                        @foreach($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->name }} - Rp {{ number_format($package->price, 0, ',', '.') }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Nama paket akan otomatis dijadikan nama Profil di MikroTik</p>
                    @error('package_id') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Service</label>
                    <select name="service" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="pppoe" selected>PPPoE</option>
                        <option value="any">Any</option>
                        <option value="async">Async</option>
                        <option value="l2tp">L2TP</option>
                        <option value="ovpn">OVPN</option>
                        <option value="pptp">PPTP</option>
                        <option value="sstp">SSTP</option>
                    </select>
                </div>
            </div>

            <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700">
                <p class="text-sm text-slate-400">
                    <strong class="text-white">Info:</strong> Menekan tombol simpan akan langsung menembakkan data ini ke dalam Winbox MikroTik Anda, dan menuliskannya di tabel Pelanggan aplikasi Billing ini.
                </p>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-slate-800">
                <a href="{{ route('pppoe.index') }}" class="px-6 py-3 rounded-xl font-bold text-sm text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-all">
                    Batal
                </a>
                <button type="submit" @if(!$router) disabled @endif class="px-6 py-3 rounded-xl font-bold text-sm bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/25 flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Tembak ke MikroTik!
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
