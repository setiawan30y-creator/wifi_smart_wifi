@extends('layouts.app')

@section('title', 'Tagihan Sewa Aplikasi (SaaS)')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Tagihan Sewa Aplikasi</h1>
        <p class="text-slate-400 text-sm mt-1">Kelola dan pantau tagihan penyewaan aplikasi (SaaS) untuk semua RT/RW Net.</p>
    </div>
</div>

@if(session('success'))
    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl mb-6 text-sm font-semibold flex items-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        {{ session('success') }}
    </div>
@endif

<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl shadow-slate-950">
    <div class="overflow-x-auto">
        <table class="w-full text-left whitespace-nowrap">
            <thead>
                <tr class="bg-slate-900/50 border-b border-slate-800">
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider">No. Invoice</th>
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider">Penyewa (Tenant)</th>
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider">Jatuh Tempo</th>
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Nominal</th>
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-center">Status</th>
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/50">
                @forelse($invoices as $invoice)
                    <tr class="hover:bg-slate-800/20 transition-colors">
                        <td class="py-4 px-6 text-sm font-medium text-slate-300">
                            {{ $invoice->invoice_number }}
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-sm font-bold text-white">{{ $invoice->tenant->name }}</div>
                            <div class="text-xs text-slate-400">{{ $invoice->tenant->domain }}</div>
                        </td>
                        <td class="py-4 px-6 text-sm">
                            <span class="{{ $invoice->due_date->isPast() && $invoice->status === 'unpaid' ? 'text-rose-400 font-bold' : 'text-slate-300' }}">
                                {{ $invoice->due_date->format('d M Y') }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-sm text-right font-medium text-slate-300">
                            Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-4 px-6 text-center">
                            @if($invoice->status === 'paid')
                                <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-full text-xs font-medium">Lunas</span>
                            @elseif($invoice->status === 'unpaid')
                                <span class="px-2.5 py-1 bg-rose-500/10 text-rose-400 border border-rose-500/20 rounded-full text-xs font-medium">Belum Bayar</span>
                            @else
                                <span class="px-2.5 py-1 bg-slate-500/10 text-slate-400 border border-slate-500/20 rounded-full text-xs font-medium">Dibatalkan</span>
                            @endif
                        </td>
                            <div class="flex items-center justify-end gap-2">
                                @if($invoice->status === 'unpaid')
                                    <form action="{{ route('saas.invoices.mark_paid', $invoice->id) }}" method="POST" onsubmit="return confirm('Tandai tagihan ini sudah lunas secara manual? (Akan memperpanjang masa aktif 1 bulan)');">
                                        @csrf
                                        <button type="submit" title="Tandai Lunas" class="bg-indigo-600/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                                            Lunas
                                        </button>
                                    </form>
                                    <form action="{{ route('saas.invoices.send_wa', $invoice->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" title="Kirim Ulang WA" class="bg-emerald-600/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-600 hover:text-white px-2 py-1.5 rounded-lg transition-all">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        </button>
                                    </form>
                                @elseif($invoice->status === 'pending_verification')
                                    <form action="{{ route('saas.invoices.mark_paid', $invoice->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-indigo-600/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                                            Verifikasi & Lunas
                                        </button>
                                    </form>
                                    <a href="{{ asset('storage/' . $invoice->payment_proof) }}" target="_blank" class="bg-slate-600/10 text-slate-400 border border-slate-500/20 hover:bg-slate-600 hover:text-white px-2 py-1.5 rounded-lg text-xs font-semibold transition-all">
                                        Bukti
                                    </a>
                                @else
                                    <span class="text-xs text-slate-500">Lunas: {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y') : '' }}</span>
                                @endif
                                
                                <a href="{{ route('saas.invoices.print', $invoice->id) }}" target="_blank" title="Cetak Invoice" class="bg-slate-600/10 text-slate-400 border border-slate-500/20 hover:bg-slate-600 hover:text-white px-2 py-1.5 rounded-lg transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </a>
                            </div>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-500">
                            Belum ada tagihan sewa yang dibuat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-800">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
