@extends('layouts.app')

@section('title', 'Tagihan Langganan Aplikasi')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Tagihan Langganan</h1>
        <p class="text-slate-400 text-sm mt-1">Riwayat tagihan biaya sewa aplikasi (SaaS) RT/RW Net Anda.</p>
    </div>
    
    <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-2xl px-5 py-3 flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
        <div>
            <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Masa Aktif Sampai</p>
            <p class="text-lg font-bold text-white">
                {{ auth()->user()->tenant->subscription_ends_at ? auth()->user()->tenant->subscription_ends_at->format('d M Y') : 'Selamanya' }}
            </p>
        </div>
    </div>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl shadow-slate-950">
    <div class="overflow-x-auto">
        <table class="w-full text-left whitespace-nowrap">
            <thead>
                <tr class="bg-slate-900/50 border-b border-slate-800">
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider">No. Tagihan</th>
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider">Jatuh Tempo</th>
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Total Biaya</th>
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-center">Status</th>
                    <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/50">
                @forelse($invoices as $invoice)
                    <tr class="hover:bg-slate-800/20 transition-colors">
                        <td class="py-4 px-6 text-sm font-bold text-indigo-400">
                            {{ $invoice->invoice_number }}
                        </td>
                        <td class="py-4 px-6 text-sm text-slate-300">
                            {{ $invoice->due_date->format('d F Y') }}
                        </td>
                        <td class="py-4 px-6 text-sm text-right font-bold text-white">
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
                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($invoice->status === 'unpaid')
                                    @if($invoice->checkout_url)
                                        <a href="{{ $invoice->checkout_url }}" target="_blank" class="bg-emerald-600/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-600 hover:text-white px-4 py-2 rounded-xl text-xs font-semibold transition-all">
                                            Bayar via Tripay
                                        </a>
                                    @endif
                                    
                                    <button type="button" onclick="document.getElementById('upload-modal-{{ $invoice->id }}').classList.remove('hidden')" class="bg-slate-600/10 text-slate-400 border border-slate-500/20 hover:bg-slate-600 hover:text-white px-4 py-2 rounded-xl text-xs font-semibold transition-all">
                                        Upload Bukti
                                    </button>
                                    
                                    <!-- Upload Modal -->
                                    <div id="upload-modal-{{ $invoice->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                                        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl">
                                            <div class="flex justify-between items-center mb-4">
                                                <h3 class="text-lg font-bold text-white">Upload Bukti Transfer</h3>
                                                <button type="button" onclick="document.getElementById('upload-modal-{{ $invoice->id }}').classList.add('hidden')" class="text-slate-400 hover:text-white">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </div>
                                            <form action="{{ route('admin.saas_invoices.upload_proof', $invoice->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                                                @csrf
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-400 mb-1.5">File Bukti Transfer (JPG/PNG)</label>
                                                    <input type="file" name="payment_proof" accept="image/*" required class="w-full text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-500/10 file:text-indigo-400 hover:file:bg-indigo-500/20 cursor-pointer border border-slate-800 p-2 rounded-xl">
                                                </div>
                                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-xl transition-colors">Kirim Bukti Pembayaran</button>
                                            </form>
                                        </div>
                                    </div>
                                @elseif($invoice->status === 'pending_verification')
                                    <span class="text-xs text-amber-400 bg-amber-500/10 px-3 py-1 rounded-full">Menunggu Verifikasi</span>
                                @else
                                    <span class="text-xs text-slate-500">Terima kasih.</span>
                                @endif
                                
                                <a href="{{ route('saas.invoices.print', $invoice->id) }}" target="_blank" title="Cetak Invoice" class="bg-slate-600/10 text-slate-400 border border-slate-500/20 hover:bg-slate-600 hover:text-white px-2 py-1.5 rounded-lg transition-all ml-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-500">
                            Tidak ada riwayat tagihan langganan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
