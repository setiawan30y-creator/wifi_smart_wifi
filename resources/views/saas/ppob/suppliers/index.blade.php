@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-white mb-1">API Provider PPOB</h2>
        <p class="text-slate-400 text-sm">Kelola kredensial koneksi ke Suplier PPOB (Contoh: Digiflazz, Tripay).</p>
    </div>
    <button onclick="document.getElementById('modal-add').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Tambah Suplier
    </button>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-slate-950/50 border-b border-slate-800 text-slate-400">
                <tr>
                    <th class="px-6 py-4 font-medium uppercase tracking-wider text-xs">Provider</th>
                    <th class="px-6 py-4 font-medium uppercase tracking-wider text-xs">Endpoint URL</th>
                    <th class="px-6 py-4 font-medium uppercase tracking-wider text-xs">Status</th>
                    <th class="px-6 py-4 font-medium uppercase tracking-wider text-xs text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-slate-800/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-medium text-slate-200">{{ $supplier->name }}</div>
                        <div class="text-xs text-slate-500 font-mono mt-0.5">Key: {{ substr($supplier->api_key, 0, 8) }}***</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-slate-400 font-mono text-xs">{{ $supplier->endpoint ?? '-' }}</span>
                    </td>
                    <td class="px-6 py-4">
                        @if($supplier->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-500"></div> Nonaktif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editSupplier({{ $supplier->toJson() }})" class="p-2 text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            <form action="{{ route('saas.ppob.suppliers.destroy', $supplier->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus suplier ini beserta seluruh mapping harganya?');">
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
                    <td colspan="4" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 bg-slate-800/50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <h3 class="text-slate-300 font-medium mb-1">Belum ada Suplier</h3>
                            <p class="text-slate-500 text-sm">Tambahkan kredensial API Suplier (misal: Digiflazz) untuk mulai berjualan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="modal-add" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="document.getElementById('modal-add').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-white">Tambah Suplier PPOB</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="{{ route('saas.ppob.suppliers.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Nama Provider</label>
                <input type="text" name="name" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Contoh: Digiflazz">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Endpoint URL</label>
                <input type="url" name="endpoint" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="https://api.digiflazz.com/v1/...">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">API Key / Username</label>
                <input type="text" name="api_key" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">API Secret / Password</label>
                <input type="password" name="api_secret" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
            </div>
            <label class="flex items-center gap-3 p-3 bg-slate-950 rounded-xl border border-slate-800 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-slate-900">
                <span class="text-sm font-medium text-slate-300">Aktifkan Suplier ini</span>
            </label>
            <div class="pt-2">
                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-medium transition-colors">Simpan Suplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="modal-edit" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="document.getElementById('modal-edit').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-white">Edit Suplier</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="form-edit" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Nama Provider</label>
                <input type="text" name="name" id="edit-name" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Endpoint URL</label>
                <input type="url" name="endpoint" id="edit-endpoint" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">API Key / Username</label>
                <input type="text" name="api_key" id="edit-key" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">API Secret / Password (Biarkan kosong jika tidak diubah)</label>
                <input type="password" name="api_secret" id="edit-secret" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
            </div>
            <label class="flex items-center gap-3 p-3 bg-slate-950 rounded-xl border border-slate-800 cursor-pointer">
                <input type="checkbox" name="is_active" id="edit-active" value="1" class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-slate-900">
                <span class="text-sm font-medium text-slate-300">Aktifkan Suplier ini</span>
            </label>
            <div class="pt-2">
                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-medium transition-colors">Perbarui Suplier</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editSupplier(supplier) {
        document.getElementById('form-edit').action = '/saas/ppob/suppliers/' + supplier.id;
        document.getElementById('edit-name').value = supplier.name;
        document.getElementById('edit-endpoint').value = supplier.endpoint || '';
        document.getElementById('edit-key').value = supplier.api_key || '';
        document.getElementById('edit-secret').value = ''; // Don't pre-fill password
        document.getElementById('edit-active').checked = supplier.is_active == 1;
        document.getElementById('modal-edit').classList.remove('hidden');
    }
</script>
@endsection
