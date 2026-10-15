<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulai Uji Coba Gratis 14 Hari - Billing RTRW Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,0.2) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,0.2) 0, transparent 50%);
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .input-glass {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        .input-glass:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
            outline: none;
        }
    </style>
</head>
<body class="min-h-screen text-slate-300 flex items-center justify-center p-4">

    <div class="max-w-5xl w-full flex flex-col md:flex-row gap-8 items-center">
        
        <!-- Left Side: Copywriting & Value Prop -->
        <div class="flex-1 space-y-6 text-center md:text-left">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold mb-4">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                SISTEM MANAJEMEN RTRW NET #1
            </div>
            
            <h1 class="text-4xl md:text-5xl font-bold text-white leading-tight">
                Kelola RTRW Net Anda <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">Secara Otomatis</span>
            </h1>
            
            <p class="text-slate-400 text-lg leading-relaxed max-w-md mx-auto md:mx-0">
                Tinggalkan cara manual. Mulai dari tagihan otomatis, isolir pelanggan, manajemen Mikrotik, hingga jualan PPOB dalam satu aplikasi cerdas.
            </p>
            
            <div class="pt-4 grid grid-cols-2 gap-4 text-sm font-medium text-slate-300">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Auto-Isolir Tagihan
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Integrasi WhatsApp
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Payment Gateway (Qris)
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Bisa Jualan PPOB
                </div>
            </div>
        </div>

        <!-- Right Side: Registration Form -->
        <div class="w-full max-w-md">
            <div class="glass-panel p-8 rounded-3xl shadow-2xl relative overflow-hidden group">
                <!-- Decorative Glow -->
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 group-hover:opacity-40 transition-opacity duration-700"></div>
                <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-cyan-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 group-hover:opacity-40 transition-opacity duration-700"></div>

                <div class="relative z-10">
                    <h2 class="text-2xl font-bold text-white mb-2">Buat Akun Gratis</h2>
                    <p class="text-sm text-slate-400 mb-6">Mulai masa uji coba 14 hari. Tanpa kartu kredit.</p>

                    @if(session('error'))
                        <div class="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-start gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    <form action="{{ route('register') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wide">Nama Usaha / RTRW Net</label>
                            <input type="text" name="company_name" value="{{ old('company_name') }}" required class="w-full px-4 py-3 rounded-xl input-glass text-sm placeholder-slate-500" placeholder="Misal: Maju Jaya Net">
                            @error('company_name') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wide">Nama Pemilik</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 rounded-xl input-glass text-sm placeholder-slate-500" placeholder="Nama lengkap Anda">
                            @error('name') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wide">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-3 rounded-xl input-glass text-sm placeholder-slate-500" placeholder="admin@email.com">
                                @error('email') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wide">No. WhatsApp</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full px-4 py-3 rounded-xl input-glass text-sm placeholder-slate-500" placeholder="0812...">
                                @error('phone') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wide">Password</label>
                            <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl input-glass text-sm placeholder-slate-500" placeholder="Minimal 8 karakter">
                            @error('password') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wide">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" required class="w-full px-4 py-3 rounded-xl input-glass text-sm placeholder-slate-500" placeholder="Ketik ulang password">
                        </div>

                        <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-400 hover:to-cyan-400 text-white font-bold text-sm shadow-lg shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5 mt-2">
                            Daftar & Mulai Trial Sekarang
                        </button>
                    </form>

                    <p class="text-center text-xs text-slate-400 mt-6">
                        Sudah punya akun? <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300 font-bold">Masuk di sini</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
