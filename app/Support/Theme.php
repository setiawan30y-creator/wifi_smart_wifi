<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Warna tema aplikasi per tenant (Pengaturan → Tampilan).
 *
 * resources/css/app.css menurunkan seluruh palet Tailwind dari beberapa warna dasar
 * (--brand, --accent, --success, --warning, --danger, --info). Kelas ini hanya
 * menyimpan warna dasar tersebut dan mencetaknya sebagai <style>:root{...}</style>
 * di layout, sehingga satu perubahan berlaku di semua halaman dan kartu.
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

    /** Cache per tenant selama satu request. */
    private static array $cache = [];

    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * Warna dan mode yang berlaku untuk tenant aktif (bawaan bila belum diatur).
     *
     * @return array{colors: array<string, string>, mode: string}
     */
    public static function current(): array
    {
        $tenantKey = (string) (app(\App\Support\Tenancy\TenantContext::class)->id() ?? 'none');

        if (isset(self::$cache[$tenantKey])) {
            return self::$cache[$tenantKey];
        }

        $stored = Setting::query()->whereIn('key', [...array_keys(self::COLORS), 'theme_mode'])->pluck('value', 'key');

        $colors = [];
        foreach (self::COLORS as $key => [, $default]) {
            $colors[$key] = self::normalizeHex($stored[$key] ?? null) ?? $default;
        }

        $mode = (string) ($stored['theme_mode'] ?? 'system');

        return self::$cache[$tenantKey] = [
            'colors' => $colors,
            'mode' => array_key_exists($mode, self::MODES) ? $mode : 'system',
        ];
    }

    /** Isi untuk <style> di layout. Hanya warna yang berbeda dari bawaan yang dicetak. */
    public static function css(): string
    {
        $lines = [];

        foreach (self::current()['colors'] as $key => $value) {
            [$variable, $default] = self::COLORS[$key];
            if ($value !== $default) {
                $lines[] = "{$variable}:{$value}";
            }
        }

        return $lines ? ':root{' . implode(';', $lines) . '}' : '';
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
}
