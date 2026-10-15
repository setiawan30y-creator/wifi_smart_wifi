@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Perusahaan Klien (Tenants)</h2>
            <p class="text-slate-400 text-sm mt-1">Kelola perusahaan-perusahaan RT/RW yang menyewa aplikasi Anda.</p>
        </div>
        <button onclick="document.getElementById('modal-create-tenant').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Perusahaan
        </button>
    </div>

    <!-- Tabel Tenants -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">Nama Perusahaan</th>
                        <th class="px-6 py-4">Domain/Subdomain</th>
                        <th class="px-6 py-4">Total Pengguna</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($tenants as $tenant)
                    <tr class="hover:bg-slate-800/30 transition-colors">
                        <td class="px-6 py-4 font-medium text-white">
                            {{ $tenant->name }}
                            @if($tenant->id === 1)
                                <span class="ml-2 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-400 bg-indigo-400/10 border border-indigo-400/20 rounded-md">Utama</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $tenant->domain ?: '-' }}</td>
                        <td class="px-6 py-4">{{ $tenant->users_count }}</td>
                        <td class="px-6 py-4">
                            @if($tenant->status === 'active')
                                <span class="px-2.5 py-1 text-xs font-semibold text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 rounded-lg">Aktif</span>
                            @else
                                <span class="px-2.5 py-1 text-xs font-semibold text-rose-400 bg-rose-400/10 border border-rose-400/20 rounded-lg">Suspended</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @if($tenant->id !== 1)
                                <form action="{{ route('saas.tenants.toggle-status', $tenant) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-xs font-semibold px-3 py-1.5 rounded-lg border {{ $tenant->status === 'active' ? 'text-amber-400 border-amber-400/30 hover:bg-amber-400/10' : 'text-emerald-400 border-emerald-400/30 hover:bg-emerald-400/10' }}">
                                        {{ $tenant->status === 'active' ? 'Suspend' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form action="{{ route('saas.tenants.destroy', $tenant) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus seluruh data perusahaan ini secara permanen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-semibold px-3 py-1.5 rounded-lg border text-rose-400 border-rose-400/30 hover:bg-rose-400/10">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">Belum ada perusahaan klien yang ditambahkan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
        <div class="px-6 py-4 border-t border-slate-800/60 bg-slate-900/50">
            {{ $tenants->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Create Tenant -->
<div id="modal-create-tenant" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <h3 class="text-lg font-bold text-white">Tambah Perusahaan Baru</h3>
            <button type="button" onclick="document.getElementById('modal-create-tenant').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form action="{{ route('saas.tenants.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Nama Perusahaan / RT-RW</label>
                <input type="text" name="name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-600" placeholder="Misal: RT-RW Net Maju Bersama">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Domain Khusus (Opsional)</label>
                <input type="text" name="domain" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-600" placeholder="Misal: maju-net.billingrtrw.com">
            </div>

            <div class="pt-4 border-t border-slate-800">
                <h4 class="text-sm font-bold text-indigo-400 mb-4">Buat Akun Admin Klien</h4>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">Nama Admin</label>
                        <input type="text" name="admin_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-600" placeholder="Nama Pemilik/Admin">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">Email Admin</label>
                        <input type="email" name="admin_email" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-600" placeholder="admin@maju-net.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">Password Sementara</label>
                        <input type="text" name="admin_password" required minlength="8" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-600" placeholder="Minimal 8 karakter">
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-create-tenant').classList.add('hidden')" class="px-4 py-2.5 rounded-xl font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">Batal</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl font-medium text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/20 transition-all">Simpan Klien Baru</button>
            </div>
        </form>
    </div>
</div>
@endsection
