@extends('layouts.app')

@section('title', 'Riwayat Transaksi PPOB')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white mb-2">Riwayat Transaksi PPOB</h1>
            <p class="text-slate-400">Pantau seluruh aktivitas pembelian pulsa/PPOB pelanggan Anda.</p>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl shadow-slate-950">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="py-4 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Tanggal</th>
                        <th class="py-4 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Pelanggan</th>
                        <th class="py-4 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Produk / Nomor</th>
                        <th class="py-4 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Suplier</th>
                        <th class="py-4 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Modal</th>
                        <th class="py-4 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Harga Jual</th>
                        <th class="py-4 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Laba</th>
                        <th class="py-4 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-center">Status</th>
                        <th class="py-4 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">SN / Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($transactions as $trx)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-4 px-4 text-sm text-slate-300">
                                {{ $trx->created_at->format('d M Y') }}<br>
                                <span class="text-xs text-slate-500">{{ $trx->created_at->format('H:i') }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-sm font-medium text-white">{{ $trx->customer->name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $trx->customer->phone ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-sm font-bold text-indigo-400">{{ $trx->product->name ?? '-' }}</div>
                                <div class="text-xs text-slate-300">{{ $trx->customer_number }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-xs px-2.5 py-1 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $trx->supplier->name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-sm text-right text-rose-400">
                                Rp {{ number_format($trx->buy_price, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-4 text-sm text-right text-emerald-400 font-medium">
                                Rp {{ number_format($trx->sell_price, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-4 text-sm text-right text-indigo-400 font-bold">
                                Rp {{ number_format($trx->sell_price - $trx->buy_price, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-4 text-center">
                                @if($trx->status === 'success')
                                    <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-full text-xs font-medium">Berhasil</span>
                                @elseif($trx->status === 'pending')
                                    <span class="px-2.5 py-1 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-full text-xs font-medium">Pending</span>
                                @else
                                    <span class="px-2.5 py-1 bg-red-500/10 text-red-400 border border-red-500/20 rounded-full text-xs font-medium">Gagal</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-sm text-slate-400">
                                {{ $trx->sn ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500">Belum ada transaksi PPOB.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
