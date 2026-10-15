@extends('layouts.app')

@section('content')
@php
    $isClient = auth()->user()->role === 'client';
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-100 tracking-tight">Buat Tiket Gangguan</h1>
                    <p class="text-sm text-slate-400 mt-1">Catat laporan pelanggan dengan detail yang cukup agar teknisi cepat menindaklanjuti.</p>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-300">
            <div class="font-bold mb-2">Ada data yang belum sesuai:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-5 border-b border-slate-800">
                <h2 class="text-base font-bold text-white">Data Laporan</h2>
                <p class="text-xs text-slate-500 mt-1">Gunakan judul singkat dan kronologi yang mudah dipahami.</p>
            </div>

            <form action="{{ route('tickets.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                @unless($isClient)
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Pelanggan Pelapor</label>
                        <select name="user_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                            <option value="">Pilih pelanggan</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }} - {{ $client->phone ?: $client->email }}
                                </option>
                            @endforeach
                        </select>
                        @if($clients->isEmpty())
                            <p class="text-xs text-amber-400 mt-2">Belum ada akun pelanggan. Buat akun pelanggan dulu di pengaturan user atau portal pelanggan.</p>
                        @endif
                    </div>
                @endunless

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Judul Masalah</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Contoh: Internet mati total di rumah pelanggan">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Prioritas Penanganan</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="priority" value="low" class="peer sr-only" {{ old('priority') === 'low' ? 'checked' : '' }}>
                            <span class="block rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm font-semibold text-slate-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-500/10 peer-checked:text-emerald-300">Rendah</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="priority" value="medium" class="peer sr-only" {{ old('priority', 'medium') === 'medium' ? 'checked' : '' }}>
                            <span class="block rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm font-semibold text-slate-300 peer-checked:border-amber-500 peer-checked:bg-amber-500/10 peer-checked:text-amber-300">Normal</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="priority" value="high" class="peer sr-only" {{ old('priority') === 'high' ? 'checked' : '' }}>
                            <span class="block rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm font-semibold text-slate-300 peer-checked:border-rose-500 peer-checked:bg-rose-500/10 peer-checked:text-rose-300">Mendesak</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Kronologi / Detail Keluhan</label>
                    <textarea name="message" rows="7" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors resize-y" placeholder="Tulis gejala, waktu kejadian, perangkat yang dipakai, dan informasi lokasi jika perlu.">{{ old('message') }}</textarea>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 border-t border-slate-800/60">
                    <a href="{{ route('tickets.index') }}" class="px-5 py-2.5 text-sm font-semibold text-center text-slate-300 hover:text-white transition-colors">Batal</a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-indigo-600/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Tiket
                    </button>
                </div>
            </form>
        </div>

        <aside class="space-y-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-white">Standar Data Tiket</h3>
                <div class="mt-4 space-y-3 text-xs text-slate-400">
                    <div class="flex gap-3">
                        <span class="mt-1 w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                        <p>Judul harus langsung menggambarkan gangguan utama.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="mt-1 w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                        <p>Isi kronologi dengan waktu kejadian, status lampu modem/ONU, dan area pelanggan.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="mt-1 w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                        <p>Pilih prioritas mendesak hanya untuk koneksi mati total atau banyak pelanggan terdampak.</p>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-white">Alur Penanganan</h3>
                <div class="mt-4 space-y-3 text-xs text-slate-400">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex w-7 h-7 items-center justify-center rounded-lg bg-rose-500/10 text-rose-300 font-bold">1</span>
                        <span>Open: tiket baru masuk.</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex w-7 h-7 items-center justify-center rounded-lg bg-blue-500/10 text-blue-300 font-bold">2</span>
                        <span>Sedang ditangani oleh teknisi.</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex w-7 h-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-300 font-bold">3</span>
                        <span>Selesai setelah pelanggan normal kembali.</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
