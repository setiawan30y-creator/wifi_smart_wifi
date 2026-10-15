@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-100 tracking-tight">Tiket Bantuan</h1>
            <p class="text-sm text-slate-400 mt-1">Sampaikan keluhan atau masalah koneksi Anda di sini.</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-all shadow-lg shadow-indigo-500/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Buat Tiket Baru
        </a>
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
                        <th class="p-4">Judul Masalah</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Tanggal</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-sm">
                    @forelse ($tickets as $ticket)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-4 font-medium text-slate-300">#{{ $ticket->id }}</td>
                            <td class="p-4 text-slate-200">
                                <div class="font-semibold">{{ $ticket->subject }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Prioritas: 
                                    <span class="@if($ticket->priority == 'high') text-rose-400 @elseif($ticket->priority == 'medium') text-amber-400 @else text-emerald-400 @endif capitalize">{{ $ticket->priority }}</span>
                                </div>
                            </td>
                            <td class="p-4">
                                @if($ticket->status === 'open')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">Menunggu Balasan</span>
                                @elseif($ticket->status === 'in_progress')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">Sedang Ditangani</span>
                                @elseif($ticket->status === 'resolved' || $ticket->status === 'closed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Selesai</span>
                                @endif
                            </td>
                            <td class="p-4 text-slate-400 text-xs">
                                {{ $ticket->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="inline-flex items-center px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-lg transition-colors border border-slate-700">
                                    Lihat Pesan
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500">
                                Anda belum memiliki riwayat tiket bantuan.
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
