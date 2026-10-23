<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - 403</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
        }
    </style>

    <!-- Vite Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.theme')
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center antialiased relative overflow-hidden p-4">

    <!-- Decorative Glow Circles -->
    <div class="absolute top-1/4 left-1/4 w-[400px] h-[400px] bg-rose-600/5 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-indigo-600/5 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- Main Container -->
    <div class="w-full max-w-md bg-slate-900/40 border border-slate-800/80 backdrop-blur-xl rounded-3xl p-8 text-center relative z-10 shadow-2xl">
        
        <!-- Warning Icon -->
        <div class="w-20 h-20 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-rose-950/20">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <div>
            <h1 class="text-4xl font-black text-rose-500 font-mono tracking-tight">403</h1>
            <h2 class="text-xl font-bold text-white mt-2">Akses Ditolak / Forbidden</h2>
            <p class="text-sm text-slate-400 mt-3.5 leading-relaxed">Maaf, akun Anda tidak memiliki hak akses yang diperlukan untuk membuka halaman atau modul ini.</p>
        </div>

        <!-- Back Button -->
        <div class="mt-8 flex flex-col gap-3">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 font-bold text-sm text-white shadow-lg shadow-indigo-600/20 transition-all duration-300 transform active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Dashboard</span>
            </a>
            
            <form action="{{ url('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="w-full text-xs font-semibold text-slate-500 hover:text-slate-300 transition-colors py-2">
                    Keluar / Logout Akun
                </button>
            </form>
        </div>
    </div>

</body>
</html>
