<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Tampilan aplikasi per tenant (Pengaturan → Tampilan & Warna).
 *
 * resources/css/app.css memakai token CSS untuk semua warna dan bentuk. Kelas ini
 * menyimpan pilihan tenant dan mencetaknya sebagai <style> yang menimpa token bawaan:
 *  - warna dasar (utama, kedua, status) — berlaku di mode gelap & terang;
 *  - huruf, ukuran teks, sudut, bayangan, tabel belang;
 *  - warna permukaan (latar, sidebar, header, kartu, garis, kolom isian, tabel)
 *    yang diatur terpisah untuk mode gelap dan mode terang.
 */
final class Theme
{
    /** key setting => [variabel CSS, warna bawaan, label] */
    public const COLORS = [
        'theme_brand' => ['--brand', '#6366f1', 'Warna utama'],
        'theme_accent' => ['--accent', '#a855f7', 'Warna kedua'],
        'theme_success' => ['--success', '#10b981', 'Berhasil / lunas'],
        'theme_warning' => ['--warning', '#f59e0b', 'Peringatan'],
        'theme_danger' => ['--danger', '#f43f5e', 'Bahaya / tunggakan'],
        'theme_info' => ['--info', '#0ea5e9', 'Informasi'],
    ];

    public const MODES = ['system' => 'Ikuti perangkat', 'dark' => 'Gelap', 'light' => 'Terang'];

    /** Pilihan cepat warna utama + warna kedua. */
    public const PRESETS = [
        'indigo' => ['Indigo (bawaan)', '#6366f1', '#a855f7'],
        'biru' => ['Biru Laut', '#3b82f6', '#06b6d4'],
        'tosca' => ['Tosca', '#14b8a6', '#0ea5e9'],
        'hijau' => ['Hijau Daun', '#16a34a', '#84cc16'],
        'ungu' => ['Ungu', '#8b5cf6', '#ec4899'],
        'oranye' => ['Oranye', '#f97316', '#eab308'],
        'merah' => ['Merah Marun', '#e11d48', '#f97316'],
        'grafit' => ['Grafit', '#64748b', '#6366f1'],
    ];

    /** key => [label, ['css font-family', 'nama Google Font' | null]] */
    public const FONTS = [
        'jakarta' => ['Plus Jakarta Sans (bawaan)', ["'Plus Jakarta Sans'", null]],
        'inter' => ['Inter', ["'Inter'", 'Inter:wght@300;400;500;600;700;800']],
        'poppins' => ['Poppins', ["'Poppins'", 'Poppins:wght@300;400;500;600;700;800']],
        'nunito' => ['Nunito', ["'Nunito'", 'Nunito:wght@300;400;500;600;700;800']],
        'outfit' => ['Outfit', ["'Outfit'", null]],
        'roboto' => ['Roboto', ["'Roboto'", 'Roboto:wght@300;400;500;700;900']],
        'dmsans' => ['DM Sans', ["'DM Sans'", 'DM+Sans:wght@300;400;500;600;700;800']],
        'system' => ['Huruf perangkat', ['system-ui', null]],
    ];

    public const FONT_SIZES = ['small' => ['Kecil', '15px'], 'normal' => ['Normal', '16px'], 'large' => ['Besar', '17px']];

    public const RADII = ['sharp' => ['Tajam', '0.4'], 'normal' => ['Normal', '1'], 'round' => ['Bulat', '1.6']];

    public const SHADOWS = [
        'none' => ['Tanpa', 'none', 'none'],
        'soft' => ['Halus', '0 8px 24px -20px var(--shadow-color)', '0 6px 18px -16px var(--shadow-color)'],
        'normal' => ['Sedang', '0 16px 40px -32px var(--shadow-color)', '0 14px 32px -28px var(--shadow-color)'],
        'strong' => ['Tebal', '0 22px 48px -22px var(--shadow-color)', '0 18px 40px -22px var(--shadow-color)'],
    ];

    /**
     * Permukaan yang warnanya diatur per mode.
     * key => [label, keterangan, bawaan gelap, bawaan terang, variabel CSS yang diisi]
     */
    public const SURFACES = [
        'page' => ['Latar halaman', 'Warna dasar di belakang semua kartu.', '#070b14', '#f1f5f9', ['--page-bg']],
        'sidebar' => ['Sidebar', 'Menu samping (dan menu bawah di HP).', '#080d1a', '#ffffff', ['--sidebar-bg']],
        'header' => ['Header atas', 'Bilah judul di bagian atas.', '#080d1a', '#ffffff', ['--header-bg']],
        'card' => ['Kartu', 'Latar kartu, panel, dan jendela.', '#0f172a', '#ffffff', ['--card-bg', '--neutral-900']],
        'card_border' => ['Garis kartu', 'Garis tepi kartu, panel, dan pemisah.', '#1f2a3d', '#dbe3ef', ['--card-border']],
        'input' => ['Kolom isian', 'Latar kotak isian dan pilihan.', '#030712', '#ffffff', ['--input-bg']],
        'table_head' => ['Kepala tabel', 'Baris judul kolom tabel.', '#0f172a', '#e2e8f0', ['--table-head-bg']],
        'table_border' => ['Garis tabel', 'Garis antar baris tabel.', '#1e293b', '#dbe3ef', ['--table-border']],
        'table_hover' => ['Baris disorot', 'Warna baris saat kursor di atasnya.', '#151e2e', '#eef2ff', ['--table-hover']],
    ];

    /** Permukaan yang diberi kontras teks otomatis. */
    public const TONED = ['sidebar', 'header'];

    public const OPTION_DEFAULTS = [
        'theme_font' => 'jakarta',
        'theme_font_size' => 'normal',
        'theme_radius' => 'normal',
        'theme_shadow' => 'normal',
        'theme_table_striped' => '0',
        'theme_mode' => 'system',
    ];

    /** Cache per tenant selama satu request. */
    private static array $cache = [];

    public static function flush(): void
    {
        self::$cache = [];
    }

    /** Semua key setting yang dipakai tema. */
    public static function keys(): array
    {
        $keys = [...array_keys(self::COLORS), ...array_keys(self::OPTION_DEFAULTS)];

        foreach (['dark', 'light'] as $mode) {
            foreach (array_keys(self::SURFACES) as $surface) {
                $keys[] = self::surfaceKey($mode, $surface);
            }
        }

        return $keys;
    }

    public static function surfaceKey(string $mode, string $surface): string
    {
        return "theme_{$mode}_{$surface}";
    }

    /**
     * Pengaturan tenant aktif yang sudah dibersihkan.
     *
     * @return array{colors: array<string,string>, options: array<string,string>, surfaces: array{dark: array<string,?string>, light: array<string,?string>}, mode: string}
     */
    public static function current(): array
    {
        $tenantKey = (string) (app(\App\Support\Tenancy\TenantContext::class)->id() ?? 'none');

        if (isset(self::$cache[$tenantKey])) {
            return self::$cache[$tenantKey];
        }

        $stored = Setting::query()->whereIn('key', self::keys())->pluck('value', 'key')->all();

        return self::$cache[$tenantKey] = self::resolve($stored);
    }

    /**
     * Ubah data mentah (setting tersimpan atau isian form) menjadi pengaturan yang valid.
     */
    public static function resolve(array $raw): array
    {
        $colors = [];
        foreach (self::COLORS as $key => [, $default]) {
            $colors[$key] = self::normalizeHex($raw[$key] ?? null) ?? $default;
        }

        $options = [];
        $choices = [
            'theme_font' => self::FONTS,
            'theme_font_size' => self::FONT_SIZES,
            'theme_radius' => self::RADII,
            'theme_shadow' => self::SHADOWS,
            'theme_mode' => self::MODES,
        ];
        foreach (self::OPTION_DEFAULTS as $key => $default) {
            $value = (string) ($raw[$key] ?? $default);

            $options[$key] = match (true) {
                $key === 'theme_table_striped' => in_array($value, ['1', 'true', 'on'], true) ? '1' : '0',
                default => array_key_exists($value, $choices[$key]) ? $value : $default,
            };
        }

        $surfaces = [];
        foreach (['dark', 'light'] as $mode) {
            foreach (array_keys(self::SURFACES) as $surface) {
                $surfaces[$mode][$surface] = self::normalizeHex($raw[self::surfaceKey($mode, $surface)] ?? null);
            }
        }

        return ['colors' => $colors, 'options' => $options, 'surfaces' => $surfaces, 'mode' => $options['theme_mode']];
    }

    /**
     * CSS penimpa token. Hanya nilai yang berbeda dari bawaan yang dicetak.
     */
    public static function css(?array $theme = null): string
    {
        $theme ??= self::current();
        $options = $theme['options'];
        $root = [];

        foreach ($theme['colors'] as $key => $value) {
            [$variable, $default] = self::COLORS[$key];
            if ($value !== $default) {
                $root[] = "{$variable}:{$value}";
            }
        }

        if ($options['theme_font'] !== 'jakarta') {
            $root[] = '--app-font:' . self::FONTS[$options['theme_font']][1][0];
        }
        if ($options['theme_font_size'] !== 'normal') {
            $root[] = '--app-font-size:' . self::FONT_SIZES[$options['theme_font_size']][1];
        }
        if ($options['theme_radius'] !== 'normal') {
            $root[] = '--radius-scale:' . self::RADII[$options['theme_radius']][1];
        }
        if ($options['theme_shadow'] !== 'normal') {
            $root[] = '--shadow-card:' . self::SHADOWS[$options['theme_shadow']][1];
            $root[] = '--shadow-soft:' . self::SHADOWS[$options['theme_shadow']][2];
        }
        if ($options['theme_table_striped'] === '1') {
            $root[] = '--table-stripe:color-mix(in oklab, var(--neutral-800) 28%, transparent)';
        }

        $css = $root ? ':root{' . implode(';', $root) . '}' : '';

        foreach (['dark' => 'html:not([data-theme="light"])', 'light' => 'html[data-theme="light"]'] as $mode => $selector) {
            $vars = [];

            foreach ($theme['surfaces'][$mode] as $surface => $value) {
                if ($value === null) {
                    continue;
                }

                foreach (self::SURFACES[$surface][4] as $variable) {
                    $vars[] = "{$variable}:{$value}";
                }

                if ($surface === 'page') {
                    $vars[] = "--page-gradient:linear-gradient(180deg,{$value} 0%,{$value} 100%)";
                }
                if ($surface === 'table_head') {
                    $vars[] = '--table-head-text:' . (self::isDark($value) ? '#cbd5e1' : '#334155');
                }
            }

            if ($vars) {
                $css .= $selector . '{' . implode(';', $vars) . '}';
            }
        }

        return $css;
    }

    /**
     * Atribut kontras untuk area berwarna latar pilihan tenant, mis.
     * data-tone-dark="light" bila sidebar mode gelap diberi warna terang.
     */
    public static function toneAttributes(string $surface, ?array $theme = null): string
    {
        $theme ??= self::current();
        $attributes = [];

        foreach (['dark', 'light'] as $mode) {
            $value = $theme['surfaces'][$mode][$surface] ?? null;
            if ($value !== null) {
                $attributes[] = 'data-tone-' . $mode . '="' . (self::isDark($value) ? 'dark' : 'light') . '"';
            }
        }

        return implode(' ', $attributes);
    }

    /** Nada (dark/light) per area untuk pratinjau JavaScript. */
    public static function tones(?array $theme = null): array
    {
        $theme ??= self::current();
        $tones = [];

        foreach (self::TONED as $surface) {
            foreach (['dark', 'light'] as $mode) {
                $value = $theme['surfaces'][$mode][$surface] ?? null;
                $tones[$surface][$mode] = $value === null ? null : (self::isDark($value) ? 'dark' : 'light');
            }
        }

        return $tones;
    }

    /** URL Google Fonts untuk huruf pilihan, atau null bila sudah dimuat/huruf perangkat. */
    public static function fontUrl(?array $theme = null): ?string
    {
        $theme ??= self::current();
        $family = self::FONTS[$theme['options']['theme_font']][1][1] ?? null;

        return $family ? 'https://fonts.googleapis.com/css2?family=' . $family . '&display=swap' : null;
    }

    public static function brand(): string
    {
        return self::current()['colors']['theme_brand'];
    }

    public static function defaultMode(): string
    {
        return self::current()['mode'];
    }

    public static function defaults(): array
    {
        return array_map(fn (array $meta) => $meta[1], self::COLORS);
    }

    /** "#ABC" / "abc123" => "#aabbcc"; selain warna hex valid => null. */
    public static function normalizeHex($value): ?string
    {
        $value = strtolower(ltrim(trim((string) $value), '#'));

        if (preg_match('/^[0-9a-f]{3}$/', $value)) {
            $value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
        }

        return preg_match('/^[0-9a-f]{6}$/', $value) ? '#' . $value : null;
    }

    /** Gelap bila teks putih lebih kontras daripada teks hitam (luminans WCAG < 0.179). */
    public static function isDark(string $hex): bool
    {
        [$r, $g, $b] = array_map(function ($channel) {
            $c = hexdec($channel) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) < 0.179;
    }
}
