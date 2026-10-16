<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Membaca koordinat (latitude, longitude) dari link Google Maps atau teks "lat, lng".
 * Dipakai form Pelanggan, Calon Pasang, ODP, dan tombol "Ekstrak" (dulu ada 6 salinan berbeda).
 */
final class MapsCoordinates
{
    private const PATTERNS = [
        '/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
        '/(?:q|query|center|ll)=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
        '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
        '/(?:place|dir|search)\/.*?\/?(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
        '/[?&](?:destination|saddr|daddr)=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
        '/^(-?\d+(?:\.\d+)?)(?:\s*,\s*|\s+)(-?\d+(?:\.\d+)?)$/',
        '/(-?\d{1,2}\.\d{3,})\s*,\s*(-?\d{2,3}\.\d{3,})/',
    ];

    /** Host yang boleh dibuka untuk membuka link pendek Google Maps. */
    private const ALLOWED_HOSTS = ['maps.app.goo.gl', 'goo.gl', 'maps.google.com', 'www.google.com', 'google.com', 'maps.google.co.id', 'www.google.co.id'];

    /**
     * @return array{lat: float, lng: float}|null
     */
    public static function fromText(?string $text): ?array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        foreach ([urldecode($text), $text] as $candidate) {
            foreach (self::PATTERNS as $pattern) {
                if (preg_match($pattern, $candidate, $m)) {
                    $lat = (float) $m[1];
                    $lng = (float) $m[2];

                    if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && !($lat == 0.0 && $lng == 0.0)) {
                        return ['lat' => $lat, 'lng' => $lng];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Isi latitude/longitude pada data form bila maps_link berisi koordinat.
     */
    public static function apply(array $data): array
    {
        if ($coords = self::fromText($data['maps_link'] ?? null)) {
            $data['latitude'] = $coords['lat'];
            $data['longitude'] = $coords['lng'];
        }

        return $data;
    }

    /**
     * Seperti fromText(), tetapi bila link adalah link pendek Google (maps.app.goo.gl),
     * link dibuka dulu untuk mendapatkan URL lengkapnya. Hanya host Google yang diizinkan.
     *
     * @return array{lat: float, lng: float, resolved_url: string}|null
     */
    public static function resolve(string $link): ?array
    {
        if ($coords = self::fromText($link)) {
            return $coords + ['resolved_url' => $link];
        }

        $url = $link;
        for ($i = 0; $i < 5; $i++) {
            if (!self::isAllowedUrl($url)) {
                return null;
            }

            $response = Http::withoutRedirecting()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                ->timeout(15)
                ->get($url);

            $location = $response->header('Location');

            if ($location) {
                $url = str_starts_with($location, '/') ? self::origin($url) . $location : $location;

                if ($coords = self::fromText($url)) {
                    return $coords + ['resolved_url' => $url];
                }

                continue;
            }

            if ($coords = self::fromText($response->body())) {
                return $coords + ['resolved_url' => $url];
            }

            return null;
        }

        return null;
    }

    private static function isAllowedUrl(string $url): bool
    {
        $parts = parse_url($url);

        return in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && in_array(strtolower($parts['host'] ?? ''), self::ALLOWED_HOSTS, true);
    }

    private static function origin(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
    }
}
