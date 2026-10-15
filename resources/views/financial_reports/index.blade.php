@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Laporan Keuangan</h1>
            <p class="text-slate-400 mt-1">Pantau performa bisnis, rasio pengeluaran, laba bersih, dan tren pemasukan secara realtime.</p>
        </div>
        
        <!-- Date Filter Form -->
        <form action="{{ route('financial-reports.index') }}" method="GET" class="bg-slate-900/60 border border-slate-800/80 p-3 rounded-2xl flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider pl-1">Periode:</span>
                <input type="date" name="start_date" value="{{ $startDateInput }}" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                <span class="text-slate-500 text-xs">-</span>
                <input type="date" name="end_date" value="{{ $endDateInput }}" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-650 hover:bg-indigo-550 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-indigo-600/10">
                Terapkan
            </button>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Income KPI -->
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 flex items-center justify-between relative overflow-hidden group">
            <div class="absolute -right-8 -top-8 w-20 h-20 rounded-full bg-emerald-500/5 blur-2xl group-hover:bg-emerald-500/10 transition-colors"></div>
            <div>
                <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider block">Pemasukan (Lunas)</span>
                <h3 class="text-2xl font-extrabold text-emerald-400 mt-1.5">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-slate-400 mt-1 block">Dari Invoice Terbayar</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
        </div>

        <!-- Expense KPI -->
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 flex items-center justify-between relative overflow-hidden group">
            <div class="absolute -right-8 -top-8 w-20 h-20 rounded-full bg-rose-500/5 blur-2xl group-hover:bg-rose-500/10 transition-colors"></div>
            <div>
                <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider block">Total Pengeluaran</span>
                <h3 class="text-2xl font-extrabold text-rose-450 mt-1.5">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-slate-400 mt-1 block">Semua Kategori Kas</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-450 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
                </svg>
            </div>
        </div>

        <!-- Net Profit KPI -->
        @php
            $isProfitPositive = $netProfit >= 0;
        @endphp
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 flex items-center justify-between relative overflow-hidden group">
            <div class="absolute -right-8 -top-8 w-20 h-20 rounded-full {{ $isProfitPositive ? 'bg-indigo-500/5' : 'bg-red-500/5' }} blur-2xl group-hover:bg-indigo-500/10 transition-colors"></div>
            <div>
                <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider block">Laba Bersih</span>
                <h3 class="text-2xl font-extrabold {{ $isProfitPositive ? 'text-indigo-400' : 'text-rose-550' }} mt-1.5">Rp {{ number_format($netProfit, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-slate-400 mt-1 block">Pendapatan - Biaya</span>
            </div>
            <div class="w-11 h-11 rounded-xl {{ $isProfitPositive ? 'bg-indigo-500/10 border border-indigo-500/20 text-indigo-400' : 'bg-red-500/10 border border-red-500/20 text-rose-450' }} flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Profit Margin KPI -->
        <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-2xl p-5 flex items-center justify-between relative overflow-hidden group">
            <div class="absolute -right-8 -top-8 w-20 h-20 rounded-full bg-cyan-500/5 blur-2xl group-hover:bg-cyan-500/10 transition-colors"></div>
            <div>
                <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider block">Margin Keuntungan</span>
                <h3 class="text-2xl font-extrabold text-cyan-400 mt-1.5">{{ number_format($profitMargin, 1, ',', '.') }}%</h3>
                <span class="text-[10px] text-slate-400 mt-1 block">Rasio Efisiensi Bersih</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Monthly Summary Table & Categories breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Monthly summary table -->
        <div class="bg-slate-900/30 border border-slate-800/80 backdrop-blur-md rounded-3xl p-6">
            <h3 class="font-bold text-slate-200 mb-5">Tren Rekap Bulanan (6 Bulan Terakhir)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800/60 text-slate-450 text-xs font-bold uppercase tracking-wider">
                            <th class="pb-3 px-3">Bulan</th>
                            <th class="pb-3 px-3 text-right">Pemasukan</th>
                            <th class="pb-3 px-3 text-right">Pengeluaran</th>
                            <th class="pb-3 px-3 text-right">Laba Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850">
                        @foreach($monthlyBreakdown as $row)
                            <tr class="hover:bg-slate-850/10">
                                <td class="py-3 px-3 text-sm font-semibold text-slate-300 font-mono">{{ $row['label'] }}</td>
                                <td class="py-3 px-3 text-right text-sm text-emerald-400 font-mono">Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right text-sm text-rose-400 font-mono">Rp {{ number_format($row['expense'], 0, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right text-sm font-bold font-mono {{ $row['profit'] >= 0 ? 'text-indigo-400' : 'text-rose-500' }}">
                                    Rp {{ number_format($row['profit'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expenses category breakdown -->
        <div class="bg-slate-900/30 border border-slate-800/80 backdrop-blur-md rounded-3xl p-6 space-y-5">
            <h3 class="font-bold text-slate-200">Distribusi Pengeluaran per Kategori</h3>
            
            <div class="space-y-4 pt-1">
                @forelse($categoryBreakdown as $breakdown)
                    @php
                        $barColor = match($breakdown['category']) {
                            'Pembelian Perangkat' => 'bg-indigo-500 shadow-[0_0_8px_#6366f1]',
                            'Operasional' => 'bg-cyan-500 shadow-[0_0_8px_#06b6d4]',
                            'Gaji/Honor' => 'bg-amber-500 shadow-[0_0_8px_#f59e0b]',
                            'Perbaikan' => 'bg-emerald-500 shadow-[0_0_8px_#10b981]',
                            'ISP' => 'bg-rose-500 shadow-[0_0_8px_#f43f5e]',
                            'Listrik' => 'bg-yellow-500 shadow-[0_0_8px_#eab308]',
                            'Perlengkapan' => 'bg-purple-500 shadow-[0_0_8px_#a855f7]',
                            default => 'bg-slate-500',
                        };
                    @endphp
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-300">{{ $breakdown['category'] }}</span>
                            <span class="text-slate-400 font-mono">{{ $breakdown['percentage'] }}% <span class="text-[10px] text-slate-550 ml-1.5">(Rp {{ number_format($breakdown['amount'], 0, ',', '.') }})</span></span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-950/60 overflow-hidden border border-slate-850">
                            <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $breakdown['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 text-slate-500 text-xs">
                        Tidak ada pengeluaran terdeteksi pada rentang waktu ini.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Transactions Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Invoices Paid -->
        <div class="bg-slate-900/30 border border-slate-800/80 backdrop-blur-md rounded-3xl p-6">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-4 mb-4">
                <h3 class="font-bold text-slate-200">Pembayaran Masuk Terbaru</h3>
                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold text-emerald-450 bg-emerald-500/10 border border-emerald-500/20 uppercase tracking-wider">Lunas</span>
            </div>
            
            <div class="space-y-3">
                @forelse($recentInvoices as $invoice)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/30 border border-slate-850/50 hover:border-slate-800 transition-colors">
                        <div>
                            <span class="text-xs font-bold text-slate-200">{{ $invoice->customer ? $invoice->customer->name : 'Pelanggan Dihapus' }}</span>
                            <div class="text-[10px] text-slate-450 font-mono mt-0.5">{{ $invoice->invoice_number }} &bull; {{ $invoice->paid_at->translatedFormat('d M Y H:i') }}</div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-emerald-400 font-mono">+ Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                            <div class="text-[9px] text-slate-450 font-medium mt-0.5">{{ $invoice->payment_method ?? 'Transfer' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-500 text-xs italic">
                        Belum ada pembayaran masuk terdeteksi dalam rentang waktu terpilih.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Expenses -->
        <div class="bg-slate-900/30 border border-slate-800/80 backdrop-blur-md rounded-3xl p-6">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-4 mb-4">
                <h3 class="font-bold text-slate-200">Log Pengeluaran Kas Terbaru</h3>
                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold text-rose-450 bg-rose-500/10 border border-rose-500/20 uppercase tracking-wider">Pengeluaran</span>
            </div>
            
            <div class="space-y-3">
                @forelse($recentExpenses as $expense)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/30 border border-slate-850/50 hover:border-slate-800 transition-colors">
                        <div>
                            <span class="text-xs font-bold text-slate-200">{{ $expense->title }}</span>
                            <div class="text-[10px] text-slate-450 font-mono mt-0.5">{{ $expense->category }} &bull; {{ $expense->date->translatedFormat('d M Y') }}</div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-rose-450 font-mono">- Rp {{ number_format($expense->amount, 0, ',', '.') }}</span>
                            @if($expense->quantity)
                                <div class="text-[9px] text-slate-450 font-medium mt-0.5">{{ $expense->quantity }} Unit</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-500 text-xs italic">
                        Belum ada pengeluaran kas terdeteksi dalam rentang waktu terpilih.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
