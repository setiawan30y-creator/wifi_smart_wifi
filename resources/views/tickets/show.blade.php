@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('tickets.index') }}" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h1 class="text-xl font-bold text-slate-100 tracking-tight">Tiket #{{ $ticket->id }} - {{ $ticket->customer?->name ?? $ticket->user->name }}</h1>
            </div>
            <h2 class="text-lg font-semibold text-slate-300 mt-2">{{ $ticket->subject }}</h2>
            <p class="text-xs text-slate-500 mt-1">
                @if($ticket->assignee)
                    Dalam penanganan oleh {{ $ticket->assignee->name }}{{ $ticket->taken_at ? ' sejak ' . $ticket->taken_at->format('d M Y, H:i') : '' }}
                @else
                    Belum diambil teknisi
                @endif
            </p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
            @if($ticket->status === 'open')
                <form action="{{ route('tickets.take', $ticket) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold transition-colors">
                        Ambil Tiket
                    </button>
                </form>
            @elseif($ticket->status === 'in_progress')
                <form action="{{ route('tickets.complete', $ticket) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition-colors">
                        Tandai Selesai
                    </button>
                </form>
            @endif
            <form action="{{ route('tickets.status', $ticket->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <select name="status" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 md:col-span-2">
            <h3 class="text-sm font-bold text-white">Pelanggan Billing</h3>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-xs text-slate-500">Nama</span>
                    <p class="font-semibold text-slate-200">{{ $ticket->customer?->name ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Kontak</span>
                    <p class="font-semibold text-slate-200">{{ $ticket->customer?->phone ?? $ticket->customer?->email ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Paket</span>
                    <p class="font-semibold text-slate-200">{{ $ticket->customer?->package?->name ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">ODP</span>
                    <p class="font-semibold text-slate-200">{{ $ticket->customer?->odp?->name ?? '-' }}</p>
                </div>
            </div>
            @if($ticket->customer?->address)
                <p class="mt-3 text-xs text-slate-400">{{ $ticket->customer->address }}</p>
            @endif
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-white">Status Operasional</h3>
            <div class="mt-3 space-y-2 text-xs text-slate-400">
                <p>Status: <span class="font-bold text-slate-200">{{ str_replace('_', ' ', strtoupper($ticket->status)) }}</span></p>
                <p>Prioritas: <span class="font-bold text-slate-200">{{ strtoupper($ticket->priority) }}</span></p>
                <p>Teknisi: <span class="font-bold text-slate-200">{{ $ticket->assignee?->name ?? 'Belum diambil' }}</span></p>
                @if($ticket->resolved_at)
                    <p>Selesai: <span class="font-bold text-emerald-300">{{ $ticket->resolved_at->format('d M Y, H:i') }}</span></p>
                @endif
            </div>
        </div>
    </div>

    <!-- Chat Interface -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm flex flex-col h-[600px]">
        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            @foreach($ticket->replies as $reply)
                @if($reply->user->role !== 'client')
                    <!-- Admin Message (Right) -->
                    <div class="flex justify-end">
                        <div class="max-w-[80%] md:max-w-[70%]">
                            <div class="flex items-center justify-end gap-2 mb-1.5">
                                <span class="text-[10px] font-medium text-slate-500">{{ $reply->created_at->format('d M Y, H:i') }}</span>
                                <span class="text-xs font-semibold text-slate-300">Anda (Teknisi)</span>
                            </div>
                            <div class="bg-indigo-600 rounded-2xl rounded-tr-sm px-5 py-3.5 text-sm text-white shadow-sm">
                                {!! nl2br(e($reply->message)) !!}
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Client Message (Left) -->
                    <div class="flex justify-start">
                        <div class="max-w-[80%] md:max-w-[70%]">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="text-xs font-semibold text-emerald-400">{{ $reply->user->name }}</span>
                                <span class="text-[10px] font-medium text-slate-500">{{ $reply->created_at->format('d M Y, H:i') }}</span>
                            </div>
                            <div class="bg-slate-800 rounded-2xl rounded-tl-sm px-5 py-3.5 text-sm text-slate-200 shadow-sm">
                                {!! nl2br(e($reply->message)) !!}
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Reply Input Box -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <form action="{{ route('tickets.reply', $ticket->id) }}" method="POST" class="mt-2 flex gap-3">
                @csrf
                <textarea name="message" rows="2" required class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors resize-none" placeholder="Tulis balasan Anda untuk {{ $ticket->user->name }}..."></textarea>
                <button type="submit" class="self-end px-5 py-3 bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-all shadow-lg shadow-indigo-500/20 h-[46px] flex items-center justify-center">
                    Kirim
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto scroll to bottom of chat
    window.onload = function() {
        const messagesArea = document.querySelector('.overflow-y-auto');
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }
</script>
@endsection
