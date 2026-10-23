{{-- Warna tema tenant (Pengaturan → Tampilan). Harus setelah app.css agar menimpa warna bawaan. --}}
@php($themeCss = \App\Support\Theme::css())
@if($themeCss !== '')
    <style id="tenant-theme">{!! $themeCss !!}</style>
@endif
