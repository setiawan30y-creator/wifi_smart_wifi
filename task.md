# Tugas Implementasi: Pendaftaran Mandiri SaaS & Free Trial

- `[x]` 1. **Database & Migrations**
  - `[x]` Tambah kolom `trial_ends_at` (datetime) dan `subscription_status` (enum) di tabel `tenants`
- `[x]` 2. **Backend & Controllers**
  - `[x]` Buat `Auth/RegisterController` untuk menangani form pendaftaran
  - `[x]` Buat Middleware `CheckTrialStatus`
  - `[x]` Daftarkan Middleware `CheckTrialStatus` di `bootstrap/app.php` atau `Kernel.php`
- `[x]` 3. **Routing**
  - `[x]` Tambah route `GET /register` dan `POST /register` di `routes/web.php`
  - `[x]` Tambah route `GET /subscription/locked`
- `[x]` 4. **Frontend & Views**
  - `[x]` Buat halaman `resources/views/auth/register.blade.php` (UI Pendaftaran Modern)
  - `[x]` Buat halaman `resources/views/saas/subscription/locked.blade.php` (Halaman Terkunci)
  - `[x]` Tampilkan "Sisa Masa Trial: X Hari" di layout navbar dashboard Klien
