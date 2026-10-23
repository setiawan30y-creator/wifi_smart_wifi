<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ $title ?? 'Aplikasi Pelanggan' }}</title>
    
    <!-- Google Fonts: Inter for that modern Flutter/Native feel -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Hide scrollbar for a native app feel */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Smooth transitions */
        .tap-highlight-transparent { -webkit-tap-highlight-color: transparent; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.theme')
</head>
<body class="bg-gray-100 antialiased selection:bg-indigo-500 selection:text-white tap-highlight-transparent">

    <!-- Mobile Device Container -->
    <div class="relative max-w-md mx-auto min-h-screen bg-slate-50 shadow-2xl overflow-hidden flex flex-col">
        
        <!-- Main Content Area (Scrollable) -->
        <main class="flex-1 overflow-y-auto no-scrollbar pb-24 relative">
            @yield('content')
        </main>

        <!-- Bottom Navigation Bar (Fixed) -->
        <nav class="absolute bottom-0 w-full bg-white border-t border-slate-100 rounded-t-3xl shadow-[0_-10px_40px_rgba(0,0,0,0.03)] pb-safe pt-2 px-6">
            <div class="flex justify-between items-center h-16">
                <!-- Beranda -->
                <a href="#" class="flex flex-col items-center justify-center w-14 group">
                    <div class="w-10 h-10 flex items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 transition-all duration-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3zm0 2.83l7 7V20h-4v-6H9v6H5v-7.17l7-7z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-semibold text-indigo-600 mt-1">Beranda</span>
                </a>

                <!-- Riwayat -->
                <a href="#" class="flex flex-col items-center justify-center w-14 group">
                    <div class="w-10 h-10 flex items-center justify-center rounded-2xl text-slate-400 group-hover:bg-slate-50 group-hover:text-indigo-500 transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-medium text-slate-400 mt-1">Tagihan</span>
                </a>

                <!-- PPOB (Floating Center Button Style) -->
                <div class="relative -top-5">
                    <a href="#" class="flex items-center justify-center w-14 h-14 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white shadow-xl shadow-indigo-500/40 transform hover:scale-105 transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                    </a>
                </div>

                <!-- Bantuan -->
                <a href="#" class="flex flex-col items-center justify-center w-14 group">
                    <div class="w-10 h-10 flex items-center justify-center rounded-2xl text-slate-400 group-hover:bg-slate-50 group-hover:text-indigo-500 transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-medium text-slate-400 mt-1">Bantuan</span>
                </a>

                <!-- Akun -->
                <a href="#" class="flex flex-col items-center justify-center w-14 group">
                    <div class="w-10 h-10 flex items-center justify-center rounded-2xl text-slate-400 group-hover:bg-slate-50 group-hover:text-indigo-500 transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-medium text-slate-400 mt-1">Akun</span>
                </a>
            </div>
        </nav>
    </div>

</body>
</html>
