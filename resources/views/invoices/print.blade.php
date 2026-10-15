<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KUITANSI - {{ $invoice->invoice_number }}</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Courier+Prime&display=swap" rel="stylesheet">
    
    <!-- Vite Styles -->
    @vite(['resources/css/app.css'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
            }
            .no-print {
                display: none !important;
            }
            .print-border {
                border: 1px solid #cbd5e1 !important;
            }
        }
    </style>
</head>
<body class="min-h-screen py-10 px-4 flex items-center justify-center">

    <div class="max-w-2xl w-full bg-white print-border border border-slate-200 shadow-xl rounded-3xl p-8 sm:p-10 relative overflow-hidden">
        
        <!-- Background accents (screen only) -->
        <div class="no-print absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl"></div>
        <div class="no-print absolute bottom-0 left-0 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl"></div>

        <!-- Print & Back Actions -->
        <div class="no-print flex justify-between items-center mb-8 border-b border-slate-100 pb-5">
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali</span>
            </a>
            
            <button onclick="window.print()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4.5 py-2.5 rounded-xl shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2zm5-17V3a1 1 0 011-1h2a1 1 0 011 1v3M5 12h.01M19 12h.01" />
                </svg>
                <span>Cetak Kuitansi</span>
            </button>
        </div>

        <!-- Receipt Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pb-8 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shadow-md shadow-indigo-600/10">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="font-extrabold text-base tracking-tight">BILLING RT/RW</span>
                </div>
                <p class="text-xs text-slate-500 mt-2.5">
                    Layanan Internet & Iuran Warga Mandiri<br>
                    Wilayah Kepengurusan RT/RW Setempat
                </p>
            </div>
            
            <div class="text-left sm:text-right">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wider">
                    LUNAS / PAID
                </span>
                <div class="mt-3 font-mono font-bold text-slate-900 text-sm">
                    No: {{ $invoice->invoice_number }}
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">
                    Periode: {{ \Carbon\Carbon::create(2026, $invoice->month, 1)->translatedFormat('F') }} {{ $invoice->year }}
                </div>
            </div>
        </div>

        <!-- Receipt Body: Customer Info -->
        <div class="py-8 grid grid-cols-1 sm:grid-cols-2 gap-6 border-b border-slate-100 text-sm">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">DIBAYAR OLEH (PELANGGAN)</span>
                <div class="font-bold text-slate-800 mt-1.5">{{ $invoice->customer->name }}</div>
                <div class="text-slate-600 mt-1 font-medium text-xs">{{ $invoice->customer->phone }}</div>
                <p class="text-slate-500 mt-1.5 leading-relaxed text-xs">
                    {{ $invoice->customer->address }}
                </p>
            </div>
            
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">DETAIL PEMBAYARAN</span>
                <div class="mt-2 flex flex-col gap-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Metode Bayar:</span>
                        <span class="font-bold text-slate-800">{{ $invoice->payment_method }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tanggal Bayar:</span>
                        <span class="font-bold text-slate-800">{{ $invoice->paid_at ? $invoice->paid_at->translatedFormat('d F Y - H:i') : '-' }} WIB</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Paket Layanan:</span>
                        <span class="font-bold text-slate-800">{{ $invoice->customer->package->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Kecepatan:</span>
                        <span class="font-bold text-indigo-600">{{ $invoice->customer->package->speed ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Total -->
        <div class="py-8 border-b border-slate-100 flex flex-col items-end">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">TOTAL PEMBAYARAN</span>
            <h4 class="text-3xl font-black text-slate-900 mt-1.5">
                Rp {{ number_format($invoice->amount, 0, ',', '.') }}
            </h4>
            <p class="text-[10px] text-slate-400 mt-1 italic">
                Terbilang: # {{ CoretanTerbilang($invoice->amount) }} Rupiah #
            </p>
        </div>

        <!-- Receipt Signatures -->
        <div class="pt-8 grid grid-cols-2 text-center text-xs gap-6">
            <div>
                <span class="text-slate-400">Pembayar,</span>
                <div class="h-16"></div>
                <div class="font-bold text-slate-800 border-t border-slate-200 pt-1.5 mx-auto max-w-[150px]">
                    {{ $invoice->customer->name }}
                </div>
                <span class="text-[9px] text-slate-400 font-medium">Pelanggan</span>
            </div>
            
            <div>
                <span class="text-slate-400">Penerima (Petugas),</span>
                <div class="h-16 flex items-center justify-center">
                    <!-- stamp-like badge -->
                    <div class="border-2 border-emerald-500/30 text-emerald-500/80 font-bold uppercase px-3 py-1 rounded text-[10px] tracking-widest transform -rotate-6 scale-95 border-dashed">
                        Terbayar
                    </div>
                </div>
                <div class="font-bold text-slate-800 border-t border-slate-200 pt-1.5 mx-auto max-w-[150px]">
                    Admin RT/RW
                </div>
                <span class="text-[9px] text-slate-400 font-medium">Pengurus Wilayah</span>
            </div>
        </div>

    </div>

</body>
</html>

@php
// Simple helper for Indonesian Terbilang (number to words) spelling
function CoretanTerbilang($amount) {
    $number = abs($amount);
    $words = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
    $result = "";
    
    if ($number < 12) {
        $result = " " . $words[$number];
    } else if ($number < 20) {
        $result = CoretanTerbilang($number - 10) . " Belas";
    } else if ($number < 100) {
        $result = CoretanTerbilang(floor($number / 10)) . " Puluh" . CoretanTerbilang($number % 10);
    } else if ($number < 200) {
        $result = " Seratus" . CoretanTerbilang($number - 100);
    } else if ($number < 1000) {
        $result = CoretanTerbilang(floor($number / 100)) . " Ratus" . CoretanTerbilang($number % 100);
    } else if ($number < 2000) {
        $result = " Seribu" . CoretanTerbilang($number - 1000);
    } else if ($number < 1000000) {
        $result = CoretanTerbilang(floor($number / 1000)) . " Ribu" . CoretanTerbilang($number % 1000);
    } else if ($number < 1000000000) {
        $result = CoretanTerbilang(floor($number / 1000000)) . " Juta" . CoretanTerbilang($number % 1000000);
    }
    
    return trim($result);
}
@endphp
