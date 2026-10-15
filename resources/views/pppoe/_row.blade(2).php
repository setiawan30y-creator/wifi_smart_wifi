<tr class="transition-colors group">
    @php
        $isActive = isset($activeConnections[trim($customer->pppoe_username)]);
    @endphp
    <td class="px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 font-bold text-[10px] border border-slate-700">
                {{ strtoupper(substr($customer->pppoe_username, 0, 1)) }}
            </div>
            <div class="font-bold text-white">{{ $customer->pppoe_username }}</div>
        </div>
    </td>
    <td class="px-6 py-4">
        <div class="font-semibold text-slate-200">{{ $customer->name }}</div>
        <div class="mt-0.5 text-[11px] text-slate-500">{{ $customer->phone ?? '-' }}</div>
        <div class="mt-0.5 text-[11px] text-indigo-300/80">{{ $customer->odp?->name ?? 'Tanpa ODP' }}</div>
    </td>
    <td class="px-6 py-4 text-slate-300 font-mono text-xs">
        <div class="flex items-center gap-2">
            <span class="blur-sm group-hover:blur-none transition-all duration-300 cursor-help" title="Arahkan kursor untuk melihat password">
                {{ $customer->pppoe_password ?? '***' }}
            </span>
        </div>
    </td>
    <td class="px-6 py-4">
        <span class="px-2 py-0.5 text-[11px] font-medium bg-slate-800 text-slate-300 rounded-lg border border-slate-700">
            {{ $customer->package ? $customer->package->name : 'default' }}
        </span>
    </td>
    <td class="px-6 py-4">
        @if($customer->status !== 'active')
            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium bg-rose-500/10 text-rose-400 rounded-lg border border-rose-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Disabled
            </span>
        @elseif($isActive)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium bg-emerald-500/10 text-emerald-400 rounded-lg border border-emerald-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Online
            </span>
        @else
            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium bg-slate-500/10 text-slate-400 rounded-lg border border-slate-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Offline
            </span>
        @endif
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
        <div class="flex items-center justify-end gap-1">
            <a href="{{ route('pppoe.edit', $customer->id) }}" class="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-colors" title="Edit Akun">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </a>
            <form action="{{ route('pppoe.toggle', $customer->id) }}" method="POST" class="inline" onsubmit="return confirm('Anda yakin ingin merubah status akun ini?');">
                @csrf
                <button type="submit" class="p-1.5 text-slate-400 hover:text-amber-400 hover:bg-amber-500/10 rounded-lg transition-colors" title="Enable/Disable Akun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                </button>
            </form>
            <form action="{{ route('pppoe.destroy', $customer->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin mencabut akun ini dari MikroTik?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors" title="Hapus Akun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </form>
        </div>
    </td>
</tr>
