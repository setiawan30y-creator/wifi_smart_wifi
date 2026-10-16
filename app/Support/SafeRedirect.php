<?php

namespace App\Support;

/**
 * Validasi URL tujuan redirect dari input form (mis. "redirect_to"),
 * agar tidak bisa dipakai mengarahkan pengguna ke situs lain (open redirect).
 */
final class SafeRedirect
{
    public static function to(?string $target, string $fallback): string
    {
        $target = trim((string) $target);

        if ($target === '') {
            return $fallback;
        }

        // Path relatif di aplikasi ini; tolak "//domain-lain" dan "/\domain-lain".
        if (str_starts_with($target, '/') && !str_starts_with($target, '//') && !str_starts_with($target, '/\\')) {
            return $target;
        }

        $base = rtrim(url('/'), '/');
        if ($target === $base || str_starts_with($target, $base . '/') || str_starts_with($target, $base . '?')) {
            return $target;
        }

        return $fallback;
    }
}
