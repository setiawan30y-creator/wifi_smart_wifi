<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Billing RT/RW</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
        }
        /* Fix Webkit Autofill Background */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px var(--neutral-950) inset !important;
            -webkit-text-fill-color: var(--neutral-100) !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>

    <!-- Vite Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.theme')
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center antialiased relative overflow-hidden select-none p-4">

    <!-- Decorative Glow Circles -->
    <div class="absolute top-1/4 left-1/4 w-[400px] h-[400px] bg-indigo-600/20 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-purple-600/20 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- Main Container -->
    <div class="w-full max-w-md bg-slate-900/60 border border-slate-800/80 backdrop-blur-2xl rounded-3xl p-8 sm:p-10 relative z-10 shadow-2xl shadow-slate-950/80">
        
        <!-- App Logo Header -->
        <div class="text-center space-y-4 mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 mx-auto transform hover:scale-105 transition-transform duration-300">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-white leading-tight">Billing RT/RW</h1>
                <p class="text-sm text-slate-400 mt-1.5 font-medium">Masuk untuk mengelola tagihan dan jaringan Anda</p>
            </div>
        </div>

        <!-- Alert messages -->
        @if (session('info'))
            <div class="mb-6 p-4 rounded-2xl border border-indigo-500/30 bg-indigo-500/10 flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-indigo-300 text-sm font-medium leading-relaxed">{{ session('info') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 flex items-start gap-3">
                <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div class="text-rose-300 text-sm font-medium leading-relaxed">{{ $errors->first() }}</div>
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ url('login') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Email Input -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Alamat E-mail</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500 group-focus-within:text-indigo-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                        </svg>
                    </div>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full bg-slate-950/50 border border-slate-700/60 rounded-2xl pl-11 pr-4 py-3.5 text-base text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:bg-slate-950 transition-all font-sans" placeholder="admin@rtrw.net">
                </div>
            </div>

            <!-- Password Input -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Kata Sandi</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500 group-focus-within:text-indigo-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input type="password" name="password" id="password" required class="w-full bg-slate-950/50 border border-slate-700/60 rounded-2xl pl-11 pr-12 py-3.5 text-base text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:bg-slate-950 transition-all" placeholder="••••••••">
                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-indigo-400 transition-colors focus:outline-none">
                        <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none group">
                    <div class="relative flex items-center justify-center">
                        <input type="checkbox" name="remember" class="peer w-5 h-5 rounded border-slate-700 bg-slate-950/50 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-slate-900 transition-colors cursor-pointer appearance-none checked:bg-indigo-500 checked:border-indigo-500">
                        <svg class="w-3 h-3 text-white absolute pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <span class="text-sm text-slate-400 font-medium group-hover:text-slate-300 transition-colors">Ingat Saya</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                    Lupa Sandi?
                </a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-400 text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 focus:ring-4 focus:ring-indigo-500/30 flex justify-center items-center gap-2 group mt-2">
                <span>Masuk ke Dashboard</span>
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-10 border-t border-slate-800/60 pt-6 text-center">
            <p class="text-[10px] font-bold text-slate-600 tracking-[0.2em] uppercase">Billing RT/RW Premium v1.0</p>
        </div>
    </div>

    <!-- Toggle Password Script -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            if (type === 'text') {
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                `;
            } else {
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
        });
    </script>
</body>
</html>
