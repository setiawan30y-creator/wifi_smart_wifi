<?php

namespace App\Helpers;

class CustomerStatusHelper
{
    public static function labels(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'isolated' => 'Isolir',
            'stopped' => 'Berhenti',
        ];
    }

    public static function label(?string $status): string
    {
        return self::labels()[$status] ?? ucfirst((string) $status);
    }
}
