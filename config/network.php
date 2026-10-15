<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Titik Koordinat Peta Jaringan
    |--------------------------------------------------------------------------
    |
    | Menentukan lokasi geografis default untuk ISP Gateway (sumber bandwidth)
    | dan Server NOC (pusat router / server core) untuk dipetakan secara otomatis.
    |
    */

    'isp' => [
        'name' => env('NETWORK_ISP_NAME', 'ISP Gateway (Trunk)'),
        'latitude' => env('NETWORK_ISP_LATITUDE', -6.2065),
        'longitude' => env('NETWORK_ISP_LONGITUDE', 106.8430),
        'description' => env('NETWORK_ISP_DESCRIPTION', 'Sumber Bandwidth Utama / Fiber Optic Backhaul (1 Gbps)'),
    ],

    'server' => [
        'name' => env('NETWORK_SERVER_NAME', 'Server Core NOC'),
        'latitude' => env('NETWORK_SERVER_LATITUDE', -6.2070),
        'longitude' => env('NETWORK_SERVER_LONGITUDE', 106.8445),
        'description' => env('NETWORK_SERVER_DESCRIPTION', 'Pusat Distribusi Jaringan (Mikrotik CCR, OLT, & Billing Server)'),
    ],
];
