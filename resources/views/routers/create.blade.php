@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    <div class="flex items-center gap-4">
        <a href="{{ route('routers.index') }}" class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Tambah Router MikroTik</h1>
            <p class="text-slate-400 mt-1">Masukkan kredensial login Winbox/API router Anda.</p>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <form action="{{ route('routers.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Nama Router / Identitas</label>
                    <input type="text" name="name" required class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Contoh: MikroTik Pusat">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">IP Address / Host</label>
                    <input type="text" name="ip_address" required class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="192.168.88.1">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">API Port</label>
                    <input type="number" name="port" value="8728" required class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <p class="text-[11px] text-slate-500 mt-1">Default API Port: 8728</p>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Keterangan (Opsional)</label>
                    <input type="text" name="description" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Router utama NOC lantai 1">
                </div>

                <div class="md:col-span-2">
                    <hr class="border-slate-800 my-2">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Username Winbox</label>
                    <input type="text" name="username" required class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="admin">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Password</label>
                    <input type="password" name="password" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Kosongkan jika tidak ada password">
                    <p class="text-[11px] text-emerald-500 mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Password akan dienkripsi secara aman di database.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-slate-800">
                <a href="{{ route('routers.index') }}" class="px-6 py-3 rounded-xl font-bold text-sm text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-all">
                    Batal
                </a>
                <button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/25 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Simpan Router
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
