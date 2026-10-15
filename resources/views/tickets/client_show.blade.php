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
                <h1 class="text-xl font-bold text-slate-100 tracking-tight">Tiket #{{ $ticket->id }}</h1>
                @if($ticket->status === 'open')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">Menunggu Balasan</span>
                @elseif($ticket->status === 'in_progress')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">Sedang Ditangani</span>
                @elseif($ticket->status === 'resolved' || $ticket->status === 'closed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Selesai</span>
                @endif
            </div>
            <h2 class="text-lg font-semibold text-slate-300 mt-2">{{ $ticket->subject }}</h2>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Chat Interface -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm flex flex-col h-[600px]">
        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            @foreach($ticket->replies as $reply)
                @if($reply->user->role === 'client')
                    <!-- Client Message (Right) -->
                    <div class="flex justify-end">
                        <div class="max-w-[80%] md:max-w-[70%]">
                            <div class="flex items-center justify-end gap-2 mb-1.5">
                                <span class="text-[10px] font-medium text-slate-500">{{ $reply->created_at->format('d M Y, H:i') }}</span>
                                <span class="text-xs font-semibold text-slate-300">Anda</span>
                            </div>
                            <div class="bg-indigo-600 rounded-2xl rounded-tr-sm px-5 py-3.5 text-sm text-white shadow-sm">
                                {!! nl2br(e($reply->message)) !!}
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Admin Message (Left) -->
                    <div class="flex justify-start">
                        <div class="max-w-[80%] md:max-w-[70%]">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="text-xs font-semibold text-emerald-400">Teknisi / Admin</span>
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
            @if(in_array($ticket->status, ['resolved', 'closed']))
                <div class="text-center p-3 text-sm text-slate-400 bg-slate-900 rounded-xl border border-slate-800">
                    Tiket ini telah ditandai selesai. <br>Anda dapat membalas pesan di bawah ini jika masalah belum sepenuhnya teratasi.
                </div>
            @endif
            <form action="{{ route('tickets.reply', $ticket->id) }}" method="POST" class="mt-2 flex gap-3">
                @csrf
                <textarea name="message" rows="2" required class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors resize-none" placeholder="Tulis balasan Anda..."></textarea>
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
