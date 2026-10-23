{{-- Pengaturan → Tampilan: warna tema tenant. Semua halaman mengikuti warna ini. --}}
@php
    $theme = \App\Support\Theme::current();
    $themeColors = collect(\App\Support\Theme::COLORS)->map(fn ($meta, $key) => [
        'key' => $key, 'var' => $meta[0], 'default' => $meta[1], 'label' => $meta[2],
        'value' => old($key, $theme['colors'][$key]),
    ]);
    $mainColors = $themeColors->only(['theme_brand', 'theme_accent']);
    $statusColors = $themeColors->except(['theme_brand', 'theme_accent']);
    $themeMode = old('theme_mode', $theme['mode']);
@endphp

<div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <form id="theme-form" action="{{ route('settings.theme') }}" method="POST" class="space-y-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 p-6 md:p-8">
        @csrf
        <div>
            <h3 class="text-xl font-bold text-white">Warna Tampilan</h3>
            <p class="mt-1 text-sm text-slate-400">Satu pengaturan untuk seluruh aplikasi: menu, tombol, kartu, grafik, dan label status mengikuti warna di sini. Perubahan langsung terlihat sebagai pratinjau sebelum disimpan.</p>
        </div>

        @if($errors->hasAny(array_merge(array_keys(\App\Support\Theme::COLORS), ['theme_mode'])))
            <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-xs text-rose-300">
                @foreach(array_merge(array_keys(\App\Support\Theme::COLORS), ['theme_mode']) as $field)
                    @error($field)<div>{{ $message }}</div>@enderror
                @endforeach
            </div>
        @endif

        {{-- Pilihan cepat --}}
        <div>
            <div class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Pilihan cepat</div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach(\App\Support\Theme::PRESETS as $presetKey => [$presetName, $presetBrand, $presetAccent])
                    <button type="button" data-theme-preset data-brand="{{ $presetBrand }}" data-accent="{{ $presetAccent }}"
                        class="theme-preset group flex flex-col gap-2 rounded-2xl border border-slate-800 bg-slate-950/40 p-3 text-left transition-colors hover:border-slate-600">
                        <span class="flex h-9 overflow-hidden rounded-lg">
                            <span class="flex-[2]" style="background: {{ $presetBrand }}"></span>
                            <span class="flex-1" style="background: {{ $presetAccent }}"></span>
                        </span>
                        <span class="text-xs font-semibold text-slate-200">{{ $presetName }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Warna utama & kedua --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach($mainColors as $color)
                <div class="rounded-2xl border border-slate-800 bg-slate-950/30 p-4">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500" for="{{ $color['key'] }}">{{ $color['label'] }}</label>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ $color['key'] === 'theme_brand' ? 'Menu aktif, tombol utama, tautan, grafik.' : 'Gradasi, aksen, dan sorotan.' }}
                    </p>
                    <div class="mt-3 flex items-center gap-3">
                        <input type="color" value="{{ $color['value'] }}" data-color-picker="{{ $color['key'] }}" class="h-11 w-14 shrink-0 cursor-pointer rounded-lg border border-slate-700 bg-transparent p-1" aria-label="{{ $color['label'] }}">
                        <input type="text" id="{{ $color['key'] }}" name="{{ $color['key'] }}" value="{{ $color['value'] }}" data-color-input="{{ $color['key'] }}" data-var="{{ $color['var'] }}" data-default="{{ $color['default'] }}" maxlength="7" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 font-mono text-sm uppercase text-slate-200">
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Warna status --}}
        <details class="group rounded-2xl border border-slate-800 bg-slate-950/30" @if($errors->hasAny(array_keys($statusColors->all()))) open @endif>
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
                        <input type="color" value="{{ $color['value'] }}" data-color-picker="{{ $color['key'] }}" class="h-9 w-11 shrink-0 cursor-pointer rounded-lg border border-slate-700 bg-transparent p-0.5" aria-label="{{ $color['label'] }}">
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-semibold text-slate-300" for="{{ $color['key'] }}">{{ $color['label'] }}</label>
                            <input type="text" id="{{ $color['key'] }}" name="{{ $color['key'] }}" value="{{ $color['value'] }}" data-color-input="{{ $color['key'] }}" data-var="{{ $color['var'] }}" data-default="{{ $color['default'] }}" maxlength="7" class="mt-1 w-full rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 font-mono text-xs uppercase text-slate-200">
                        </div>
                    </div>
                @endforeach
            </div>
        </details>

        {{-- Mode bawaan --}}
        <div>
            <div class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Mode bawaan</div>
            <div class="grid grid-cols-3 gap-2">
                @foreach(\App\Support\Theme::MODES as $modeKey => $modeLabel)
                    <label class="theme-mode flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2.5 text-xs font-semibold text-slate-300 transition-colors hover:border-slate-600">
                        <input type="radio" name="theme_mode" value="{{ $modeKey }}" class="sr-only" @checked($themeMode === $modeKey)>
                        {{ $modeLabel }}
                    </label>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-slate-500">Dipakai untuk pengguna yang belum memilih sendiri lewat tombol matahari/bulan di bagian atas.</p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800/80 pt-5">
            <button type="submit" form="theme-reset-form" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200">Kembalikan bawaan</button>
            <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition-colors hover:bg-indigo-500">Simpan warna</button>
        </div>
    </form>

    <form id="theme-reset-form" action="{{ route('settings.theme.reset') }}" method="POST" class="hidden" onsubmit="return confirm('Kembalikan semua warna ke bawaan?')">@csrf</form>

    {{-- Pratinjau --}}
    <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
        <div class="rounded-3xl border border-slate-800/60 bg-slate-900/40 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h4 class="text-sm font-bold text-white">Pratinjau</h4>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Contoh komponen</span>
            </div>

            <div class="space-y-4">
                <div class="flex items-center gap-2 rounded-xl bg-indigo-600 px-3 py-2.5 text-sm font-semibold text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z"/></svg>
                    Menu aktif
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-950/40 p-4">
                    <div class="text-[11px] font-semibold text-slate-400">Dana terkumpul</div>
                    <div class="mt-1 text-xl font-extrabold text-white">Rp 12.450.000</div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
                        <div class="h-full w-2/3 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                    </div>
                    <div class="mt-1.5 text-[11px] text-indigo-400">67% dari target bulan ini</div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-bold text-emerald-400">Lunas</span>
                    <span class="rounded-full border border-rose-500/30 bg-rose-500/10 px-2.5 py-1 text-[11px] font-bold text-rose-400">Belum bayar</span>
                    <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-[11px] font-bold text-amber-400">Jatuh tempo</span>
                    <span class="rounded-full border border-sky-500/30 bg-sky-500/10 px-2.5 py-1 text-[11px] font-bold text-sky-400">Info</span>
                </div>

                <div class="flex gap-2">
                    <span class="flex-1 rounded-xl bg-indigo-600 px-3 py-2 text-center text-xs font-semibold text-white">Tombol utama</span>
                    <span class="flex-1 rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-center text-xs font-semibold text-slate-200">Tombol biasa</span>
                </div>

                <input type="text" value="Kolom isian" readonly class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200" aria-label="Contoh kolom isian">
            </div>
        </div>
        <p class="px-1 text-xs text-slate-500">Seluruh halaman ini ikut berubah saat Anda memilih warna, sehingga hasilnya bisa dilihat sebelum disimpan.</p>
    </aside>
</div>

<style>
    .theme-preset.is-active { border-color: var(--brand-500); box-shadow: 0 0 0 3px color-mix(in oklab, var(--brand-500) 22%, transparent); }
    .theme-mode:has(input:checked) { border-color: var(--brand-500); background: color-mix(in oklab, var(--brand-500) 12%, transparent); color: var(--on-surface-strong); }
    .theme-mode:has(input:focus-visible) { box-shadow: 0 0 0 3px var(--ui-focus); }
</style>

<script>
(function () {
    const form = document.getElementById('theme-form');
    if (!form) return;

    const root = document.documentElement;
    const normalize = (value) => {
        let v = String(value || '').trim().replace(/^#/, '').toLowerCase();
        if (/^[0-9a-f]{3}$/.test(v)) v = v.split('').map((c) => c + c).join('');
        return /^[0-9a-f]{6}$/.test(v) ? '#' + v : null;
    };

    function apply(key, value, from) {
        const hex = normalize(value);
        const input = form.querySelector(`[data-color-input="${key}"]`);
        const picker = form.querySelector(`[data-color-picker="${key}"]`);
        if (!input) return;
        input.classList.toggle('border-rose-500', !hex);
        if (!hex) return;
        if (from !== 'input') input.value = hex;
        if (picker && from !== 'picker') picker.value = hex;
        root.style.setProperty(input.dataset.var, hex);
        markPreset();
    }

    function markPreset() {
        const brand = normalize(form.querySelector('[data-color-input="theme_brand"]').value);
        const accent = normalize(form.querySelector('[data-color-input="theme_accent"]').value);
        form.querySelectorAll('[data-theme-preset]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.brand === brand && button.dataset.accent === accent);
        });
    }

    form.querySelectorAll('[data-color-picker]').forEach((picker) => {
        picker.addEventListener('input', () => apply(picker.dataset.colorPicker, picker.value, 'picker'));
    });
    form.querySelectorAll('[data-color-input]').forEach((input) => {
        input.addEventListener('input', () => apply(input.dataset.colorInput, input.value, 'input'));
        input.addEventListener('blur', () => { const hex = normalize(input.value); if (hex) input.value = hex; });
    });
    form.querySelectorAll('[data-theme-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            apply('theme_brand', button.dataset.brand);
            apply('theme_accent', button.dataset.accent);
        });
    });
    form.querySelectorAll('input[name="theme_mode"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            if (radio.value !== 'system') root.dataset.theme = radio.value;
        });
    });

    markPreset();
})();
</script>
