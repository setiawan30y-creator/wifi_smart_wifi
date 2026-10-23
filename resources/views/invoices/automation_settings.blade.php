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

    // Hari yang pernah disimpan di luar pilihan cepat tetap tampil sebagai chip.
    $beforeChipList = collect($beforeChoices)->merge($beforeDays)->unique()->sortDesc()->values();
    $afterChipList = collect($afterChoices)->merge($afterDays)->unique()->sort()->values();

    $templateOptions = $waTemplates->map(fn ($t) => ['id' => $t->id, 'title' => $t->title ?: $t->name])->values();
    $templateContents = $waTemplates->mapWithKeys(fn ($t) => [$t->id => (string) $t->content]);

    $placeholders = [
        '{nama_pelanggan}' => 'Nama pelanggan',
        '{nomor_tagihan}' => 'Nomor invoice',
        '{periode}' => 'Periode tagihan',
        '{nominal}' => 'Total tagihan',
        '{tanggal_jatuh_tempo}' => 'Tanggal jatuh tempo',
        '{sisa_hari}' => 'besok / dalam 3 hari',
        '{hari_terlambat}' => 'Hari terlambat',
        '{jumlah_tunggakan}' => 'Jumlah periode tertunggak',
        '{tanggal_janji_bayar}' => 'Tanggal janji bayar',
        '{catatan_janji_bayar}' => 'Catatan janji bayar',
        '{nama_odp}' => 'Nama ODP',
        '{rincian_tagihan}' => 'Rincian pesan gabungan',
    ];

    $rupiah = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $gen = $preview['generate'];
    $reminderRows = collect($preview['reminders']);
    $beforeCount = $reminderRows->where('type', 'before')->count();
    $afterCount = $reminderRows->where('type', 'after')->count();
@endphp

<style>
    .ba {
        --ba-card: rgba(15, 23, 42, .72);
        --ba-card-2: rgba(2, 6, 23, .45);
        --ba-line: rgba(71, 85, 105, .36);
        --ba-line-strong: rgba(100, 116, 139, .5);
        --ba-text: #e5edf7;
        --ba-strong: #f8fafc;
        --ba-muted: #94a3b8;
        --ba-soft: #64748b;
        --ba-field: rgba(2, 6, 23, .6);
        --ba-primary: #6366f1;
        --ba-primary-strong: #4f46e5;
        --ba-sky: #38bdf8;
        --ba-amber: #f59e0b;
        --ba-rose: #f43f5e;
        --ba-violet: #a78bfa;
        --ba-emerald: #10b981;
        --ba-bubble: #134e4a;
        --ba-bubble-text: #ecfdf5;
        --ba-chat: rgba(2, 6, 23, .55);
        color: var(--ba-text);
    }
    html[data-theme="light"] .ba {
        --ba-card: #ffffff;
        --ba-card-2: #f8fafc;
        --ba-line: #e2e8f0;
        --ba-line-strong: #cbd5e1;
        --ba-text: #334155;
        --ba-strong: #0f172a;
        --ba-muted: #64748b;
        --ba-soft: #94a3b8;
        --ba-field: #ffffff;
        --ba-bubble: #dcfce7;
        --ba-bubble-text: #14532d;
        --ba-chat: #f1f5f9;
    }

    .ba h1, .ba h2, .ba h3 { color: var(--ba-strong); }
    .ba-muted { color: var(--ba-muted); }
    .ba-soft { color: var(--ba-soft); }

    .ba-card { background: var(--ba-card); border: 1px solid var(--ba-line); border-radius: 1rem; box-shadow: 0 16px 40px -34px rgba(2, 6, 23, .9); }
    html[data-theme="light"] .ba-card { box-shadow: 0 10px 30px -24px rgba(15, 23, 42, .25); }

    /* Ringkasan */
    .ba-stat { display: flex; gap: .85rem; align-items: flex-start; padding: 1rem; }
    .ba-stat + .ba-stat { border-left: 1px solid var(--ba-line); }
    @media (max-width: 1023px) { .ba-stat + .ba-stat { border-left: 0; border-top: 1px solid var(--ba-line); } }
    .ba-icon { flex-shrink: 0; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .75rem; background: color-mix(in srgb, var(--tone) 14%, transparent); color: var(--tone); }
    .ba-icon svg { width: 1.15rem; height: 1.15rem; }
    .ba-stat-value { font-size: 1.5rem; line-height: 1.9rem; font-weight: 800; color: var(--ba-strong); }
    .ba-stat-label { font-size: .72rem; font-weight: 600; color: var(--ba-muted); }
    .ba-stat-note { font-size: .7rem; color: var(--ba-soft); margin-top: .1rem; }

    /* Kartu langkah */
    .ba-step-card { position: relative; overflow: hidden; }
    .ba-step-card::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 3px; background: var(--tone); opacity: .9; }
    .ba-step-head { display: flex; align-items: center; gap: .85rem; padding: 1rem 1.1rem; }
    .ba-num { flex-shrink: 0; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: .65rem; font-size: .8rem; font-weight: 800; color: var(--tone); background: color-mix(in srgb, var(--tone) 14%, transparent); border: 1px solid color-mix(in srgb, var(--tone) 35%, transparent); }
    .ba-step-title { font-size: .92rem; font-weight: 700; color: var(--ba-strong); }
    .ba-step-desc { font-size: .74rem; color: var(--ba-muted); margin-top: .1rem; }
    .ba-step-body { display: grid; gap: 1.1rem; padding: 0 1.1rem 1.1rem; transition: opacity .15s ease, filter .15s ease; }
    @media (min-width: 900px) { .ba-step-body.ba-split { grid-template-columns: minmax(0, 1fr) minmax(0, 18rem); } }
    .ba-step-card[data-off="1"] .ba-step-body { opacity: .4; filter: grayscale(.6); }
    .ba-off-note { display: none; font-size: .7rem; font-weight: 700; color: var(--ba-soft); }
    .ba-step-card[data-off="1"] .ba-off-note { display: inline; }

    /* Form */
    .ba-label { display: block; font-size: .72rem; font-weight: 700; color: var(--ba-muted); margin-bottom: .4rem; }
    .ba-hint { font-size: .7rem; color: var(--ba-soft); margin-top: .4rem; line-height: 1.05rem; }
    .ba-field { width: 100%; border: 1px solid var(--ba-line-strong); background: var(--ba-field); color: var(--ba-strong); border-radius: .7rem; padding: .55rem .75rem; font-size: .84rem; outline: none; transition: border-color .12s ease, box-shadow .12s ease; }
    .ba-field:focus { border-color: var(--ba-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ba-primary) 22%, transparent); }
    .ba-field option { background: #0f172a; color: #e2e8f0; }
    html[data-theme="light"] .ba-field option { background: #fff; color: #0f172a; }
    .ba-invalid { border-color: rgba(244, 63, 94, .75) !important; }
    .ba-error { font-size: .7rem; color: #fb7185; margin-top: .35rem; }
    .ba-suffix { position: relative; }
    .ba-suffix span { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); font-size: .72rem; color: var(--ba-soft); pointer-events: none; }
    .ba-suffix .ba-field { padding-right: 3.2rem; }

    .ba-switch { position: relative; display: inline-flex; flex-shrink: 0; cursor: pointer; }
    .ba-switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .ba-switch span { width: 2.6rem; height: 1.45rem; border-radius: 999px; background: var(--ba-line-strong); transition: background .15s ease; position: relative; }
    .ba-switch span::after { content: ""; position: absolute; top: .2rem; left: .2rem; width: 1.05rem; height: 1.05rem; border-radius: 999px; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, .3); transition: transform .15s ease; }
    .ba-switch input:checked + span { background: var(--ba-primary); }
    .ba-switch input:checked + span::after { transform: translateX(1.15rem); }
    .ba-switch input:focus-visible + span { box-shadow: 0 0 0 3px color-mix(in srgb, var(--ba-primary) 40%, transparent); }

    .ba-option { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .7rem .8rem; border: 1px solid var(--ba-line); border-radius: .75rem; background: var(--ba-card-2); cursor: pointer; }
    .ba-option-title { font-size: .8rem; font-weight: 600; color: var(--ba-strong); }
    .ba-option-sub { font-size: .7rem; color: var(--ba-soft); margin-top: .1rem; }

    .ba-chips { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .ba-chip { position: relative; }
    .ba-chip input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .ba-chip span { display: inline-flex; align-items: center; justify-content: center; gap: .3rem; min-width: 3.4rem; padding: .42rem .65rem; border-radius: .6rem; border: 1px solid var(--ba-line-strong); background: var(--ba-card-2); color: var(--ba-muted); font-size: .76rem; font-weight: 700; cursor: pointer; user-select: none; transition: all .12s ease; }
    .ba-chip span:hover { border-color: var(--tone); color: var(--ba-strong); }
    .ba-chip input:checked + span { border-color: var(--tone); background: var(--tone); color: #fff; box-shadow: 0 6px 16px -10px var(--tone); }
    .ba-chip input:focus-visible + span { box-shadow: 0 0 0 3px color-mix(in srgb, var(--tone) 40%, transparent); }
    .ba-add { display: inline-flex; align-items: center; border: 1px dashed var(--ba-line-strong); border-radius: .6rem; overflow: hidden; }
    .ba-add input { width: 3.6rem; background: transparent; border: 0; padding: .4rem .5rem; font-size: .76rem; color: var(--ba-strong); outline: none; }
    .ba-add button { padding: .4rem .6rem; font-size: .8rem; font-weight: 800; color: var(--tone); border-left: 1px dashed var(--ba-line-strong); }
    .ba-add button:hover { background: color-mix(in srgb, var(--tone) 12%, transparent); }

    .ba-template { display: flex; align-items: center; gap: .55rem; padding: .55rem .7rem; border: 1px solid var(--ba-line); border-radius: .7rem; background: var(--ba-card-2); font-size: .78rem; color: var(--ba-text); cursor: pointer; transition: border-color .12s ease, background .12s ease; }
    .ba-template:has(input:checked) { border-color: var(--ba-primary); background: color-mix(in srgb, var(--ba-primary) 10%, transparent); color: var(--ba-strong); }
    .ba-template input { accent-color: var(--ba-primary); }

    /* Contoh pesan WA */
    .ba-chat { border-radius: .85rem; background: var(--ba-chat); border: 1px solid var(--ba-line); padding: .75rem; }
    .ba-chat-head { display: flex; align-items: center; justify-content: space-between; font-size: .68rem; font-weight: 700; color: var(--ba-soft); margin-bottom: .55rem; text-transform: uppercase; letter-spacing: .04em; }
    .ba-bubble { position: relative; white-space: pre-wrap; word-break: break-word; font-size: .76rem; line-height: 1.15rem; color: var(--ba-bubble-text); background: var(--ba-bubble); border-radius: .75rem .75rem .2rem .75rem; padding: .6rem .7rem 1.15rem; max-height: 16rem; overflow: auto; }
    .ba-bubble small { position: absolute; right: .6rem; bottom: .3rem; font-size: .62rem; opacity: .65; }

    /* Alur */
    .ba-flow { display: flex; overflow-x: auto; padding: .25rem 0 .1rem; scrollbar-width: thin; }
    .ba-flow-step { flex: 1 0 4.6rem; display: flex; flex-direction: column; align-items: center; gap: .35rem; position: relative; text-align: center; }
    .ba-flow-step::before { content: ""; position: absolute; top: .55rem; left: 0; right: 0; height: 2px; background: var(--ba-line-strong); }
    .ba-flow-step:first-child::before { left: 50%; }
    .ba-flow-step:last-child::before { right: 50%; }
    .ba-flow-dot { position: relative; z-index: 1; width: 1.1rem; height: 1.1rem; border-radius: 999px; background: var(--dot); box-shadow: 0 0 0 4px color-mix(in srgb, var(--dot) 22%, transparent); }
    .ba-flow-step.is-due .ba-flow-dot { width: 1.35rem; height: 1.35rem; margin-top: -.12rem; background: var(--ba-card); border: 3px solid var(--dot); }
    .ba-flow-step b { font-size: .74rem; color: var(--ba-strong); }
    .ba-flow-step small { font-size: .64rem; color: var(--ba-soft); line-height: .85rem; max-width: 5.5rem; }
    .ba-legend { display: flex; flex-wrap: wrap; gap: .9rem; font-size: .68rem; color: var(--ba-soft); }
    .ba-legend i { display: inline-block; width: .55rem; height: .55rem; border-radius: 999px; margin-right: .35rem; background: var(--dot); }

    /* Lain-lain */
    .ba-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; border-radius: .7rem; padding: .55rem .95rem; font-size: .8rem; font-weight: 700; border: 1px solid var(--ba-line-strong); background: var(--ba-card); color: var(--ba-text); transition: background .12s ease, border-color .12s ease; }
    .ba-btn:hover { border-color: var(--ba-primary); color: var(--ba-strong); }
    .ba-btn-primary { border-color: transparent; background: linear-gradient(135deg, var(--ba-primary-strong), #2563eb); color: #fff; box-shadow: 0 10px 24px -14px rgba(79, 70, 229, .9); }
    .ba-btn-primary:hover { color: #fff; filter: brightness(1.08); }
    .ba-btn svg { width: 1rem; height: 1rem; }

    .ba-alert { display: flex; gap: .7rem; align-items: flex-start; border-radius: .85rem; padding: .75rem .9rem; font-size: .78rem; border: 1px solid color-mix(in srgb, var(--tone) 35%, transparent); background: color-mix(in srgb, var(--tone) 10%, transparent); color: var(--ba-text); }
    .ba-alert svg { width: 1.1rem; height: 1.1rem; color: var(--tone); flex-shrink: 0; margin-top: .05rem; }

    .ba-ph { display: flex; align-items: center; justify-content: space-between; gap: .5rem; width: 100%; padding: .45rem .6rem; border-radius: .55rem; font-size: .72rem; text-align: left; transition: background .12s ease; }
    .ba-ph:hover { background: var(--ba-card-2); }
    .ba-ph code { color: var(--ba-primary); font-weight: 700; font-size: .72rem; }
    html:not([data-theme="light"]) .ba-ph code { color: #a5b4fc; }
    .ba-ph span { color: var(--ba-soft); }

    .ba-table { width: 100%; font-size: .76rem; border-collapse: collapse; }
    .ba-table th { text-align: left; color: var(--ba-soft); font-weight: 700; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; padding: .55rem .75rem; border-bottom: 1px solid var(--ba-line); }
    .ba-table td { padding: .55rem .75rem; border-bottom: 1px solid var(--ba-line); color: var(--ba-text); }
    .ba-tag { display: inline-flex; padding: .12rem .45rem; border-radius: .4rem; font-size: .68rem; font-weight: 800; color: var(--tone); background: color-mix(in srgb, var(--tone) 14%, transparent); }

    .ba-savebar { position: sticky; bottom: .75rem; z-index: 20; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem .8rem .7rem 1rem; border-radius: 1rem; border: 1px solid var(--ba-line-strong); background: color-mix(in srgb, var(--ba-card) 92%, transparent); backdrop-filter: blur(10px); box-shadow: 0 18px 40px -20px rgba(2, 6, 23, .7); }
    .ba-savebar p { font-size: .74rem; color: var(--ba-muted); }
    .ba-savebar p.is-dirty { color: var(--ba-amber); font-weight: 700; }
    @media (max-width: 767px) { .ba-savebar { bottom: 5.5rem; } }

    /* Aturan input global aplikasi memakai !important di @layer components; agar kolom di
       halaman ini ikut mode terang, aturannya ditaruh di @layer base (layer awal menang untuk !important). */
    @layer base {
        html[data-theme="light"] .ba .ba-field { background-color: #ffffff !important; color: #0f172a !important; border-color: #cbd5e1 !important; }
        html[data-theme="light"] .ba .ba-add input { background-color: transparent !important; color: #0f172a !important; border: 0 !important; }
    }
    .ba .ba-add input { background-color: transparent !important; border: 0 !important; box-shadow: none !important; }

    .ba details > summary { list-style: none; cursor: pointer; }
    .ba details > summary::-webkit-details-marker { display: none; }
    .ba-caret { transition: transform .15s ease; }
    .ba details[open] .ba-caret { transform: rotate(90deg); }
</style>

<div class="ba space-y-5 pb-6">
    {{-- Judul --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-400 hover:underline">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Tagihan &amp; Invoice
            </a>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight" style="color: #f8fafc">Otomatis Tagihan</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-400">Atur kapan tagihan terbit dan kapan pelanggan diingatkan lewat WhatsApp. Hanya untuk pelanggan berstatus <b style="color: #e2e8f0">Aktif</b>; pelanggan nonaktif, isolir, dan gratis dilewati.</p>
        </div>
        <button type="submit" form="ba-form" class="ba-btn ba-btn-primary self-start md:self-auto">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Simpan pengaturan
        </button>
    </div>

    @unless($preview['wa_ready'])
        <div class="ba-alert" style="--tone: var(--ba-amber)">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            <div><b>WA gateway belum diatur.</b> Tagihan tetap terbit, tetapi pesan WhatsApp tidak terkirim. Isi token di <a class="font-bold underline" href="{{ route('settings.index') }}">Pengaturan</a>.</div>
        </div>
    @endunless

    @if($errors->any())
        <div class="ba-alert" style="--tone: var(--ba-rose)">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <b>Pengaturan belum tersimpan.</b>
                <ul class="mt-1 list-disc pl-4">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Ringkasan hari ini --}}
    <section class="ba-card">
        <div class="flex flex-wrap items-center justify-between gap-2 px-4 pt-4">
            <h2 class="text-sm font-bold">Hari ini · {{ now()->locale('id')->translatedFormat('l, d F Y') }}</h2>
            <span class="ba-soft text-xs">Perkiraan dari pengaturan tersimpan · halaman ini tidak mengirim pesan</span>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-4">
            <div class="ba-stat" style="--tone: var(--ba-sky)">
                <span class="ba-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z"/></svg></span>
                <div>
                    <div class="ba-stat-label">Tagihan siap terbit</div>
                    <div class="ba-stat-value">{{ $gen['count'] }}</div>
                    <div class="ba-stat-note">
                        @if(!$gen['enabled']) Otomatis terbit nonaktif
                        @elseif($gen['ran_today']) Sudah jalan hari ini · sisanya besok {{ $gen['time'] }}
                        @else {{ $rupiah($gen['amount']) }} · jam {{ $gen['time'] }}
                        @endif
                    </div>
                </div>
            </div>
            <div class="ba-stat" style="--tone: var(--ba-amber)">
                <span class="ba-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                <div>
                    <div class="ba-stat-label">Pengingat sebelum jatuh tempo</div>
                    <div class="ba-stat-value">{{ $beforeCount }}</div>
                    <div class="ba-stat-note">{{ $beforeOn ? 'Mulai jam ' . $preview['schedule']->time : 'Nonaktif' }}</div>
                </div>
            </div>
            <div class="ba-stat" style="--tone: var(--ba-rose)">
                <span class="ba-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></span>
                <div>
                    <div class="ba-stat-label">Pengingat belum bayar</div>
                    <div class="ba-stat-value">{{ $afterCount }}</div>
                    <div class="ba-stat-note">{{ $afterOn ? 'Mulai jam ' . $preview['schedule']->time : 'Nonaktif' }}</div>
                </div>
            </div>
            <div class="ba-stat" style="--tone: {{ $preview['wa_ready'] ? 'var(--ba-emerald)' : 'var(--ba-soft)' }}">
                <span class="ba-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.4-4 8-9 8a9.9 9.9 0 01-4.3-.9L3 20l1.4-3.7A7.5 7.5 0 013 12c0-4.4 4-8 9-8s9 3.6 9 8z"/></svg></span>
                <div>
                    <div class="ba-stat-label">WA gateway</div>
                    <div class="ba-stat-value text-base! leading-8!">{{ $preview['wa_ready'] ? 'Siap' : 'Belum diatur' }}</div>
                    <div class="ba-stat-note">Jeda {{ $val('billing_auto_generate_wa_delay_min') }}–{{ $val('billing_auto_generate_wa_delay_max') }} detik antar pesan</div>
                </div>
            </div>
        </div>

        @if($reminderRows->isNotEmpty())
            <details class="border-t" style="border-color: var(--ba-line)">
                <summary class="flex items-center gap-2 px-4 py-3 text-xs font-bold" style="color: var(--ba-primary)">
                    <svg class="ba-caret h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    Lihat {{ $reminderRows->count() }} pelanggan yang akan diingatkan hari ini
                </summary>
                <div class="max-h-80 overflow-auto px-1 pb-2">
                    <table class="ba-table">
                        <thead><tr><th>Pelanggan</th><th>Tahap</th><th class="hidden sm:table-cell">Invoice</th><th class="text-right">Nominal</th></tr></thead>
                        <tbody>
                            @foreach($reminderRows as $row)
                                <tr>
                                    <td class="font-semibold">{{ $row['customer'] }}</td>
                                    <td><span class="ba-tag" style="--tone: {{ $row['type'] === 'before' ? 'var(--ba-amber)' : 'var(--ba-rose)' }}">{{ $row['stages'] }}</span></td>
                                    <td class="ba-soft hidden sm:table-cell">{{ $row['invoices'] }}</td>
                                    <td class="text-right font-semibold">{{ $rupiah($row['amount']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
    </section>

    <form id="ba-form" action="{{ route('invoices.automation-settings.update') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_21rem]">
            {{-- Kolom utama --}}
            <div class="min-w-0 space-y-5">
                {{-- Alur --}}
                <section class="ba-card p-4">
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-bold">Alur satu tagihan</h2>
                            <p class="ba-muted mt-0.5 text-xs">Ikut berubah saat Anda mengubah pengaturan. Pengingat berhenti begitu tagihan dibayar.</p>
                        </div>
                        <div class="ba-legend">
                            <span style="--dot: var(--ba-sky)"><i></i>Terbit</span>
                            <span style="--dot: var(--ba-amber)"><i></i>Sebelum</span>
                            <span style="--dot: var(--ba-violet)"><i></i>Jatuh tempo</span>
                            <span style="--dot: var(--ba-rose)"><i></i>Belum bayar</span>
                        </div>
                    </div>
                    <div id="ba-flow" class="ba-flow" aria-live="polite"></div>
                </section>

                {{-- 1. Tagihan terbit --}}
                <section class="ba-card ba-step-card" style="--tone: var(--ba-sky)" data-section="generate" data-off="{{ $generateOn ? 0 : 1 }}">
                    <div class="ba-step-head">
                        <span class="ba-num">1</span>
                        <div class="min-w-0 flex-1">
                            <h3 class="ba-step-title">Tagihan terbit otomatis <span class="ba-off-note">· nonaktif</span></h3>
                            <p class="ba-step-desc">Tagihan bulan berjalan dibuat beberapa hari sebelum tanggal tagih pelanggan.</p>
                        </div>
                        <label class="ba-switch" title="Aktif / nonaktif"><input type="checkbox" name="billing_auto_generate_enabled" value="1" data-toggle="generate" @checked($generateOn)><span></span></label>
                    </div>
                    <div class="ba-step-body ba-split">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="ba-label" for="days_before_due">Terbit sebelum jatuh tempo</label>
                                    <div class="ba-suffix">
                                        <input id="days_before_due" type="number" min="0" max="31" name="billing_auto_generate_days_before_due" value="{{ $val('billing_auto_generate_days_before_due') }}" class="ba-field @error('billing_auto_generate_days_before_due') ba-invalid @enderror" data-flow>
                                        <span>hari</span>
                                    </div>
                                    @error('billing_auto_generate_days_before_due')<div class="ba-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="ba-label" for="generate_time">Jam terbit</label>
                                    <input id="generate_time" type="time" name="billing_auto_generate_time" value="{{ $val('billing_auto_generate_time') }}" class="ba-field @error('billing_auto_generate_time') ba-invalid @enderror">
                                    @error('billing_auto_generate_time')<div class="ba-error">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <label class="ba-option">
                                <div>
                                    <div class="ba-option-title">Kirim WA saat tagihan terbit</div>
                                    <div class="ba-option-sub">Pengingat sebelum jatuh tempo di hari yang sama dilewati agar tidak dobel.</div>
                                </div>
                                <span class="ba-switch"><input type="checkbox" name="billing_auto_generate_send_wa" value="1" data-flow @checked($on('billing_auto_generate_send_wa'))><span></span></span>
                            </label>

                            <label class="ba-option">
                                <div>
                                    <div class="ba-option-title">Gabungkan pelanggan dengan nomor WA sama</div>
                                    <div class="ba-option-sub">Satu pesan berisi rincian semua tagihan di nomor itu.</div>
                                </div>
                                <span class="ba-switch"><input type="checkbox" name="billing_auto_generate_group_same_phone" value="1" @checked($on('billing_auto_generate_group_same_phone'))><span></span></span>
                            </label>

                            <div>
                                <span class="ba-label">Template WA</span>
                                @if($templateOptions->isEmpty())
                                    <p class="ba-hint">Belum ada template, pesan bawaan dipakai. <a class="font-semibold underline" href="{{ route('wa-templates.index') }}">Buat template</a></p>
                                @else
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach($templateOptions as $template)
                                            <label class="ba-template">
                                                <input type="checkbox" name="billing_auto_generate_wa_template_ids[]" value="{{ $template['id'] }}" data-preview="generated" @checked(in_array($template['id'], $selectedGenerateTemplates, true))>
                                                <span class="truncate">{{ $template['title'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="ba-hint">Pilih beberapa agar isi pesan bervariasi. Tidak memilih = pesan bawaan.</p>
                                @endif
                            </div>
                        </div>
                        <div class="ba-chat">
                            <div class="ba-chat-head"><span>Contoh pesan</span><span data-preview-name="generated"></span></div>
                            <div class="ba-bubble"><span data-preview-text="generated"></span><small>09.00</small></div>
                        </div>
                    </div>
                </section>

                {{-- 2. Sebelum jatuh tempo --}}
                <section class="ba-card ba-step-card" style="--tone: var(--ba-amber)" data-section="before" data-off="{{ $beforeOn ? 0 : 1 }}">
                    <div class="ba-step-head">
                        <span class="ba-num">2</span>
                        <div class="min-w-0 flex-1">
                            <h3 class="ba-step-title">Pengingat sebelum jatuh tempo <span class="ba-off-note">· nonaktif</span></h3>
                            <p class="ba-step-desc">Untuk tagihan yang belum dibayar, supaya pelanggan bayar tepat waktu.</p>
                        </div>
                        <label class="ba-switch" title="Aktif / nonaktif"><input type="checkbox" name="billing_reminder_before_enabled" value="1" data-toggle="before" @checked($beforeOn)><span></span></label>
                    </div>
                    <div class="ba-step-body ba-split">
                        <div class="space-y-4">
                            <div>
                                <span class="ba-label">Kirim pada hari</span>
                                <div class="ba-chips">
                                    @foreach($beforeChipList as $day)
                                        <label class="ba-chip">
                                            <input type="checkbox" name="billing_reminder_before_days[]" value="{{ $day }}" data-flow @checked(in_array($day, $beforeDays, true))>
                                            <span>{{ $day === 0 ? 'Hari H' : 'H-' . $day }}</span>
                                        </label>
                                    @endforeach
                                    <span class="ba-add">
                                        <input type="number" min="0" max="31" placeholder="H-" data-add-input="before" aria-label="Tambah hari sebelum jatuh tempo">
                                        <button type="button" data-add="before" aria-label="Tambah">+</button>
                                    </span>
                                </div>
                                @error('billing_reminder_before_days')<div class="ba-error">{{ $message }}</div>@enderror
                                @error('billing_reminder_before_days.*')<div class="ba-error">{{ $message }}</div>@enderror
                                <p class="ba-hint">Hari H = tepat pada tanggal jatuh tempo.</p>
                            </div>
                            <div>
                                <label class="ba-label" for="before_template">Template WA</label>
                                <select id="before_template" name="billing_reminder_before_wa_template_id" class="ba-field" data-preview="before">
                                    <option value="">Pesan bawaan</option>
                                    @foreach($templateOptions as $template)
                                        <option value="{{ $template['id'] }}" @selected((int) $val('billing_reminder_before_wa_template_id') === $template['id'])>{{ $template['title'] }}</option>
                                    @endforeach
                                </select>
                                @error('billing_reminder_before_wa_template_id')<div class="ba-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="ba-chat">
                            <div class="ba-chat-head"><span>Contoh pesan · H-1</span><span data-preview-name="before"></span></div>
                            <div class="ba-bubble"><span data-preview-text="before"></span><small>08.00</small></div>
                        </div>
                    </div>
                </section>

                {{-- 3. Sesudah jatuh tempo --}}
                <section class="ba-card ba-step-card" style="--tone: var(--ba-rose)" data-section="after" data-off="{{ $afterOn ? 0 : 1 }}">
                    <div class="ba-step-head">
                        <span class="ba-num">3</span>
                        <div class="min-w-0 flex-1">
                            <h3 class="ba-step-title">Pengingat sesudah jatuh tempo <span class="ba-off-note">· nonaktif</span></h3>
                            <p class="ba-step-desc">Untuk pelanggan yang belum bayar. Tunggakan beberapa bulan digabung jadi satu pesan.</p>
                        </div>
                        <label class="ba-switch" title="Aktif / nonaktif"><input type="checkbox" name="billing_overdue_wa_enabled" value="1" data-toggle="after" @checked($afterOn)><span></span></label>
                    </div>
                    <div class="ba-step-body ba-split">
                        <div class="space-y-4">
                            <div>
                                <span class="ba-label">Kirim pada hari</span>
                                <div class="ba-chips">
                                    @foreach($afterChipList as $day)
                                        <label class="ba-chip">
                                            <input type="checkbox" name="billing_reminder_after_days[]" value="{{ $day }}" data-flow @checked(in_array($day, $afterDays, true))>
                                            <span>H+{{ $day }}</span>
                                        </label>
                                    @endforeach
                                    <span class="ba-add">
                                        <input type="number" min="1" max="120" placeholder="H+" data-add-input="after" aria-label="Tambah hari sesudah jatuh tempo">
                                        <button type="button" data-add="after" aria-label="Tambah">+</button>
                                    </span>
                                </div>
                                @error('billing_reminder_after_days')<div class="ba-error">{{ $message }}</div>@enderror
                                @error('billing_reminder_after_days.*')<div class="ba-error">{{ $message }}</div>@enderror
                                <p class="ba-hint">Maksimal 10 hari. Ditahan selama pelanggan punya janji bayar yang belum lewat.</p>
                            </div>
                            <div>
                                <label class="ba-label" for="after_template">Template WA</label>
                                <select id="after_template" name="billing_overdue_wa_template_id" class="ba-field" data-preview="after">
                                    <option value="">Pesan bawaan</option>
                                    @foreach($templateOptions as $template)
                                        <option value="{{ $template['id'] }}" @selected((int) $val('billing_overdue_wa_template_id') === $template['id'])>{{ $template['title'] }}</option>
                                    @endforeach
                                </select>
                                @error('billing_overdue_wa_template_id')<div class="ba-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="ba-chat">
                            <div class="ba-chat-head"><span>Contoh pesan · H+3</span><span data-preview-name="after"></span></div>
                            <div class="ba-bubble"><span data-preview-text="after"></span><small>08.00</small></div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Kolom samping --}}
            <aside class="min-w-0 space-y-5 xl:sticky xl:top-24 xl:self-start">
                <section class="ba-card p-4">
                    <h2 class="text-sm font-bold">Jam kirim</h2>
                    <p class="ba-muted mt-0.5 text-xs">Berlaku untuk semua pengingat otomatis.</p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="ba-label" for="reminder_time">Kirim pengingat mulai jam</label>
                            <input id="reminder_time" type="time" name="billing_reminder_time" value="{{ $val('billing_reminder_time') }}" class="ba-field @error('billing_reminder_time') ba-invalid @enderror">
                            @error('billing_reminder_time')<div class="ba-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <span class="ba-label">Jeda acak antar pesan</span>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="ba-suffix">
                                    <input type="number" min="0" max="300" name="billing_auto_generate_wa_delay_min" value="{{ $val('billing_auto_generate_wa_delay_min') }}" class="ba-field @error('billing_auto_generate_wa_delay_min') ba-invalid @enderror" aria-label="Jeda minimal">
                                    <span>dtk</span>
                                </div>
                                <div class="ba-suffix">
                                    <input type="number" min="0" max="300" name="billing_auto_generate_wa_delay_max" value="{{ $val('billing_auto_generate_wa_delay_max') }}" class="ba-field @error('billing_auto_generate_wa_delay_max') ba-invalid @enderror" aria-label="Jeda maksimal">
                                    <span>dtk</span>
                                </div>
                            </div>
                            @error('billing_auto_generate_wa_delay_max')<div class="ba-error">{{ $message }}</div>@enderror
                            <p class="ba-hint">Membantu nomor WA tidak dianggap spam.</p>
                        </div>
                    </div>
                </section>

                <section class="ba-card p-4">
                    <h2 class="text-sm font-bold">Janji bayar</h2>
                    <p class="ba-muted mt-0.5 text-xs">Dikirim sekali pada tanggal janji bayar yang dicatat di tagihan.</p>
                    <div class="mt-4">
                        <label class="ba-label" for="promise_template">Template WA</label>
                        <select id="promise_template" name="billing_payment_promise_wa_template_id" class="ba-field" data-preview="promise">
                            <option value="">Pesan bawaan</option>
                            @foreach($templateOptions as $template)
                                <option value="{{ $template['id'] }}" @selected((int) $val('billing_payment_promise_wa_template_id') === $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                        <details class="mt-2">
                            <summary class="flex items-center gap-1 text-xs font-semibold" style="color: var(--ba-primary)">
                                <svg class="ba-caret h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                Contoh pesan
                            </summary>
                            <div class="ba-bubble mt-2"><span data-preview-text="promise"></span><small>08.00</small></div>
                        </details>
                    </div>
                </section>

                <section class="ba-card">
                    <details>
                        <summary class="flex items-center justify-between gap-2 p-4">
                            <span>
                                <span class="block text-sm font-bold" style="color: var(--ba-strong)">Kode isian template</span>
                                <span class="ba-muted block text-xs">Klik untuk menyalin ke template WA</span>
                            </span>
                            <svg class="ba-caret ba-soft h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </summary>
                        <div class="space-y-0.5 px-2 pb-3">
                            @foreach($placeholders as $code => $label)
                                <button type="button" class="ba-ph" data-copy="{{ $code }}"><code>{{ $code }}</code><span>{{ $label }}</span></button>
                            @endforeach
                        </div>
                    </details>
                </section>
            </aside>
        </div>

        <div class="ba-savebar mt-5">
            <p id="ba-dirty">Semua pengaturan dijalankan scheduler server secara otomatis.</p>
            <div class="flex gap-2">
                <a href="{{ route('invoices.automation-settings') }}" class="ba-btn">Batal</a>
                <button type="submit" class="ba-btn ba-btn-primary">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan pengaturan
                </button>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('ba-form');
    const flow = document.getElementById('ba-flow');
    if (!form || !flow) return;

    const templates = @json($templateContents);
    const defaults = @json($defaultMessages);
    const sample = {
        '{nama}': 'Nama Pelanggan', '{nama_pelanggan}': 'Nama Pelanggan',
        '{no_invoice}': 'INV-202610-0001', '{nomor_tagihan}': 'INV-202610-0001',
        '{bulan}': 'Oktober', '{tahun}': '2026', '{periode}': 'Oktober 2026',
        '{jumlah}': 'Rp 150.000', '{nominal}': 'Rp 150.000',
        '{tanggal_jatuh_tempo}': '10 Oktober 2026', '{odp}': 'ODP-01', '{nama_odp}': 'ODP-01',
        '{jumlah_tunggakan}': '1', '{tanggal_janji_bayar}': '15 Oktober 2026', '{catatan_janji_bayar}': '-',
        '{rincian_tagihan}': '- Nama Pelanggan | Oktober 2026 | Rp 150.000', '{jumlah_pelanggan}': '1',
    };
    const extra = {
        generated: { '{sisa_hari}': 'dalam 3 hari', '{hari_terlambat}': '0' },
        before: { '{sisa_hari}': 'besok', '{hari_terlambat}': '0' },
        after: { '{sisa_hari}': '-', '{hari_terlambat}': '3' },
        promise: { '{sisa_hari}': '-', '{hari_terlambat}': '5' },
    };

    const fill = (text, type) => {
        const map = Object.assign({}, sample, extra[type]);
        return text.replace(/\{[a-z_]+\}/g, (m) => (m in map ? map[m] : m));
    };
    const checked = (name) => Array.from(form.querySelectorAll(`input[name="${name}[]"]:checked`)).map((el) => parseInt(el.value, 10));
    const isOn = (name) => !!form.querySelector(`input[name="${name}"]`)?.checked;

    function renderPreview(type) {
        const target = document.querySelector(`[data-preview-text="${type}"]`);
        const nameEl = document.querySelector(`[data-preview-name="${type}"]`);
        if (!target) return;

        let id = null;
        if (type === 'generated') {
            id = checked('billing_auto_generate_wa_template_ids')[0] ?? null;
        } else {
            id = form.querySelector(`select[data-preview="${type}"]`)?.value || null;
        }

        const content = id && templates[id] ? templates[id] : defaults[type];
        target.textContent = fill(content || '', type);
        if (nameEl) nameEl.textContent = id && templates[id] ? 'Template' : 'Bawaan';
    }

    function renderFlow() {
        const steps = [];
        const generateOn = isOn('billing_auto_generate_enabled');
        const sendWa = isOn('billing_auto_generate_send_wa');
        const issueDay = parseInt(form.querySelector('[name="billing_auto_generate_days_before_due"]').value || '0', 10);

        if (generateOn) {
            steps.push({ at: -issueDay - 0.1, label: issueDay === 0 ? 'Hari H' : 'H-' + issueDay, note: sendWa ? 'Terbit + WA' : 'Tagihan terbit', color: 'var(--ba-sky)' });
        }
        if (isOn('billing_reminder_before_enabled')) {
            checked('billing_reminder_before_days').forEach((d) => {
                if (generateOn && sendWa && d === issueDay) return;
                steps.push({ at: -d, label: d === 0 ? 'Hari H' : 'H-' + d, note: 'Pengingat', color: 'var(--ba-amber)' });
            });
        }
        steps.push({ at: 0.5, label: 'Jatuh tempo', note: 'Tanggal tagih', color: 'var(--ba-violet)', due: true });
        if (isOn('billing_overdue_wa_enabled')) {
            checked('billing_reminder_after_days').forEach((d) => steps.push({ at: d, label: 'H+' + d, note: 'Belum bayar', color: 'var(--ba-rose)' }));
        }

        steps.sort((a, b) => a.at - b.at);
        flow.replaceChildren(...steps.map((s) => {
            const el = document.createElement('div');
            el.className = 'ba-flow-step' + (s.due ? ' is-due' : '');
            el.style.setProperty('--dot', s.color);
            const dot = document.createElement('span'); dot.className = 'ba-flow-dot';
            const label = document.createElement('b'); label.textContent = s.label;
            const note = document.createElement('small'); note.textContent = s.note;
            el.append(dot, label, note);
            return el;
        }));
    }

    // Aktif / nonaktif per bagian
    form.querySelectorAll('[data-toggle]').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const card = form.querySelector(`[data-section="${toggle.dataset.toggle}"]`);
            if (card) card.dataset.off = toggle.checked ? '0' : '1';
            renderFlow();
        });
    });

    // Tambah hari sendiri
    form.querySelectorAll('[data-add]').forEach((button) => {
        const type = button.dataset.add;
        const input = form.querySelector(`[data-add-input="${type}"]`);
        const name = type === 'before' ? 'billing_reminder_before_days[]' : 'billing_reminder_after_days[]';
        const min = parseInt(input.min, 10), max = parseInt(input.max, 10);

        const add = () => {
            const day = parseInt(input.value, 10);
            if (Number.isNaN(day) || day < min || day > max) { input.focus(); return; }
            let box = form.querySelector(`input[name="${name}"][value="${day}"]`);
            if (!box) {
                const chip = document.createElement('label');
                chip.className = 'ba-chip';
                box = document.createElement('input');
                box.type = 'checkbox'; box.name = name; box.value = day;
                box.addEventListener('change', renderFlow);
                const text = document.createElement('span');
                text.textContent = type === 'before' ? (day === 0 ? 'Hari H' : 'H-' + day) : 'H+' + day;
                chip.append(box, text);
                button.parentElement.before(chip);
            }
            box.checked = true;
            input.value = '';
            renderFlow();
            markDirty();
        };

        button.addEventListener('click', add);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); add(); } });
    });

    form.querySelectorAll('[data-flow]').forEach((el) => ['input', 'change'].forEach((ev) => el.addEventListener(ev, renderFlow)));
    form.querySelectorAll('[data-preview]').forEach((el) => el.addEventListener('change', () => renderPreview(el.dataset.preview)));

    // Salin kode isian
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            try { await navigator.clipboard.writeText(button.dataset.copy); } catch (e) { return; }
            const label = button.querySelector('span');
            const original = label.textContent;
            label.textContent = 'Disalin ✓';
            setTimeout(() => { label.textContent = original; }, 1200);
        });
    });

    // Tanda ada perubahan
    const dirty = document.getElementById('ba-dirty');
    function markDirty() {
        dirty.textContent = 'Ada perubahan yang belum disimpan.';
        dirty.classList.add('is-dirty');
    }
    form.addEventListener('change', markDirty);

    ['generated', 'before', 'after', 'promise'].forEach(renderPreview);
    renderFlow();
})();
</script>
@endsection
