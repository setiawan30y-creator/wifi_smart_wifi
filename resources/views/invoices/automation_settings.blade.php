@extends('layouts.app')

@section('content')
<style>
    .billing-automation-select option {
        background: #0f172a;
        color: #e2e8f0;
    }

    .billing-automation-select option:checked,
    .billing-automation-select option:hover {
        background: #1e293b;
        color: #ffffff;
    }

    .billing-automation-field {
        border: 1px solid rgba(51, 65, 85, .9);
        background: rgba(2, 6, 23, .72);
        color: #f8fafc;
        border-radius: .75rem;
        padding: .55rem .75rem;
        font-size: .78rem;
        line-height: 1.1rem;
        width: 100%;
        outline: none;
    }

    .billing-automation-field:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 2px rgba(56, 189, 248, .15);
    }

    .billing-template-choice {
        display: flex;
        min-height: 2.35rem;
        align-items: center;
        gap: .55rem;
        border: 1px solid rgba(51, 65, 85, .8);
        border-radius: .65rem;
        background: rgba(15, 23, 42, .62);
        padding: .45rem .55rem;
        transition: border-color .18s ease, background .18s ease;
    }

    .billing-template-choice:has(input:checked) {
        border-color: rgba(56, 189, 248, .75);
        background: rgba(14, 165, 233, .12);
    }
</style>

@php
    $autoGenerateActive = old('billing_auto_generate_enabled', $settings['billing_auto_generate_enabled']) == '1';
    $autoSendWaActive = old('billing_auto_generate_send_wa', $settings['billing_auto_generate_send_wa']) == '1';
    $autoGroupSamePhoneActive = old('billing_auto_generate_group_same_phone', $settings['billing_auto_generate_group_same_phone']) == '1';
    $overdueActive = old('billing_overdue_wa_enabled', $settings['billing_overdue_wa_enabled']) == '1';
    $storedGenerateTemplateIds = json_decode($settings['billing_auto_generate_wa_template_ids'] ?? '[]', true);
    $storedGenerateTemplateIds = is_array($storedGenerateTemplateIds) ? $storedGenerateTemplateIds : [];
    if (empty($storedGenerateTemplateIds) && !empty($settings['billing_auto_generate_wa_template_id'])) {
        $storedGenerateTemplateIds = [$settings['billing_auto_generate_wa_template_id']];
    }
    $selectedGenerateTemplateIds = collect(old('billing_auto_generate_wa_template_ids', $storedGenerateTemplateIds))
        ->map(fn ($id) => (string) $id)
        ->all();
    $placeholders = [
        '{nama_pelanggan}' => 'Nama pelanggan',
        '{nomor_tagihan}' => 'Nomor invoice',
        '{periode}' => 'Periode tagihan',
        '{nominal}' => 'Total tagihan',
        '{tanggal_jatuh_tempo}' => 'Jatuh tempo',
        '{tanggal_janji_bayar}' => 'Tanggal janji bayar',
        '{catatan_janji_bayar}' => 'Catatan janji bayar',
        '{bulan}' => 'Bulan',
        '{tahun}' => 'Tahun',
        '{nama_odp}' => 'Nama ODP',
    ];
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight text-white md:text-2xl">Otomatis Tagihan</h1>
            <p class="mt-1 text-xs text-slate-400">Aktifkan generate tagihan otomatis dan pengingat WhatsApp dari satu halaman.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="rounded-lg border {{ $autoGenerateActive ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-slate-700 bg-slate-900 text-slate-400' }} px-3 py-1.5 font-bold">
                Generate: {{ $autoGenerateActive ? 'Aktif' : 'Nonaktif' }}
            </span>
            <span class="rounded-lg border {{ $overdueActive ? 'border-rose-500/30 bg-rose-500/10 text-rose-300' : 'border-slate-700 bg-slate-900 text-slate-400' }} px-3 py-1.5 font-bold">
                Reminder: {{ $overdueActive ? 'Aktif' : 'Nonaktif' }}
            </span>
            <form action="{{ route('invoices.automation-settings.run-now') }}" method="POST" class="inline-flex" onsubmit="return confirm('Jalankan otomatis tagihan sekarang? Proses tetap mengikuti H- dan tanggal tagih pelanggan.')">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 font-semibold text-emerald-200 transition hover:bg-emerald-500/20">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-4.197-2.42A1 1 0 009 9.614v4.772a1 1 0 001.555.832l4.197-2.352a1 1 0 000-1.698z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Jalankan Sesuai H-
                </button>
            </form>
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-1.5 font-semibold text-slate-200 transition hover:bg-slate-800">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-2.5 text-xs font-semibold text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-500/25 bg-rose-500/10 px-4 py-2.5 text-xs font-semibold text-rose-200">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-500/25 bg-rose-500/10 px-4 py-2.5 text-xs text-rose-200">
            Ada pengaturan yang belum sesuai. Cek kolom yang ditandai.
        </div>
    @endif

    <form action="{{ route('invoices.automation-settings.update') }}" method="POST" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <section class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-lg shadow-slate-950/20">
                <div class="flex items-start justify-between gap-4 border-b border-slate-800/80 pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-white">Generate Tagihan</h2>
                            <span class="rounded-md {{ $autoGenerateActive ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-800 text-slate-400' }} px-2 py-0.5 text-[10px] font-bold uppercase">
                                {{ $autoGenerateActive ? 'Aktif' : 'Mati' }}
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">Membuat tagihan otomatis untuk pelanggan aktif berbayar. Pelanggan gratis dan berhenti tidak ikut.</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="billing_auto_generate_enabled" value="1" class="peer sr-only" {{ $autoGenerateActive ? 'checked' : '' }}>
                        <span class="relative h-6 w-11 rounded-full bg-slate-700 transition peer-checked:bg-emerald-500 after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                    </label>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Generate H-</label>
                        <input type="number" min="0" max="31" name="billing_auto_generate_days_before_due" value="{{ old('billing_auto_generate_days_before_due', $settings['billing_auto_generate_days_before_due']) }}" class="billing-automation-field">
                        <p class="mt-1 text-[10px] text-slate-500">Contoh 3: dibuat 3 hari sebelum tanggal tagih.</p>
                        @error('billing_auto_generate_days_before_due') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Jam Eksekusi</label>
                        <input type="time" name="billing_auto_generate_time" value="{{ old('billing_auto_generate_time', $settings['billing_auto_generate_time']) }}" class="billing-automation-field">
                        <p class="mt-1 text-[10px] text-slate-500">Jam server menjalankan proses otomatis.</p>
                        @error('billing_auto_generate_time') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/45 p-3">
                    <label class="flex cursor-pointer items-start justify-between gap-3">
                        <span>
                            <span class="block text-xs font-bold text-slate-200">Kirim WA setelah tagihan dibuat</span>
                            <span class="mt-0.5 block text-[10px] text-slate-500">Centang ini kalau invoice yang baru dibuat otomatis langsung dikirim lewat WA Gateway.</span>
                        </span>
                        <input type="checkbox" name="billing_auto_generate_send_wa" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500" {{ $autoSendWaActive ? 'checked' : '' }}>
                    </label>

                    <label class="mt-3 flex cursor-pointer items-start justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/45 px-3 py-2.5">
                        <span>
                            <span class="block text-xs font-bold text-slate-200">Gabungkan WA jika nomor sama</span>
                            <span class="mt-0.5 block text-[10px] text-slate-500">Jika 2 pelanggan memakai nomor WA yang sama, sistem mengirim 1 pesan berisi semua rincian tagihan.</span>
                        </span>
                        <input type="checkbox" name="billing_auto_generate_group_same_phone" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500" {{ $autoGroupSamePhoneActive ? 'checked' : '' }}>
                    </label>

                    <div class="mt-3">
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400">Template WA Generate</label>
                            <span id="generate-template-count" class="text-[10px] font-semibold text-sky-300">{{ count($selectedGenerateTemplateIds) }} dipilih</span>
                        </div>
                        <input id="generate-template-primary" type="hidden" name="billing_auto_generate_wa_template_id" value="{{ $selectedGenerateTemplateIds[0] ?? '' }}">
                        <div class="grid max-h-44 grid-cols-1 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                            @foreach($waTemplates as $template)
                                <label class="billing-template-choice cursor-pointer">
                                    <input type="checkbox" name="billing_auto_generate_wa_template_ids[]" value="{{ $template->id }}" class="h-3.5 w-3.5 rounded border-slate-600 bg-slate-950 text-sky-500 focus:ring-sky-500" {{ in_array((string) $template->id, $selectedGenerateTemplateIds, true) ? 'checked' : '' }}>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[11px] font-bold text-slate-100">{{ $template->title }}</span>
                                        <span class="block truncate text-[10px] text-slate-500">{{ $template->name }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-[10px] text-slate-500">Centang beberapa template. Sistem akan memilih otomatis secara bergantian saat WA tagihan dikirim. Jika nomor WA sama dan opsi gabung aktif, satu pesan akan berisi rincian beberapa pelanggan.</p>
                        @error('billing_auto_generate_wa_template_ids') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        @error('billing_auto_generate_wa_template_ids.*') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-3 rounded-lg border border-slate-800 bg-slate-950/45 p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400">Jeda Kirim Random</label>
                            <span class="rounded-md bg-sky-500/10 px-2 py-0.5 text-[10px] font-bold text-sky-300">Detik</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <input type="number" min="0" max="3600" name="billing_auto_generate_wa_delay_min" value="{{ old('billing_auto_generate_wa_delay_min', $settings['billing_auto_generate_wa_delay_min']) }}" class="billing-automation-field" placeholder="Min">
                                <p class="mt-1 text-[10px] text-slate-500">Minimal</p>
                                @error('billing_auto_generate_wa_delay_min') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input type="number" min="0" max="3600" name="billing_auto_generate_wa_delay_max" value="{{ old('billing_auto_generate_wa_delay_max', $settings['billing_auto_generate_wa_delay_max']) }}" class="billing-automation-field" placeholder="Max">
                                <p class="mt-1 text-[10px] text-slate-500">Maksimal</p>
                                @error('billing_auto_generate_wa_delay_max') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="mt-2 text-[10px] leading-relaxed text-slate-500">Contoh 8 dan 25: setiap WA diberi jeda acak antara 8-25 detik sebelum kirim berikutnya.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-lg shadow-slate-950/20">
                <div class="flex items-start justify-between gap-4 border-b border-slate-800/80 pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-white">WA Setelah Jatuh Tempo</h2>
                            <span class="rounded-md {{ $overdueActive ? 'bg-rose-500/15 text-rose-300' : 'bg-slate-800 text-slate-400' }} px-2 py-0.5 text-[10px] font-bold uppercase">
                                {{ $overdueActive ? 'Aktif' : 'Mati' }}
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">Mengirim pengingat untuk invoice yang sudah lewat jatuh tempo dan belum lunas.</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="billing_overdue_wa_enabled" value="1" class="peer sr-only" {{ $overdueActive ? 'checked' : '' }}>
                        <span class="relative h-6 w-11 rounded-full bg-slate-700 transition peer-checked:bg-rose-500 after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                    </label>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Kirim H+</label>
                        <input type="number" min="0" max="60" name="billing_overdue_days_after_due" value="{{ old('billing_overdue_days_after_due', $settings['billing_overdue_days_after_due']) }}" class="billing-automation-field">
                        <p class="mt-1 text-[10px] text-slate-500">Contoh 1: kirim 1 hari setelah jatuh tempo.</p>
                        @error('billing_overdue_days_after_due') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Maks. Pengingat</label>
                        <input type="number" min="1" max="10" name="billing_overdue_max_reminders" value="{{ old('billing_overdue_max_reminders', $settings['billing_overdue_max_reminders']) }}" class="billing-automation-field">
                        <p class="mt-1 text-[10px] text-slate-500">Batas kirim WA agar pelanggan tidak berulang terus.</p>
                        @error('billing_overdue_max_reminders') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Template WA Jatuh Tempo</label>
                    <select name="billing_overdue_wa_template_id" class="billing-automation-field billing-automation-select focus:border-rose-500">
                        <option value="">Template bawaan sistem</option>
                        @foreach($waTemplates as $template)
                            <option value="{{ $template->id }}" {{ (string) old('billing_overdue_wa_template_id', $settings['billing_overdue_wa_template_id']) === (string) $template->id ? 'selected' : '' }}>
                                {{ $template->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-slate-500">Template ini dipakai untuk tagihan yang belum dibayar setelah jatuh tempo.</p>
                    @error('billing_overdue_wa_template_id') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4 rounded-xl border border-amber-500/20 bg-amber-500/10 p-3">
                    <div class="mb-2">
                        <h3 class="text-xs font-bold text-amber-200">WA Janji Bayar</h3>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-amber-100/70">Dipakai saat admin mencatat pelanggan janji bayar tanggal tertentu. Sistem mengirim WA otomatis pada tanggal janji jika invoice masih belum lunas.</p>
                    </div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-amber-100/80">Template Default Janji Bayar</label>
                    <select name="billing_payment_promise_wa_template_id" class="billing-automation-field billing-automation-select focus:border-amber-500">
                        <option value="">Template bawaan sistem</option>
                        @foreach($waTemplates as $template)
                            <option value="{{ $template->id }}" {{ (string) old('billing_payment_promise_wa_template_id', $settings['billing_payment_promise_wa_template_id']) === (string) $template->id ? 'selected' : '' }}>
                                {{ $template->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-amber-100/65">Template ini dipakai jika invoice tidak memilih template khusus.</p>
                    @error('billing_payment_promise_wa_template_id') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                </div>
            </section>
        </div>

        <section class="rounded-xl border border-slate-800 bg-slate-900/55 p-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white">Placeholder Template</h2>
                    <p class="text-[11px] text-slate-400">Klik kode untuk copy, lalu tempel di isi template WA pada menu Pengaturan &gt; Template WA.</p>
                </div>
                <span id="placeholder-copy-status" class="hidden text-[11px] font-semibold text-emerald-300">Tersalin</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 md:grid-cols-4">
                @foreach($placeholders as $placeholder => $label)
                    <button type="button" data-copy-placeholder="{{ $placeholder }}" class="group rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-left transition hover:border-sky-500/70 hover:bg-slate-900">
                        <span class="block font-mono text-[11px] font-bold text-slate-200 group-hover:text-sky-300">{{ $placeholder }}</span>
                        <span class="mt-0.5 block text-[10px] text-slate-500">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-sky-500/20 bg-sky-500/10 px-4 py-3 text-xs text-sky-100">
            <strong>Cara mengaktifkan:</strong> nyalakan switch Generate Tagihan atau WA Setelah Jatuh Tempo, isi jadwalnya, pilih template bila perlu, lalu klik Simpan Pengaturan satu kali. Pengaturan tersimpan dan akan berjalan terus; generate mengikuti jam yang dipilih, sedangkan reminder setelah jatuh tempo diperiksa setiap menit. Tombol Jalankan Sesuai H- hanya menjalankan proses test/manual. Di Ubuntu, scheduler Laravel juga harus aktif.
        </section>

        <div class="sticky bottom-3 z-10 flex flex-col justify-end gap-2 rounded-xl border border-slate-800 bg-slate-950/85 p-3 shadow-xl shadow-slate-950/40 backdrop-blur sm:flex-row">
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-700 bg-slate-900/80 px-4 py-2 text-xs font-semibold text-slate-200 transition hover:bg-slate-800">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-sky-500 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-sky-950/20 transition hover:bg-sky-600">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const status = document.getElementById('placeholder-copy-status');
        const templateCount = document.getElementById('generate-template-count');
        const primaryTemplate = document.getElementById('generate-template-primary');
        const templateChecks = document.querySelectorAll('input[name="billing_auto_generate_wa_template_ids[]"]');

        const refreshTemplateCount = () => {
            const selected = Array.from(templateChecks).filter((checkbox) => checkbox.checked);
            if (templateCount) {
                templateCount.textContent = selected.length + ' dipilih';
            }
            if (primaryTemplate) {
                primaryTemplate.value = selected[0]?.value || '';
            }
        };

        templateChecks.forEach((checkbox) => {
            checkbox.addEventListener('change', refreshTemplateCount);
        });
        refreshTemplateCount();

        document.querySelectorAll('[data-copy-placeholder]').forEach((button) => {
            button.addEventListener('click', async () => {
                const value = button.dataset.copyPlaceholder || '';

                try {
                    await navigator.clipboard.writeText(value);
                } catch (error) {
                    const temp = document.createElement('textarea');
                    temp.value = value;
                    temp.setAttribute('readonly', 'readonly');
                    temp.style.position = 'fixed';
                    temp.style.opacity = '0';
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                }

                if (status) {
                    status.textContent = value + ' tersalin';
                    status.classList.remove('hidden');
                    window.clearTimeout(Number(status.dataset.timerId || 0));
                    const timerId = window.setTimeout(() => status.classList.add('hidden'), 1600);
                    status.dataset.timerId = String(timerId);
                }
            });
        });
    });
</script>
@endsection
