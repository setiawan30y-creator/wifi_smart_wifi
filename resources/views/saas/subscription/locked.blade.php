<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Langganan Diperlukan - Billing RTRW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: var(--neutral-900); }
    </style>
</head>
<body class="min-h-screen text-slate-300 flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl text-center relative overflow-hidden">
        <!-- Glow -->
        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-rose-500 to-orange-500"></div>
        
        <div class="w-20 h-20 mx-auto bg-rose-500/10 border border-rose-500/20 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-white mb-2">Masa Uji Coba Berakhir</h1>
        <p class="text-slate-400 text-sm leading-relaxed mb-8">
            Halo <b>{{ Auth::user()->name }}</b>, masa uji coba 14 hari untuk <b>{{ Auth::user()->tenant->name ?? 'Perusahaan Anda' }}</b> telah berakhir. Silakan berlangganan untuk membuka kembali akses ke seluruh fitur cerdas kami.
        </p>

        <div class="bg-slate-950 rounded-xl p-4 mb-8 border border-slate-800 text-left">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Paket Langganan</h3>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-slate-300 font-medium">Paket SaaS Basic</span>
                <span class="text-sm text-indigo-400 font-bold">Rp 150.000 <span class="text-[10px] text-slate-500 font-normal">/Bulan</span></span>
            </div>
            <ul class="space-y-1.5 mt-3 border-t border-slate-800 pt-3">
                <li class="text-xs text-slate-400 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Manajemen Pelanggan Tanpa Batas
                </li>
                <li class="text-xs text-slate-400 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Integrasi WhatsApp Gateway
                </li>
                <li class="text-xs text-slate-400 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Akses Fitur Jualan PPOB
                </li>
            </ul>
        </div>

        <div class="flex flex-col gap-3">
            <a href="https://wa.me/6281234567890?text=Halo%20Admin,%20saya%20ingin%20berlangganan%20Aplikasi%20Billing%20RTRW" target="_blank" class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-white font-bold text-sm shadow-lg shadow-emerald-500/25 transition-all flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.573-.187-.981-.342-1.714-.652-2.822-2.427-2.908-2.544-.087-.117-.693-.923-.693-1.761 0-.837.437-1.246.59-1.42.152-.174.331-.218.441-.218.11 0 .22.001.316.005.093.004.22-.036.345.265.127.306.435 1.066.474 1.144.039.078.065.17.011.277-.054.107-.082.174-.163.269-.082.095-.172.203-.244.288-.08.093-.164.195-.069.359.094.164.42 .696.903 1.127.625.556 1.144.726 1.309.805.164.078.261.065.359-.045.097-.111.42-.486.533-.653.112-.167.224-.139.375-.084.152.056.963.454 1.127.536.164.083.274.125.314.195.04.07.04.405-.104.81z"/></svg>
                Hubungi Admin via WhatsApp
            </a>
            
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-sm transition-all">
                    Keluar / Logout
                </button>
            </form>
        </div>
    </div>

</body>
</html>
