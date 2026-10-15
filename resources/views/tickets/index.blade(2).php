@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-100 tracking-tight">Tiket Gangguan (Helpdesk)</h1>
            <p class="text-sm text-slate-400 mt-1">Kelola laporan dan keluhan dari pelanggan Anda.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <a href="{{ route('tickets.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-indigo-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Tiket
            </a>
            <form method="GET" action="{{ route('tickets.index') }}" class="flex items-center gap-2">
            <select name="status" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                <option value="">Semua Status</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Menunggu Balasan (Open)</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Sedang Ditangani</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Selesai</option>
            </select>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tickets List -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-950/50 border-b border-slate-800 text-xs uppercase tracking-wider text-slate-400 font-semibold">
                        <th class="p-4">ID</th>
                        <th class="p-4">Pelanggan</th>
                        <th class="p-4">Judul Masalah</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Penanganan</th>
                        <th class="p-4">Tanggal Masuk</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-sm">
                    @forelse ($tickets as $ticket)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-4 font-medium text-slate-300">#{{ $ticket->id }}</td>
                            <td class="p-4">
                                <div class="font-semibold text-slate-200">{{ $ticket->customer?->name ?? $ticket->user->name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $ticket->customer?->phone ?? $ticket->user->phone ?? $ticket->user->email }}</div>
                                @if($ticket->customer?->address)
                                    <div class="text-[11px] text-slate-600 mt-0.5">{{ Str::limit($ticket->customer->address, 36) }}</div>
                                @endif
                            </td>
                            <td class="p-4 text-slate-200">
                                <div class="font-semibold">{{ $ticket->subject }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Prioritas: 
                                    <span class="@if($ticket->priority == 'high') text-rose-400 @elseif($ticket->priority == 'medium') text-amber-400 @else text-emerald-400 @endif capitalize">{{ $ticket->priority }}</span>
                                </div>
                            </td>
                            <td class="p-4">
                                @if($ticket->status === 'open')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">Open (Baru)</span>
                                @elseif($ticket->status === 'in_progress')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">Sedang Ditangani</span>
                                @elseif($ticket->status === 'resolved' || $ticket->status === 'closed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Selesai</span>
                                @endif
                            </td>
                            <td class="p-4 text-xs">
                                @if($ticket->assignee)
                                    <div class="font-semibold text-slate-200">{{ $ticket->assignee->name }}</div>
                                    <div class="text-slate-500 mt-0.5">{{ $ticket->taken_at?->format('d M Y, H:i') }}</div>
                                @else
                                    <span class="text-slate-500">Belum diambil</span>
                                @endif
                            </td>
                            <td class="p-4 text-slate-400 text-xs">
                                {{ $ticket->created_at->format('d M Y, H:i') }}<br>
                                <span class="text-slate-500">{{ $ticket->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="p-4 text-right space-x-2">
                                @if($ticket->status === 'open')
                                    <form action="{{ route('tickets.take', $ticket) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-500/10 hover:bg-blue-500 text-blue-400 hover:text-white text-xs font-medium rounded-lg transition-colors border border-blue-500/20">
                                            Ambil
                                        </button>
                                    </form>
                                @elseif($ticket->status === 'in_progress')
                                    <form action="{{ route('tickets.complete', $ticket) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white text-xs font-medium rounded-lg transition-colors border border-emerald-500/20">
                                            Selesai
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-medium rounded-lg transition-colors shadow-sm">
                                    Balas Pesan
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                Tidak ada tiket gangguan saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection
