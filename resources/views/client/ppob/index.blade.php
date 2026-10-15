@extends('layouts.client')

@section('content')
<!-- Header -->
<div class="bg-indigo-600 rounded-b-[40px] pt-12 pb-8 px-6 text-white relative shadow-lg">
    <div class="flex items-center gap-4 relative z-10">
        <a href="{{ route('client.dashboard') }}" class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold">Beli Pulsa & PPOB</h1>
            <p class="text-indigo-200 text-xs">Saldo: Rp {{ number_format($customer->balance ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>
</div>

<div class="px-6 mt-6 pb-24">
    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-600 border border-emerald-200 p-4 rounded-2xl mb-6 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 text-red-600 border border-red-200 p-4 rounded-2xl mb-6 text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('client.ppob.buy') }}" method="POST">
        @csrf
        
        <!-- Input Nomor Tujuan -->
        <div class="mb-6">
            <label class="block text-sm font-bold text-slate-700 mb-2">Nomor Tujuan / No Meter PLN</label>
            <input type="text" name="customer_number" required placeholder="Contoh: 081234567890" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-semibold shadow-sm">
        </div>

        <h3 class="text-lg font-bold text-slate-800 mb-4">Pilih Produk</h3>
        
        <div class="space-y-6">
            @forelse($products as $category => $categoryProducts)
                <div>
                    <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-3">{{ $category }}</h4>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($categoryProducts as $product)
                            <label class="cursor-pointer relative">
                                <input type="radio" name="product_id" value="{{ $product->id }}" required class="peer sr-only">
                                <div class="bg-white border-2 border-slate-100 rounded-2xl p-4 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all shadow-sm flex flex-col h-full justify-between">
                                    <span class="font-bold text-slate-700 block mb-2">{{ $product->name }}</span>
                                    <span class="text-indigo-600 font-bold block">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                                    
                                    <div class="absolute top-3 right-3 opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <div class="w-5 h-5 bg-indigo-600 rounded-full flex items-center justify-center text-white">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-slate-50 border border-slate-100 p-6 rounded-2xl text-center">
                    <p class="text-slate-500 text-sm">Belum ada produk PPOB yang tersedia.</p>
                </div>
            @endforelse
        </div>

        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-100 max-w-md mx-auto shadow-[0_-10px_40px_rgba(0,0,0,0.05)] z-50">
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-indigo-600/20 active:scale-[0.98] transition-transform flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Beli Sekarang
            </button>
        </div>
    </form>
</div>
@endsection
