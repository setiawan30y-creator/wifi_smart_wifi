{{-- Pengaturan → Tampilan & Warna. Semua halaman mengikuti pengaturan ini. --}}
@use('App\Support\Theme')
@php
    $theme = Theme::current();
    $opt = fn (string $key) => old($key, $theme['options'][$key]);
    $colorRows = collect(Theme::COLORS)->map(fn ($meta, $key) => [
        'key' => $key, 'default' => $meta[1], 'label' => $meta[2], 'value' => old($key, $theme['colors'][$key]),
    ]);
    $mainColors = $colorRows->only(['theme_brand', 'theme_accent']);
    $statusColors = $colorRows->except(['theme_brand', 'theme_accent']);
    $surfaceValue = fn (string $mode, string $surface) => old(Theme::surfaceKey($mode, $surface), $theme['surfaces'][$mode][$surface]);
    $customSurfaces = collect(['dark', 'light'])->sum(fn ($m) => collect($theme['surfaces'][$m])->filter()->count());
@endphp

<style>
    .ap-card { border: 1px solid var(--ui-border); background: var(--ui-card); border-radius: var(--radius-3xl); box-shadow: var(--ui-shadow-card); }
    .ap-section-title { font-size: 1rem; font-weight: 800; color: var(--ui-text-strong); }
    .ap-label { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--ui-text-soft); }
    .ap-seg { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: .35rem; padding: .3rem; border-radius: var(--radius-xl); background: color-mix(in oklab, var(--neutral-950) 45%, transparent); border: 1px solid var(--ui-border); }
    html[data-theme="light"] .ap-seg { background: var(--neutral-900); }
    .ap-seg label { position: relative; display: flex; align-items: center; justify-content: center; gap: .4rem; padding: .5rem .6rem; border-radius: var(--radius-lg); font-size: .76rem; font-weight: 700; color: var(--ui-text-muted); cursor: pointer; text-align: center; transition: background .12s ease, color .12s ease; }
    .ap-seg label:hover { color: var(--ui-text-strong); }
    .ap-seg input { position: absolute; opacity: 0; pointer-events: none; }
    .ap-seg label:has(input:checked) { background: var(--brand-600); color: #fff; box-shadow: 0 6px 16px -10px var(--brand-600); }
    .ap-seg label:has(input:focus-visible) { box-shadow: 0 0 0 3px var(--ui-focus); }
    .ap-preset.is-active { border-color: var(--brand-500) !important; box-shadow: 0 0 0 3px color-mix(in oklab, var(--brand-500) 22%, transparent); }
    .ap-swatch { width: 2.6rem; height: 2.6rem; flex-shrink: 0; border-radius: var(--radius-lg); border: 1px solid var(--ui-border-strong); padding: 0; cursor: pointer; background: transparent; overflow: hidden; }
    .ap-swatch::-webkit-color-swatch-wrapper { padding: 0; }
    .ap-swatch::-webkit-color-swatch { border: 0; }
    .ap-hex { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; text-transform: uppercase; }
    .ap-surface { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: .75rem; padding: .7rem .2rem; border-bottom: 1px solid var(--ui-border); }
    .ap-surface:last-child { border-bottom: 0; }
    .ap-surface-input { display: flex; align-items: center; gap: .5rem; }
    .ap-surface-input input[type="text"] { width: 6.6rem; }
    .ap-default-btn { font-size: .68rem; font-weight: 700; color: var(--ui-text-soft); padding: .3rem .45rem; border-radius: var(--radius-md); }
    .ap-default-btn:hover { color: var(--ui-text-strong); background: color-mix(in oklab, var(--neutral-800) 50%, transparent); }
    .ap-surface[data-custom="0"] .ap-default-btn { visibility: hidden; }
    .ap-surface[data-custom="0"] .ap-swatch { opacity: .55; }
    .ap-dot { display: inline-block; width: .5rem; height: .5rem; border-radius: 999px; background: var(--warning); margin-left: .35rem; vertical-align: middle; }
    .ap-surface[data-custom="0"] .ap-dot { display: none; }
    .ap-mock { display: grid; grid-template-columns: 4.6rem minmax(0, 1fr); min-height: 23rem; border-radius: var(--radius-2xl); overflow: hidden; border: 1px solid var(--ui-border); background: radial-gradient(circle at 16% -8%, var(--page-glow), transparent 18rem), var(--page-gradient); }
    .ap-mock-side { background: var(--sidebar-bg); border-right: 1px solid var(--ui-border); padding: .6rem .45rem; display: flex; flex-direction: column; gap: .3rem; }
    .ap-mock-head { background: var(--header-bg); border-bottom: 1px solid var(--ui-border); padding: .55rem .75rem; display: flex; align-items: center; justify-content: space-between; }
    .ap-fontsample { font-family: var(--font-sans); }
</style>

<div class="grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1fr)_25rem]">
    <form id="theme-form" action="{{ route('settings.theme') }}" method="POST" class="space-y-6" data-preview-url="{{ route('settings.theme.preview') }}">
        @csrf

        @if($errors->hasAny(Theme::keys()))
            <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-xs text-rose-300">
                <div class="font-bold">Tampilan belum tersimpan:</div>
                @foreach(Theme::keys() as $field)
                    @error($field)<div>{{ $message }}</div>@enderror
                @endforeach
            </div>
        @endif

        {{-- 1. Warna dasar --}}
        <section class="ap-card space-y-5 p-5 md:p-6">
            <div>
                <h3 class="ap-section-title">1. Warna dasar</h3>
                <p class="mt-1 text-sm text-slate-400">Menu aktif, tombol, tautan, grafik, dan label status. Berlaku di mode gelap dan terang.</p>
            </div>

            <div>
                <div class="ap-label mb-2.5">Pilihan cepat</div>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                    @foreach(Theme::PRESETS as [$presetName, $presetBrand, $presetAccent])
                        <button type="button" data-theme-preset data-brand="{{ $presetBrand }}" data-accent="{{ $presetAccent }}"
                            class="ap-preset flex flex-col gap-2 rounded-2xl border border-slate-800 bg-slate-950/40 p-2.5 text-left transition-colors hover:border-slate-600">
                            <span class="flex h-8 overflow-hidden rounded-lg">
                                <span class="flex-[2]" style="background: {{ $presetBrand }}"></span>
                                <span class="flex-1" style="background: {{ $presetAccent }}"></span>
                            </span>
                            <span class="text-xs font-semibold text-slate-200">{{ $presetName }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($mainColors as $color)
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/30 p-4">
                        <label class="ap-label" for="{{ $color['key'] }}">{{ $color['label'] }}</label>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $color['key'] === 'theme_brand' ? 'Menu aktif, tombol utama, tautan, grafik.' : 'Gradasi, aksen, dan sorotan.' }}</p>
                        <div class="mt-3 flex items-center gap-2.5">
                            <input type="color" value="{{ $color['value'] }}" data-picker-for="{{ $color['key'] }}" class="ap-swatch" aria-label="{{ $color['label'] }}">
                            <input type="text" id="{{ $color['key'] }}" name="{{ $color['key'] }}" value="{{ $color['value'] }}" data-hex maxlength="7" class="ap-hex w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                        </div>
                    </div>
                @endforeach
            </div>

            <details class="group rounded-2xl border border-slate-800 bg-slate-950/30" @if($errors->hasAny($statusColors->keys()->all())) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4">
                    <span>
                        <span class="block text-sm font-semibold text-slate-200">Warna status</span>
                        <span class="block text-xs text-slate-500">Lunas, peringatan, tunggakan, dan informasi. Biasanya tidak perlu diubah.</span>
                    </span>
                    <svg class="h-4 w-4 text-slate-500 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </summary>
                <div class="grid grid-cols-1 gap-3 px-4 pb-4 sm:grid-cols-2">
                    @foreach($statusColors as $color)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/40 p-3">
                            <input type="color" value="{{ $color['value'] }}" data-picker-for="{{ $color['key'] }}" class="ap-swatch" aria-label="{{ $color['label'] }}">
                            <div class="min-w-0 flex-1">
                                <label class="block text-xs font-semibold text-slate-300" for="{{ $color['key'] }}">{{ $color['label'] }}</label>
                                <input type="text" id="{{ $color['key'] }}" name="{{ $color['key'] }}" value="{{ $color['value'] }}" data-hex maxlength="7" class="ap-hex mt-1 w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200">
                            </div>
                        </div>
                    @endforeach
                </div>
            </details>
        </section>

        {{-- 2. Huruf & bentuk --}}
        <section class="ap-card space-y-5 p-5 md:p-6">
            <div>
                <h3 class="ap-section-title">2. Huruf &amp; bentuk</h3>
                <p class="mt-1 text-sm text-slate-400">Jenis huruf, ukuran teks, kelengkungan sudut kartu/tombol, dan bayangan kartu.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="ap-label mb-2 block" for="theme_font">Jenis huruf</label>
                    <select id="theme_font" name="theme_font" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200">
                        @foreach(Theme::FONTS as $fontKey => [$fontLabel])
                            <option value="{{ $fontKey }}" @selected($opt('theme_font') === $fontKey)>{{ $fontLabel }}</option>
                        @endforeach
                    </select>
                    <p class="ap-fontsample mt-2 text-sm text-slate-300">Tagihan Oktober 2026 · Rp 150.000 · 0812-0000-0000</p>
                </div>
                <div>
                    <span class="ap-label mb-2 block">Ukuran teks</span>
                    <div class="ap-seg">
                        @foreach(Theme::FONT_SIZES as $sizeKey => [$sizeLabel])
                            <label><input type="radio" name="theme_font_size" value="{{ $sizeKey }}" @checked($opt('theme_font_size') === $sizeKey)>{{ $sizeLabel }}</label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <span class="ap-label mb-2 block">Sudut kartu &amp; tombol</span>
                    <div class="ap-seg">
                        @foreach(Theme::RADII as $radiusKey => [$radiusLabel, $radiusScale])
                            <label>
                                <input type="radio" name="theme_radius" value="{{ $radiusKey }}" @checked($opt('theme_radius') === $radiusKey)>
                                <span class="inline-block h-3.5 w-3.5 border-2 border-current" style="border-radius: {{ (float) $radiusScale * 4 }}px; border-right-color: transparent; border-bottom-color: transparent"></span>
                                {{ $radiusLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <span class="ap-label mb-2 block">Bayangan kartu</span>
                    <div class="ap-seg">
                        @foreach(Theme::SHADOWS as $shadowKey => [$shadowLabel])
                            <label><input type="radio" name="theme_shadow" value="{{ $shadowKey }}" @checked($opt('theme_shadow') === $shadowKey)>{{ $shadowLabel }}</label>
                        @endforeach
                    </div>
                </div>
            </div>

            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-800 bg-slate-950/30 p-4">
                <span>
                    <span class="block text-sm font-semibold text-slate-200">Tabel belang</span>
                    <span class="block text-xs text-slate-500">Baris tabel berselang warna agar lebih mudah dibaca.</span>
                </span>
                <input type="hidden" name="theme_table_striped" value="0">
                <input type="checkbox" name="theme_table_striped" value="1" class="h-5 w-5 accent-indigo-600" @checked($opt('theme_table_striped') === '1')>
            </label>
        </section>

        {{-- 3. Warna permukaan --}}
        <section class="ap-card space-y-4 p-5 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="ap-section-title">3. Warna permukaan</h3>
                    <p class="mt-1 text-sm text-slate-400">Latar, sidebar, header, kartu, garis, kolom isian, dan tabel. Diatur terpisah untuk mode gelap dan terang; kosong = bawaan.</p>
                </div>
                <div class="ap-seg w-full sm:w-auto" role="tablist">
                    <label><input type="radio" name="_surface_mode" value="dark" data-surface-tab checked>Mode gelap</label>
                    <label><input type="radio" name="_surface_mode" value="light" data-surface-tab>Mode terang</label>
                </div>
            </div>

            @foreach(['dark', 'light'] as $mode)
                <div data-surface-panel="{{ $mode }}" @if($mode === 'light') hidden @endif>
                    @foreach(Theme::SURFACES as $surface => [$surfaceLabel, $surfaceHint, $defaultDark, $defaultLight])
                        @php
                            $key = Theme::surfaceKey($mode, $surface);
                            $value = $surfaceValue($mode, $surface);
                            $default = $mode === 'dark' ? $defaultDark : $defaultLight;
                        @endphp
                        <div class="ap-surface" data-custom="{{ $value ? 1 : 0 }}" data-default="{{ $default }}">
                            <div class="min-w-0">
                                <label class="text-sm font-semibold text-slate-200" for="{{ $key }}">{{ $surfaceLabel }}<span class="ap-dot" title="Diubah"></span></label>
                                <div class="text-xs text-slate-500">{{ $surfaceHint }}</div>
                            </div>
                            <div class="ap-surface-input">
                                <button type="button" class="ap-default-btn" data-surface-reset>Bawaan</button>
                                <input type="color" value="{{ $value ?: $default }}" data-picker-for="{{ $key }}" class="ap-swatch" aria-label="{{ $surfaceLabel }}">
                                <input type="text" id="{{ $key }}" name="{{ $key }}" value="{{ $value }}" placeholder="{{ strtoupper($default) }}" data-hex data-surface maxlength="7" class="ap-hex rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-2 text-xs text-slate-200">
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
            <p class="text-xs text-slate-500">Warna teks di sidebar dan header menyesuaikan otomatis agar tetap terbaca.</p>
        </section>

        {{-- 4. Mode bawaan --}}
        <section class="ap-card space-y-3 p-5 md:p-6">
            <h3 class="ap-section-title">4. Mode bawaan</h3>
            <div class="ap-seg">
                @foreach(Theme::MODES as $modeKey => $modeLabel)
                    <label><input type="radio" name="theme_mode" value="{{ $modeKey }}" @checked($opt('theme_mode') === $modeKey)>{{ $modeLabel }}</label>
                @endforeach
            </div>
            <p class="text-xs text-slate-500">Dipakai untuk pengguna yang belum memilih sendiri lewat tombol matahari/bulan di bagian atas.</p>
        </section>

        <div class="ap-card sticky bottom-3 z-20 flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <button type="submit" form="theme-reset-form" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200">Kembalikan semua ke bawaan</button>
            <div class="flex items-center gap-3">
                <span class="hidden text-xs text-slate-500 sm:inline" id="theme-status">Perubahan langsung terlihat sebagai pratinjau.</span>
                <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition-colors hover:bg-indigo-500">Simpan tampilan</button>
            </div>
        </div>
    </form>

    <form id="theme-reset-form" action="{{ route('settings.theme.reset') }}" method="POST" class="hidden" onsubmit="return confirm('Kembalikan semua warna, huruf, dan bentuk ke bawaan?')">@csrf</form>

    {{-- Pratinjau mini --}}
    <aside class="space-y-3 2xl:sticky 2xl:top-24 2xl:self-start">
        <div class="flex items-center justify-between px-1">
            <h4 class="text-sm font-bold text-white">Pratinjau</h4>
            <span class="text-xs text-slate-500" id="theme-preview-mode"></span>
        </div>
        <div class="ap-mock">
            <div class="ap-mock-side" data-preview-area="sidebar" {!! Theme::toneAttributes('sidebar') !!}>
                <div class="mb-2 flex h-8 w-8 items-center justify-center self-center rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <span class="rounded-lg bg-indigo-600 px-1.5 py-1.5 text-center text-[10px] font-semibold text-white">Tagihan</span>
                <span class="rounded-lg px-1.5 py-1.5 text-center text-[10px] font-medium text-slate-400">Pelanggan</span>
                <span class="rounded-lg px-1.5 py-1.5 text-center text-[10px] font-medium text-slate-400">Paket</span>
                <span class="rounded-lg px-1.5 py-1.5 text-center text-[10px] font-medium text-slate-400">ODP</span>
            </div>
            <div class="min-w-0">
                <div class="ap-mock-head" data-preview-area="header" {!! Theme::toneAttributes('header') !!}>
                    <span class="text-xs font-bold text-white">Tagihan &amp; Invoice</span>
                    <span class="h-5 w-5 rounded-full bg-slate-800"></span>
                </div>
                <div class="space-y-3 p-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-3">
                            <div class="text-[10px] font-semibold text-slate-400">Terkumpul</div>
                            <div class="text-sm font-extrabold text-white">Rp 12,4 jt</div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-800"><div class="h-full w-2/3 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500"></div></div>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-3">
                            <div class="text-[10px] font-semibold text-slate-400">Tertunggak</div>
                            <div class="text-sm font-extrabold text-rose-400">Rp 2,1 jt</div>
                            <div class="mt-1 text-[10px] text-slate-500">14 pelanggan</div>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60">
                        <table class="w-full text-left">
                            <thead><tr><th>Pelanggan</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr><td>Pelanggan A</td><td><span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-400">Lunas</span></td></tr>
                                <tr><td>Pelanggan B</td><td><span class="rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold text-rose-400">Belum bayar</span></td></tr>
                                <tr><td>Pelanggan C</td><td><span class="rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-400">Jatuh tempo</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <input type="text" readonly value="Kolom isian" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200" aria-label="Contoh kolom isian">
                    <div class="flex gap-2">
                        <span class="flex-1 rounded-xl bg-indigo-600 px-3 py-2 text-center text-xs font-semibold text-white">Simpan</span>
                        <span class="flex-1 rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-center text-xs font-semibold text-slate-200">Batal</span>
                    </div>
                </div>
            </div>
        </div>
        <p class="px-1 text-xs text-slate-500">Seluruh halaman ikut berubah selama Anda mengatur. Belum tersimpan sampai tombol <b>Simpan tampilan</b> ditekan.</p>
    </aside>
</div>

<script>
(function () {
    const form = document.getElementById('theme-form');
    if (!form) return;

    const root = document.documentElement;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const status = document.getElementById('theme-status');
    const modeLabel = document.getElementById('theme-preview-mode');
    const normalize = (value) => {
        let v = String(value || '').trim().replace(/^#/, '').toLowerCase();
        if (/^[0-9a-f]{3}$/.test(v)) v = v.split('').map((c) => c + c).join('');
        return /^[0-9a-f]{6}$/.test(v) ? '#' + v : null;
    };

    // --- Pratinjau dari server (satu sumber CSS dengan yang dipakai saat tersimpan) ---
    let timer = null;
    let controller = null;
    function schedulePreview() {
        clearTimeout(timer);
        timer = setTimeout(preview, 180);
    }

    async function preview() {
        if (controller) controller.abort();
        controller = new AbortController();
        const data = new FormData(form);
        try {
            const response = await fetch(form.dataset.previewUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: data,
                signal: controller.signal,
            });
            if (!response.ok) return;
            const json = await response.json();
            document.getElementById('tenant-theme').textContent = json.css;
            applyFont(json.font_url);
            applyTones(json.tones);
            if (status) status.textContent = 'Pratinjau aktif · belum disimpan';
        } catch (e) { /* dibatalkan atau offline: abaikan */ }
    }

    function applyFont(url) {
        let link = document.getElementById('tenant-theme-font');
        if (!url) { if (link) link.remove(); return; }
        if (!link) {
            link = document.createElement('link');
            link.id = 'tenant-theme-font';
            link.rel = 'stylesheet';
            document.head.appendChild(link);
        }
        if (link.href !== url) link.href = url;
    }

    function applyTones(tones) {
        const areas = {
            sidebar: '.app-sidebar, .android-bottom-nav, [data-preview-area="sidebar"]',
            header: 'main > header, [data-preview-area="header"]',
        };
        Object.entries(areas).forEach(([area, selector]) => {
            document.querySelectorAll(selector).forEach((el) => {
                ['dark', 'light'].forEach((mode) => {
                    const tone = tones?.[area]?.[mode];
                    if (tone) el.setAttribute('data-tone-' + mode, tone);
                    else el.removeAttribute('data-tone-' + mode);
                });
            });
        });
    }

    // --- Kolom warna: kotak warna <-> kode hex ---
    form.querySelectorAll('[data-picker-for]').forEach((picker) => {
        const input = document.getElementById(picker.dataset.pickerFor);
        picker.addEventListener('input', () => {
            input.value = picker.value;
            markSurface(input);
            markPreset();
            schedulePreview();
        });
    });
    form.querySelectorAll('[data-hex]').forEach((input) => {
        input.addEventListener('input', () => {
            const hex = normalize(input.value);
            const empty = input.value.trim() === '' && input.hasAttribute('data-surface');
            input.classList.toggle('border-rose-500', !hex && !empty);
            const picker = form.querySelector(`[data-picker-for="${input.id}"]`);
            if (hex && picker) picker.value = hex;
            markSurface(input);
            markPreset();
            if (hex || empty) schedulePreview();
        });
        input.addEventListener('blur', () => { const hex = normalize(input.value); if (hex) input.value = hex; });
    });

    function markSurface(input) {
        const row = input.closest('.ap-surface');
        if (!row) return;
        const custom = input.value.trim() !== '';
        row.dataset.custom = custom ? '1' : '0';
        if (!custom) {
            const picker = row.querySelector('[data-picker-for]');
            if (picker) picker.value = row.dataset.default;
        }
    }

    form.querySelectorAll('[data-surface-reset]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.closest('.ap-surface').querySelector('[data-surface]');
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    // --- Pilihan cepat ---
    function markPreset() {
        const brand = normalize(document.getElementById('theme_brand').value);
        const accent = normalize(document.getElementById('theme_accent').value);
        form.querySelectorAll('[data-theme-preset]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.brand === brand && button.dataset.accent === accent);
        });
    }
    form.querySelectorAll('[data-theme-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            [['theme_brand', button.dataset.brand], ['theme_accent', button.dataset.accent]].forEach(([id, value]) => {
                const input = document.getElementById(id);
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    });

    // --- Tab mode permukaan: ikut mengganti mode tampilan agar hasilnya terlihat ---
    function showSurfaceMode(mode) {
        form.querySelectorAll('[data-surface-panel]').forEach((panel) => { panel.hidden = panel.dataset.surfacePanel !== mode; });
        root.dataset.theme = mode;
        if (modeLabel) modeLabel.textContent = mode === 'dark' ? 'Mode gelap' : 'Mode terang';
    }
    form.querySelectorAll('[data-surface-tab]').forEach((radio) => {
        radio.addEventListener('change', () => showSurfaceMode(radio.value));
    });
    const current = root.dataset.theme === 'light' ? 'light' : 'dark';
    form.querySelector(`[data-surface-tab][value="${current}"]`).checked = true;
    showSurfaceMode(current);

    // Pilihan lain (huruf, ukuran, sudut, bayangan, tabel belang, mode)
    form.querySelectorAll('select, input[type="radio"]:not([data-surface-tab]), input[type="checkbox"]').forEach((el) => {
        el.addEventListener('change', schedulePreview);
    });

    markPreset();
})();
</script>
