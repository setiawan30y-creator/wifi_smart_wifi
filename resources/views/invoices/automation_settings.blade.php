@extends('layouts.app')

@section('content')
@php
    $val = fn (string $key) => old($key, $settings[$key]);
    $on = fn (string $key) => (bool) old($key, $settings[$key]);

    $generateOn = $on('billing_auto_generate_enabled');
    $beforeOn = $on('billing_reminder_before_enabled');
    $afterOn = $on('billing_overdue_wa_enabled');

    $selectedGenerateTemplates = collect(old('billing_auto_generate_wa_template_ids', $settings['billing_auto_generate_wa_template_ids']))->map(fn ($id) => (int) $id)->all();
    $beforeDays = collect(old('billing_reminder_before_days', $settings['billing_reminder_before_days']))->map(fn ($d) => (int) $d)->all();
    $afterDays = collect(old('billing_reminder_after_days', $settings['billing_reminder_after_days']))->map(fn ($d) => (int) $d)->all();

    // Hari yang pernah disimpan di luar pilihan cepat tetap ditampilkan sebagai chip.
    $beforeChipList = collect($beforeChoices)->merge($beforeDays)->unique()->sortDesc()->values();
    $afterChipList = collect($afterChoices)->merge($afterDays)->unique()->sort()->values();

    $templateOptions = $waTemplates->map(fn ($t) => ['id' => $t->id, 'title' => $t->title ?: $t->name])->values();

    $placeholders = [
        '{nama_pelanggan}' => 'Nama pelanggan',
        '{nomor_tagihan}' => 'Nomor invoice',
        '{periode}' => 'Periode, mis. Oktober 2026',
        '{nominal}' => 'Total tagihan',
        '{tanggal_jatuh_tempo}' => 'Tanggal jatuh tempo',
        '{sisa_hari}' => 'besok / dalam 3 hari / hari ini',
        '{hari_terlambat}' => 'Jumlah hari terlambat',
        '{jumlah_tunggakan}' => 'Jumlah periode belum lunas',
        '{tanggal_janji_bayar}' => 'Tanggal janji bayar',
        '{catatan_janji_bayar}' => 'Catatan janji bayar',
        '{nama_odp}' => 'Nama ODP',
        '{rincian_tagihan}' => 'Rincian (pesan gabungan)',
    ];

    $rupiah = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $gen = $preview['generate'];
    $reminderRows = collect($preview['reminders']);
@endphp

<style>
    .ba-card { border: 1px solid rgba(30, 41, 59, 1); background: rgba(15, 23, 42, .6); border-radius: .9rem; }
    .ba-card[data-off="1"] .ba-body { opacity: .45; }
    .ba-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1rem .85rem; border-bottom: 1px solid rgba(30, 41, 59, .9); }
    .ba-body { padding: 1rem; display: grid; gap: .9rem; transition: opacity .15s ease; }
    .ba-label { display: block; font-size: .68rem; font-weight: 700; letter-spacing: .02em; color: #94a3b8; margin-bottom: .35rem; }
    .ba-hint { font-size: .68rem; color: #64748b; margin-top: .3rem; line-height: 1rem; }
    .ba-field { width: 100%; border: 1px solid rgba(51, 65, 85, .9); background: rgba(2, 6, 23, .72); color: #f8fafc; border-radius: .65rem; padding: .5rem .7rem; font-size: .8rem; outline: none; }
    .ba-field:focus { border-color: #38bdf8; box-shadow: 0 0 0 2px rgba(56, 189, 248, .15); }
    .ba-field option { background: #0f172a; color: #e2e8f0; }
    .ba-field.ba-invalid { border-color: rgba(244, 63, 94, .7); }
    .ba-error { font-size: .68rem; color: #fda4af; margin-top: .3rem; }

    .ba-switch { position: relative; display: inline-flex; flex-shrink: 0; cursor: pointer; }
    .ba-switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .ba-switch span { width: 2.5rem; height: 1.4rem; border-radius: 999px; background: #334155; transition: background .15s ease; position: relative; }
    .ba-switch span::after { content: ""; position: absolute; top: .2rem; left: .2rem; width: 1rem; height: 1rem; border-radius: 999px; background: #fff; transition: transform .15s ease; }
    .ba-switch input:checked + span { background: #0ea5e9; }
    .ba-switch input:checked + span::after { transform: translateX(1.1rem); }
    .ba-switch input:focus-visible + span { box-shadow: 0 0 0 2px rgba(56, 189, 248, .5); }

    .ba-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .6rem .7rem; border: 1px solid rgba(30, 41, 59, 1); border-radius: .65rem; background: rgba(2, 6, 23, .35); }
    .ba-row-title { font-size: .78rem; font-weight: 600; color: #e2e8f0; }
    .ba-row-sub { font-size: .68rem; color: #64748b; }

    .ba-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .ba-chip { position: relative; }
    .ba-chip input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .ba-chip span { display: inline-flex; align-items: center; min-width: 3.1rem; justify-content: center; padding: .38rem .6rem; border-radius: .55rem; border: 1px solid rgba(51, 65, 85, .9); background: rgba(2, 6, 23, .5); color: #94a3b8; font-size: .74rem; font-weight: 700; cursor: pointer; user-select: none; transition: all .12s ease; }
    .ba-chip input:checked + span { border-color: var(--chip, #38bdf8); background: color-mix(in srgb, var(--chip, #38bdf8) 16%, transparent); color: #f8fafc; }
    .ba-chip input:focus-visible + span { box-shadow: 0 0 0 2px rgba(56, 189, 248, .45); }
    .ba-chips[data-tone="before"] { --chip: #f59e0b; }
    .ba-chips[data-tone="after"] { --chip: #f43f5e; }

    .ba-add { display: inline-flex; gap: .3rem; }
    .ba-add input { width: 4.2rem; }
    .ba-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border-radius: .6rem; padding: .45rem .8rem; font-size: .75rem; font-weight: 700; border: 1px solid rgba(51, 65, 85, .9); background: rgba(15, 23, 42, .85); color: #e2e8f0; transition: background .12s ease; }
    .ba-btn:hover { background: #1e293b; }
    .ba-btn-primary { border-color: rgba(14, 165, 233, .6); background: #0284c7; color: #fff; }
    .ba-btn-primary:hover { background: #0369a1; }

    .ba-template { display: flex; align-items: center; gap: .5rem; padding: .45rem .6rem; border: 1px solid rgba(51, 65, 85, .8); border-radius: .6rem; background: rgba(15, 23, 42, .6); font-size: .75rem; color: #cbd5e1; cursor: pointer; }
    .ba-template:has(input:checked) { border-color: rgba(56, 189, 248, .75); background: rgba(14, 165, 233, .12); color: #f8fafc; }

    .ba-timeline { position: relative; display: flex; align-items: flex-start; gap: 0; overflow-x: auto; padding: .4rem .2rem .2rem; }
    .ba-step { flex: 1 0 4.2rem; display: flex; flex-direction: column; align-items: center; gap: .35rem; position: relative; text-align: center; }
    .ba-step::before { content: ""; position: absolute; top: .45rem; left: 0; right: 0; height: 2px; background: #1e293b; z-index: 0; }
    .ba-step:first-child::before { left: 50%; }
    .ba-step:last-child::before { right: 50%; }
    .ba-dot { position: relative; z-index: 1; width: .95rem; height: .95rem; border-radius: 999px; border: 2px solid #0f172a; background: var(--dot, #38bdf8); box-shadow: 0 0 0 2px var(--dot, #38bdf8); }
    .ba-step b { font-size: .72rem; color: #f8fafc; }
    .ba-step small { font-size: .62rem; color: #64748b; line-height: .85rem; }

    .ba-ph { display: flex; justify-content: space-between; gap: .5rem; padding: .4rem .55rem; border: 1px solid rgba(30, 41, 59, 1); border-radius: .5rem; background: rgba(2, 6, 23, .35); font-size: .7rem; text-align: left; width: 100%; }
    .ba-ph code { color: #7dd3fc; font-weight: 700; }
    .ba-ph span { color: #64748b; }
    .ba-ph:hover { border-color: rgba(56, 189, 248, .5); }

    .ba-pre { white-space: pre-wrap; font-size: .7rem; line-height: 1.05rem; color: #94a3b8; background: rgba(2, 6, 23, .5); border: 1px dashed rgba(51, 65, 85, .9); border-radius: .6rem; padding: .6rem .7rem; margin-top: .45rem; }
    .ba-pill { display: inline-flex; align-items: center; gap: .35rem; border-radius: .55rem; padding: .3rem .6rem; font-size: .68rem; font-weight: 700; border: 1px solid rgba(51, 65, 85, .9); background: rgba(15, 23, 42, .9); color: #94a3b8; }
    .ba-pill i { width: .45rem; height: .45rem; border-radius: 999px; background: #475569; }
    .ba-pill[data-on="1"] { color: #e2e8f0; }
    .ba-pill[data-on="1"] i { background: #34d399; }
    .ba-table { width: 100%; font-size: .72rem; border-collapse: collapse; }
    .ba-table th { text-align: left; color: #64748b; font-weight: 700; padding: .4rem .5rem; border-bottom: 1px solid #1e293b; }
    .ba-table td { padding: .45rem .5rem; border-bottom: 1px solid rgba(30, 41, 59, .6); color: #cbd5e1; }
</style>

<div class="space-y-4 pb-20">
    {{-- Judul --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight text-white md:text-2xl">Otomatis Tagihan</h1>
            <p class="mt-1 text-xs text-slate-400">Atur kapan tagihan terbit dan kapan pelanggan diingatkan lewat WhatsApp — sebelum dan sesudah jatuh tempo. Hanya untuk pelanggan berstatus <b class="text-slate-200">Aktif</b> (nonaktif, isolir, dan gratis dilewati).</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="ba-pill" data-on="{{ $generateOn ? 1 : 0 }}"><i></i>Tagihan terbit</span>
            <span class="ba-pill" data-on="{{ $beforeOn ? 1 : 0 }}"><i></i>Sebelum jatuh tempo</span>
            <span class="ba-pill" data-on="{{ $afterOn ? 1 : 0 }}"><i></i>Sesudah jatuh tempo</span>
            <span class="ba-pill" data-on="{{ $preview['wa_ready'] ? 1 : 0 }}"><i></i>WA gateway</span>
            <a href="{{ route('invoices.index') }}" class="ba-btn">← Tagihan</a>
        </div>
    </div>

    @unless($preview['wa_ready'])
        <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2.5 text-xs text-amber-200">
            Token WA gateway belum diisi, jadi pesan WhatsApp tidak akan terkirim. Isi di <a class="font-bold underline" href="{{ route('settings.index') }}">Pengaturan</a>. Pembuatan tagihan tetap berjalan.
        </div>
    @endunless

    @if($errors->any())
        <div class="rounded-xl border border-rose-500/25 bg-rose-500/10 px-4 py-2.5 text-xs text-rose-200">
            <div class="font-bold">Pengaturan belum tersimpan:</div>
            <ul class="mt-1 list-disc pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Hari ini (pratinjau, tidak mengirim apa pun) --}}
    <section class="ba-card">
        <div class="ba-head">
            <div>
                <h2 class="text-sm font-bold text-white">Hari ini, {{ now()->translatedFormat('l, d F Y') }}</h2>
                <p class="ba-row-sub mt-0.5">Perkiraan pekerjaan scheduler hari ini berdasarkan pengaturan yang <b>sudah tersimpan</b>. Halaman ini tidak mengirim pesan.</p>
            </div>
        </div>
        <div class="ba-body md:grid-cols-3">
            <div class="ba-row flex-col !items-start">
                <div class="ba-row-sub">Tagihan siap terbit</div>
                <div class="text-lg font-extrabold text-white">{{ $gen['count'] }} <span class="text-xs font-semibold text-slate-400">pelanggan</span></div>
                <div class="ba-row-sub">
                    @if(!$gen['enabled'])
                        Otomatis terbit nonaktif.
                    @elseif($gen['ran_today'])
                        Sudah berjalan hari ini; sisanya terbit besok jam {{ $gen['time'] }}.
                    @else
                        {{ $rupiah($gen['amount']) }} · terbit jam {{ $gen['time'] }}
                    @endif
                </div>
            </div>
            <div class="ba-row flex-col !items-start">
                <div class="ba-row-sub">Pengingat sebelum jatuh tempo</div>
                <div class="text-lg font-extrabold text-amber-300">{{ $reminderRows->where('type', 'before')->count() }} <span class="text-xs font-semibold text-slate-400">pelanggan</span></div>
                <div class="ba-row-sub">Dikirim mulai jam {{ $preview['schedule']->time }}</div>
            </div>
            <div class="ba-row flex-col !items-start">
                <div class="ba-row-sub">Pengingat sesudah jatuh tempo</div>
                <div class="text-lg font-extrabold text-rose-300">{{ $reminderRows->where('type', 'after')->count() }} <span class="text-xs font-semibold text-slate-400">pelanggan</span></div>
                <div class="ba-row-sub">Dikirim mulai jam {{ $preview['schedule']->time }}</div>
            </div>

            @if($reminderRows->isNotEmpty())
                <details class="md:col-span-3">
                    <summary class="cursor-pointer text-xs font-bold text-sky-300">Lihat daftar pengingat hari ini ({{ $reminderRows->count() }})</summary>
                    <div class="mt-2 max-h-72 overflow-auto rounded-lg border border-slate-800">
                        <table class="ba-table">
                            <thead><tr><th>Pelanggan</th><th>Tahap</th><th>Invoice</th><th class="text-right">Nominal</th></tr></thead>
                            <tbody>
                                @foreach($reminderRows as $row)
                                    <tr>
                                        <td>{{ $row['customer'] }}</td>
                                        <td><span class="{{ $row['type'] === 'before' ? 'text-amber-300' : 'text-rose-300' }} font-bold">{{ $row['stages'] }}</span></td>
                                        <td class="text-slate-400">{{ $row['invoices'] }}</td>
                                        <td class="text-right">{{ $rupiah($row['amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>
    </section>

    <form id="ba-form" action="{{ route('invoices.automation-settings.update') }}" method="POST" class="space-y-4">
        @csrf

        {{-- Alur satu siklus tagihan --}}
        <section class="ba-card">
            <div class="ba-head">
                <div>
                    <h2 class="text-sm font-bold text-white">Alur satu tagihan</h2>
                    <p class="ba-row-sub mt-0.5">Berubah langsung saat Anda mengubah pengaturan di bawah. Pengingat berhenti otomatis begitu tagihan dibayar.</p>
                </div>
            </div>
            <div class="ba-body">
                <div id="ba-timeline" class="ba-timeline" aria-live="polite"></div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            {{-- 1. Tagihan terbit --}}
            <section class="ba-card" data-section="generate" data-off="{{ $generateOn ? 0 : 1 }}">
                <div class="ba-head">
                    <div>
                        <h2 class="text-sm font-bold text-white">1. Tagihan terbit otomatis</h2>
                        <p class="ba-row-sub mt-0.5">Tagihan bulan berjalan dibuat beberapa hari sebelum jatuh tempo (tanggal tagih pelanggan).</p>
                    </div>
                    <label class="ba-switch" title="Aktif / nonaktif">
                        <input type="checkbox" name="billing_auto_generate_enabled" value="1" data-toggle="generate" @checked($generateOn)>
                        <span></span>
                    </label>
                </div>
                <div class="ba-body">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="ba-label" for="days_before_due">Terbit H- (hari sebelum jatuh tempo)</label>
                            <input id="days_before_due" type="number" min="0" max="31" name="billing_auto_generate_days_before_due" value="{{ $val('billing_auto_generate_days_before_due') }}" class="ba-field @error('billing_auto_generate_days_before_due') ba-invalid @enderror" data-timeline>
                            @error('billing_auto_generate_days_before_due')<div class="ba-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="ba-label" for="generate_time">Jam terbit</label>
                            <input id="generate_time" type="time" name="billing_auto_generate_time" value="{{ $val('billing_auto_generate_time') }}" class="ba-field @error('billing_auto_generate_time') ba-invalid @enderror">
                            @error('billing_auto_generate_time')<div class="ba-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <label class="ba-row cursor-pointer">
                        <div>
                            <div class="ba-row-title">Kirim WA saat tagihan terbit</div>
                            <div class="ba-row-sub">Pengingat H- pada hari yang sama dilewati agar tidak dobel.</div>
                        </div>
                        <span class="ba-switch"><input type="checkbox" name="billing_auto_generate_send_wa" value="1" data-timeline @checked($on('billing_auto_generate_send_wa'))><span></span></span>
                    </label>

                    <label class="ba-row cursor-pointer">
                        <div>
                            <div class="ba-row-title">Gabungkan pelanggan dengan nomor WA sama</div>
                            <div class="ba-row-sub">Satu pesan berisi rincian semua tagihan di nomor itu.</div>
                        </div>
                        <span class="ba-switch"><input type="checkbox" name="billing_auto_generate_group_same_phone" value="1" @checked($on('billing_auto_generate_group_same_phone'))><span></span></span>
                    </label>

                    <div>
                        <span class="ba-label">Template WA tagihan terbit</span>
                        @if($templateOptions->isEmpty())
                            <p class="ba-hint">Belum ada template. Pesan bawaan dipakai. <a class="text-sky-300 underline" href="{{ route('wa-templates.index') }}">Buat template</a></p>
                        @else
                            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                @foreach($templateOptions as $template)
                                    <label class="ba-template">
                                        <input type="checkbox" name="billing_auto_generate_wa_template_ids[]" value="{{ $template['id'] }}" @checked(in_array($template['id'], $selectedGenerateTemplates, true)) class="accent-sky-500">
                                        <span class="truncate">{{ $template['title'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="ba-hint">Pilih lebih dari satu agar isi pesan bervariasi. Tidak memilih = pesan bawaan.</p>
                        @endif
                        <details>
                            <summary class="mt-1 cursor-pointer text-[11px] font-semibold text-slate-400">Lihat pesan bawaan</summary>
                            <div class="ba-pre">{{ $defaultMessages['generated'] }}</div>
                        </details>
                    </div>
                </div>
            </section>

            {{-- 2. Sebelum jatuh tempo --}}
            <section class="ba-card" data-section="before" data-off="{{ $beforeOn ? 0 : 1 }}">
                <div class="ba-head">
                    <div>
                        <h2 class="text-sm font-bold text-white">2. Pengingat sebelum jatuh tempo</h2>
                        <p class="ba-row-sub mt-0.5">Untuk tagihan yang belum dibayar. Pilih hari-hari pengiriman.</p>
                    </div>
                    <label class="ba-switch" title="Aktif / nonaktif">
                        <input type="checkbox" name="billing_reminder_before_enabled" value="1" data-toggle="before" @checked($beforeOn)>
                        <span></span>
                    </label>
                </div>
                <div class="ba-body">
                    <div>
                        <span class="ba-label">Kirim pada</span>
                        <div class="ba-chips" data-tone="before" data-chips="before">
                            @foreach($beforeChipList as $day)
                                <label class="ba-chip">
                                    <input type="checkbox" name="billing_reminder_before_days[]" value="{{ $day }}" data-timeline @checked(in_array($day, $beforeDays, true))>
                                    <span>{{ $day === 0 ? 'Hari H' : 'H-' . $day }}</span>
                                </label>
                            @endforeach
                            <span class="ba-add">
                                <input type="number" min="0" max="31" placeholder="H-…" class="ba-field !py-1" data-add-input="before" aria-label="Tambah hari sebelum jatuh tempo">
                                <button type="button" class="ba-btn !py-1" data-add="before">+</button>
                            </span>
                        </div>
                        @error('billing_reminder_before_days')<div class="ba-error">{{ $message }}</div>@enderror
                        @error('billing_reminder_before_days.*')<div class="ba-error">{{ $message }}</div>@enderror
                        <p class="ba-hint">Hari H = pada tanggal jatuh tempo.</p>
                    </div>

                    <div>
                        <label class="ba-label" for="before_template">Template WA</label>
                        <select id="before_template" name="billing_reminder_before_wa_template_id" class="ba-field">
                            <option value="">Pesan bawaan</option>
                            @foreach($templateOptions as $template)
                                <option value="{{ $template['id'] }}" @selected((int) $val('billing_reminder_before_wa_template_id') === $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                        @error('billing_reminder_before_wa_template_id')<div class="ba-error">{{ $message }}</div>@enderror
                        <details>
                            <summary class="mt-1 cursor-pointer text-[11px] font-semibold text-slate-400">Lihat pesan bawaan</summary>
                            <div class="ba-pre">{{ $defaultMessages['before'] }}</div>
                        </details>
                    </div>
                </div>
            </section>

            {{-- 3. Sesudah jatuh tempo --}}
            <section class="ba-card" data-section="after" data-off="{{ $afterOn ? 0 : 1 }}">
                <div class="ba-head">
                    <div>
                        <h2 class="text-sm font-bold text-white">3. Pengingat sesudah jatuh tempo</h2>
                        <p class="ba-row-sub mt-0.5">Untuk pelanggan yang belum bayar. Tunggakan beberapa bulan digabung dalam satu pesan.</p>
                    </div>
                    <label class="ba-switch" title="Aktif / nonaktif">
                        <input type="checkbox" name="billing_overdue_wa_enabled" value="1" data-toggle="after" @checked($afterOn)>
                        <span></span>
                    </label>
                </div>
                <div class="ba-body">
                    <div>
                        <span class="ba-label">Kirim pada</span>
                        <div class="ba-chips" data-tone="after" data-chips="after">
                            @foreach($afterChipList as $day)
                                <label class="ba-chip">
                                    <input type="checkbox" name="billing_reminder_after_days[]" value="{{ $day }}" data-timeline @checked(in_array($day, $afterDays, true))>
                                    <span>H+{{ $day }}</span>
                                </label>
                            @endforeach
                            <span class="ba-add">
                                <input type="number" min="1" max="120" placeholder="H+…" class="ba-field !py-1" data-add-input="after" aria-label="Tambah hari sesudah jatuh tempo">
                                <button type="button" class="ba-btn !py-1" data-add="after">+</button>
                            </span>
                        </div>
                        @error('billing_reminder_after_days')<div class="ba-error">{{ $message }}</div>@enderror
                        @error('billing_reminder_after_days.*')<div class="ba-error">{{ $message }}</div>@enderror
                        <p class="ba-hint">Maksimal 10 hari. Selama pelanggan punya janji bayar yang belum lewat, pengingat ini ditahan.</p>
                    </div>

                    <div>
                        <label class="ba-label" for="after_template">Template WA</label>
                        <select id="after_template" name="billing_overdue_wa_template_id" class="ba-field">
                            <option value="">Pesan bawaan</option>
                            @foreach($templateOptions as $template)
                                <option value="{{ $template['id'] }}" @selected((int) $val('billing_overdue_wa_template_id') === $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                        @error('billing_overdue_wa_template_id')<div class="ba-error">{{ $message }}</div>@enderror
                        <details>
                            <summary class="mt-1 cursor-pointer text-[11px] font-semibold text-slate-400">Lihat pesan bawaan</summary>
                            <div class="ba-pre">{{ $defaultMessages['after'] }}</div>
                        </details>
                    </div>
                </div>
            </section>

            {{-- 4. Jam kirim, jeda, janji bayar --}}
            <section class="ba-card">
                <div class="ba-head">
                    <div>
                        <h2 class="text-sm font-bold text-white">4. Jam kirim &amp; janji bayar</h2>
                        <p class="ba-row-sub mt-0.5">Berlaku untuk semua pesan otomatis.</p>
                    </div>
                </div>
                <div class="ba-body">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="ba-label" for="reminder_time">Jam kirim pengingat</label>
                            <input id="reminder_time" type="time" name="billing_reminder_time" value="{{ $val('billing_reminder_time') }}" class="ba-field @error('billing_reminder_time') ba-invalid @enderror">
                            @error('billing_reminder_time')<div class="ba-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="ba-label" for="delay_min">Jeda min (detik)</label>
                            <input id="delay_min" type="number" min="0" max="300" name="billing_auto_generate_wa_delay_min" value="{{ $val('billing_auto_generate_wa_delay_min') }}" class="ba-field @error('billing_auto_generate_wa_delay_min') ba-invalid @enderror">
                        </div>
                        <div>
                            <label class="ba-label" for="delay_max">Jeda maks (detik)</label>
                            <input id="delay_max" type="number" min="0" max="300" name="billing_auto_generate_wa_delay_max" value="{{ $val('billing_auto_generate_wa_delay_max') }}" class="ba-field @error('billing_auto_generate_wa_delay_max') ba-invalid @enderror">
                        </div>
                    </div>
                    @error('billing_auto_generate_wa_delay_max')<div class="ba-error">{{ $message }}</div>@enderror
                    <p class="ba-hint -mt-2">Pengingat tidak dikirim sebelum jam ini. Jeda acak antar pesan membantu nomor WA tidak dianggap spam.</p>

                    <div>
                        <label class="ba-label" for="promise_template">Template WA janji bayar</label>
                        <select id="promise_template" name="billing_payment_promise_wa_template_id" class="ba-field">
                            <option value="">Pesan bawaan</option>
                            @foreach($templateOptions as $template)
                                <option value="{{ $template['id'] }}" @selected((int) $val('billing_payment_promise_wa_template_id') === $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                        <p class="ba-hint">Dikirim sekali pada tanggal janji bayar yang dicatat di tagihan.</p>
                        <details>
                            <summary class="mt-1 cursor-pointer text-[11px] font-semibold text-slate-400">Lihat pesan bawaan</summary>
                            <div class="ba-pre">{{ $defaultMessages['promise'] }}</div>
                        </details>
                    </div>
                </div>
            </section>
        </div>

        {{-- Placeholder --}}
        <details class="ba-card">
            <summary class="cursor-pointer px-4 py-3 text-xs font-bold text-slate-300">Kode isian untuk template WA (klik untuk menyalin)</summary>
            <div class="grid grid-cols-1 gap-1.5 px-4 pb-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($placeholders as $code => $label)
                    <button type="button" class="ba-ph" data-copy="{{ $code }}"><code>{{ $code }}</code><span>{{ $label }}</span></button>
                @endforeach
            </div>
        </details>

        {{-- Simpan --}}
        <div class="sticky bottom-3 z-20 flex flex-col gap-2 rounded-xl border border-slate-800 bg-slate-950/90 px-4 py-3 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[11px] text-slate-400" id="ba-dirty">Pengaturan dijalankan oleh scheduler server setiap menit.</p>
            <div class="flex gap-2">
                <a href="{{ route('invoices.automation-settings') }}" class="ba-btn">Batal</a>
                <button type="submit" class="ba-btn ba-btn-primary">Simpan pengaturan</button>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('ba-form');
    const timeline = document.getElementById('ba-timeline');
    if (!form || !timeline) return;

    const checked = (name) => Array.from(form.querySelectorAll(`input[name="${name}[]"]:checked`)).map((el) => parseInt(el.value, 10));
    const isOn = (name) => form.querySelector(`input[name="${name}"]`)?.checked;

    function render() {
        const steps = [];
        const generateOn = isOn('billing_auto_generate_enabled');
        const beforeOn = isOn('billing_reminder_before_enabled');
        const afterOn = isOn('billing_overdue_wa_enabled');
        const issueDay = parseInt(form.querySelector('[name="billing_auto_generate_days_before_due"]').value || '0', 10);

        if (generateOn) {
            steps.push({ at: -issueDay, label: issueDay === 0 ? 'Hari H' : 'H-' + issueDay, note: isOn('billing_auto_generate_send_wa') ? 'Tagihan terbit + WA' : 'Tagihan terbit', color: '#38bdf8' });
        }
        if (beforeOn) {
            checked('billing_reminder_before_days').forEach((d) => {
                if (generateOn && d === issueDay && isOn('billing_auto_generate_send_wa')) return;
                steps.push({ at: -d, label: d === 0 ? 'Hari H' : 'H-' + d, note: 'Pengingat', color: '#f59e0b' });
            });
        }
        steps.push({ at: 0.5, label: 'Jatuh tempo', note: '', color: '#a78bfa', due: true });
        if (afterOn) {
            checked('billing_reminder_after_days').forEach((d) => steps.push({ at: d, label: 'H+' + d, note: 'Belum bayar', color: '#f43f5e' }));
        }

        steps.sort((a, b) => a.at - b.at);
        timeline.innerHTML = '';
        steps.forEach((s) => {
            const el = document.createElement('div');
            el.className = 'ba-step';
            el.style.setProperty('--dot', s.color);
            const dot = document.createElement('span');
            dot.className = 'ba-dot';
            const label = document.createElement('b');
            label.textContent = s.label;
            const note = document.createElement('small');
            note.textContent = s.due ? '' : s.note;
            el.append(dot, label, note);
            if (s.due) label.style.color = '#c4b5fd';
            timeline.appendChild(el);
        });

        if (steps.length === 1) {
            const empty = document.createElement('p');
            empty.className = 'ba-hint';
            empty.textContent = 'Semua otomatis nonaktif.';
            timeline.appendChild(empty);
        }
    }

    // Aktif/nonaktif per bagian
    form.querySelectorAll('[data-toggle]').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const card = form.querySelector(`[data-section="${toggle.dataset.toggle}"]`);
            if (card) card.dataset.off = toggle.checked ? '0' : '1';
            render();
        });
    });

    // Tambah hari custom sebagai chip
    form.querySelectorAll('[data-add]').forEach((button) => {
        const type = button.dataset.add;
        const input = form.querySelector(`[data-add-input="${type}"]`);
        const name = type === 'before' ? 'billing_reminder_before_days[]' : 'billing_reminder_after_days[]';
        const min = parseInt(input.min, 10), max = parseInt(input.max, 10);

        const add = () => {
            const day = parseInt(input.value, 10);
            if (Number.isNaN(day) || day < min || day > max) { input.focus(); return; }
            const existing = form.querySelector(`input[name="${name}"][value="${day}"]`);
            if (existing) { existing.checked = true; render(); input.value = ''; return; }

            const chip = document.createElement('label');
            chip.className = 'ba-chip';
            const box = document.createElement('input');
            box.type = 'checkbox'; box.name = name; box.value = day; box.checked = true;
            box.addEventListener('change', render);
            const text = document.createElement('span');
            text.textContent = type === 'before' ? (day === 0 ? 'Hari H' : 'H-' + day) : 'H+' + day;
            chip.append(box, text);
            button.parentElement.before(chip);
            input.value = '';
            render();
        };

        button.addEventListener('click', add);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); add(); } });
    });

    form.querySelectorAll('[data-timeline]').forEach((el) => el.addEventListener('input', render));
    form.querySelectorAll('[data-timeline]').forEach((el) => el.addEventListener('change', render));

    // Salin kode isian
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            try { await navigator.clipboard.writeText(button.dataset.copy); } catch (e) { return; }
            const label = button.querySelector('span');
            const original = label.textContent;
            label.textContent = 'Disalin';
            setTimeout(() => { label.textContent = original; }, 1200);
        });
    });

    // Tanda ada perubahan belum disimpan
    const dirty = document.getElementById('ba-dirty');
    form.addEventListener('change', () => {
        dirty.textContent = 'Ada perubahan yang belum disimpan.';
        dirty.classList.add('text-amber-300');
    }, { once: true });

    render();
})();
</script>
@endsection
