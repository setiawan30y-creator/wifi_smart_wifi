{{-- Tampilan tenant (Pengaturan → Tampilan & Warna). Harus setelah app.css agar menimpa token bawaan. --}}
@php($themeFontUrl = \App\Support\Theme::fontUrl())
@if($themeFontUrl)
    <link id="tenant-theme-font" rel="stylesheet" href="{{ $themeFontUrl }}">
@endif
<style id="tenant-theme">{!! \App\Support\Theme::css() !!}</style>
