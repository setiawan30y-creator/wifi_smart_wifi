<?php

namespace App\Support;

class Phone
{
    /**
     * Normalisasi nomor HP Indonesia ke format internasional tanpa tanda "+".
     * Contoh: "0812-3456 789" -> "628123456789", "812345" -> "62812345".
     * Mengembalikan null jika tidak ada digit sama sekali.
     */
    public static function normalize(?string $phone): ?string
    {
        $number = preg_replace('/[^0-9]/', '', (string) $phone);

        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '0')) {
            return '62' . substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return '62' . $number;
        }

        return $number;
    }
}
