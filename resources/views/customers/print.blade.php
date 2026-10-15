<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>DATA PELANGGAN - {{ $customer->name }}</title>
	
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

	<div class="max-w-3xl w-full bg-white print-border border border-slate-200 shadow-xl rounded-3xl p-8 sm:p-10 relative overflow-hidden">
		
		<!-- Background accents (screen only) -->
		<div class="no-print absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl"></div>
		<div class="no-print absolute bottom-0 left-0 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl"></div>

		<!-- Actions -->
		<div class="no-print flex justify-between items-center mb-8 border-b border-slate-100 pb-5">
			<a href="{{ route('customers.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
				</svg>
				<span>Kembali</span>
			</a>
			
			<button onclick="window.print()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4.5 py-2.5 rounded-xl shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-300">
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2zm5-17V3a1 1 0 011-1h2a1 1 0 011 1v3M5 12h.01M19 12h.01" />
				</svg>
				<span>Cetak Data Pelanggan</span>
			</button>
		</div>

		<!-- Header -->
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
					Format Cetak Profil Pelanggan & Data Teknis<br>
					Tanggal Cetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
				</p>
			</div>
			
			<div class="text-left sm:text-right">
				<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wider">
					DOKUMEN TEKNIS
				</span>
				<div class="mt-3 font-mono font-bold text-slate-900 text-sm">
					ID: CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}
				</div>
				<div class="text-[11px] text-slate-500 mt-0.5">
					Status: {{ $customer->status === 'active' ? 'Aktif' : 'Nonaktif' }}
				</div>
			</div>
		</div>

		<!-- Body: Grid Details -->
		<div class="py-8 grid grid-cols-1 md:grid-cols-2 gap-8 border-b border-slate-100 text-sm">
			<!-- Section 1: Personal Info -->
			<div class="space-y-4">
				<h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider border-b border-slate-100 pb-2">1. DATA DIRI & LAYANAN</h4>
				<div class="grid grid-cols-3 gap-y-2.5 gap-x-2 text-xs">
					<div class="text-slate-450 font-medium">Nama Lengkap</div>
					<div class="col-span-2 text-slate-800 font-semibold">: {{ $customer->name }}</div>
					
					<div class="text-slate-450 font-medium">No. Telepon</div>
					<div class="col-span-2 text-slate-800 font-semibold">: {{ $customer->phone }}</div>
					
					<div class="text-slate-450 font-medium">Email</div>
					<div class="col-span-2 text-slate-600">: {{ $customer->email ?? '-' }}</div>
					
					<div class="text-slate-450 font-medium">Paket Layanan</div>
					<div class="col-span-2 text-emerald-600 font-bold">
						: @if($customer->package)
							{{ $customer->package->name }} (Rp {{ number_format($customer->package->price, 0, ',', '.') }}/bln)
						@else
							-
						@endif
					</div>

					<div class="text-slate-450 font-medium">Tanggal Pasang</div>
					<div class="col-span-2 text-slate-700">
						: {{ $customer->tanggal_pasang ? \Carbon\Carbon::parse($customer->tanggal_pasang)->translatedFormat('d F Y') : '-' }}
					</div>
					
					<div class="text-slate-450 font-medium">Siklus Tagihan</div>
					<div class="col-span-2 text-slate-700 font-semibold">: Tiap tanggal {{ $customer->billing_day }}</div>
				</div>
			</div>

			<!-- Section 2: Technical Info -->
			<div class="space-y-4">
				<h4 class="text-xs font-bold text-purple-650 uppercase tracking-wider border-b border-slate-100 pb-2">2. DETAIL TEKNIS & PPPoE</h4>
				<div class="grid grid-cols-3 gap-y-2.5 gap-x-2 text-xs">
					<div class="text-slate-455 font-medium">User PPPoE</div>
					<div class="col-span-2 text-purple-700 font-mono font-bold">: {{ $customer->pppoe_username ?? '-' }}</div>
					
					<div class="text-slate-455 font-medium">Password PPPoE</div>
					<div class="col-span-2 text-purple-700 font-mono">: {{ $customer->pppoe_password ?? '-' }}</div>

					<div class="text-slate-455 font-medium">Jalur Koneksi</div>
					<div class="col-span-2 text-slate-700 font-semibold">: {{ $customer->jalur ?? '-' }}</div>

					<div class="text-slate-455 font-medium">Model Perangkat</div>
					<div class="col-span-2 text-slate-700 font-semibold">: {{ $customer->perangkat ?? '-' }}</div>
					
					<div class="text-slate-455 font-medium">ODP Terhubung</div>
					<div class="col-span-2 text-indigo-600 font-semibold">: {{ $customer->odp ? $customer->odp->name : 'Tidak Terhubung' }}</div>
					
					<div class="text-slate-455 font-medium">Status Rumah</div>
					<div class="col-span-2 text-slate-700">
						: {{ $customer->status_rumah === 'ngontrak' ? 'Ngontrak (Pemilik: ' . ($customer->pemilik_rumah ?? '-') . ')' : 'Milik Sendiri' }}
					</div>
				</div>
			</div>
		</div>

		<!-- Address Details -->
		<div class="py-6 border-b border-slate-100 text-xs">
			<div class="grid grid-cols-6 gap-2">
				<div class="text-slate-450 font-bold uppercase tracking-wider col-span-1">Alamat Lengkap</div>
				<div class="col-span-5 text-slate-700 leading-relaxed font-semibold">
					: {{ $customer->address }}
				</div>
			</div>
			@if($customer->latitude)
			<div class="grid grid-cols-6 gap-2 mt-2">
				<div class="text-slate-450 font-bold uppercase tracking-wider col-span-1">Koordinat GPS</div>
				<div class="col-span-5 text-slate-600 font-mono">
					: {{ $customer->latitude }}, {{ $customer->longitude }}
				</div>
			</div>
			@endif
		</div>

		<!-- Photos Documentation Section -->
		<div class="py-8 space-y-4">
			<h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 pb-2">3. LAMPIRAN FOTO DOKUMENTASI</h4>
			<div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
				@php
					$photos = [
						['label' => 'Foto KTP', 'field' => 'foto_ktp'],
						['label' => 'Foto Rumah', 'field' => 'foto_rumah'],
						['label' => 'Foto ONT/Modem', 'field' => 'foto_perangkat'],
						['label' => 'Foto Port ODP', 'field' => 'foto_odp']
					];
				@endphp

				@foreach($photos as $photo)
					<div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-200/80 text-center space-y-2">
						<span class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider truncate" title="{{ $photo['label'] }}">{{ $photo['label'] }}</span>
						@if($customer->{$photo['field']})
							<div class="relative overflow-hidden rounded-xl aspect-video bg-slate-200 border border-slate-300">
								<img src="{{ asset('storage/' . $customer->{$photo['field']}) }}" class="w-full h-full object-cover">
							</div>
						@else
							<div class="aspect-video bg-slate-100 border border-slate-200 rounded-xl flex flex-col items-center justify-center text-slate-450 text-[10px]">
								<span class="italic text-slate-400">Belum Ada</span>
							</div>
						@endif
					</div>
				@endforeach
			</div>
		</div>

		<!-- Signatures -->
		<div class="pt-8 grid grid-cols-2 text-center text-xs gap-6 border-t border-slate-100 mt-6">
			<div>
				<span class="text-slate-400">Disiapkan oleh,</span>
				<div class="h-16"></div>
				<div class="font-bold text-slate-800 border-t border-slate-200 pt-1.5 mx-auto max-w-[150px]">
					Admin RT/RW
				</div>
				<span class="text-[9px] text-slate-400 font-medium">Pengurus Teknis</span>
			</div>
			
			<div>
				<span class="text-slate-400">Mengetahui (Pelanggan),</span>
				<div class="h-16"></div>
				<div class="font-bold text-slate-800 border-t border-slate-200 pt-1.5 mx-auto max-w-[150px]">
					{{ $customer->name }}
				</div>
				<span class="text-[9px] text-slate-400 font-medium">Pelanggan</span>
			</div>
		</div>

	</div>

	<!-- Auto Print Script -->
	<script>
		window.onload = function() {
			setTimeout(function() {
				window.print();
			}, 300);
		};
	</script>
</body>
</html>
