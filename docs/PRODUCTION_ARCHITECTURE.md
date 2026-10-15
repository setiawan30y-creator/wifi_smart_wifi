# Production Architecture

Target aplikasi ini adalah backend REST API untuk Flutter Android, iOS, dan Web, dengan admin panel web sebagai modul terpisah selama masa transisi.

## Versi Target

- OS: Ubuntu Server 24.04 LTS
- Web server: Nginx
- PHP: 8.4+
- Framework: Laravel 13.8
- Database: MySQL 8
- Cache/session/queue: Redis
- Queue worker: Supervisor
- Scheduler: systemd timer atau cron
- SSL: Let's Encrypt
- Deployment: Git pull + Composer + migration + cache warmup

Catatan: project memakai `laravel/framework ^13.8`. Tidak dilakukan downgrade; deployment production diarahkan ke Laravel 13.8 dengan PHP 8.4+.

## Struktur Folder Target

```text
app/
  Contracts/
    Repositories/
  Console/
    Commands/
  DTO/
  Exceptions/
  Helpers/
  Http/
    Controllers/
      Api/
        V1/
      Web/
    Middleware/
    Requests/
      Api/
        V1/
    Resources/
      V1/
  Jobs/
  Listeners/
  Mail/
  Models/
  Notifications/
  Policies/
  Providers/
  Repositories/
    Eloquent/
  Services/
  Support/
bootstrap/
config/
database/
  factories/
  migrations/
  seeders/
docs/
  api/
routes/
  api.php
  web.php
scripts/
storage/
tests/
```

## API Versioning

Semua endpoint mobile/web client baru harus berada di:

```text
/api/v1
```

Contoh:

```text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
GET  /api/v1/customers
```

Versi berikutnya dibuat paralel:

```text
/api/v2
```

## Authentication

Gunakan Laravel Sanctum token bearer untuk Flutter:

```http
Authorization: Bearer <token>
Accept: application/json
```

JWT bisa ditambahkan jika nanti dibutuhkan integrasi pihak ketiga, tetapi Sanctum lebih sederhana untuk first-party mobile app.

## Role & Permission

Saat ini permission memakai `User::hasPermission($module)` berbasis role dan setting tenant. API memakai middleware:

```text
api.permission:customers
```

Target jangka panjang:

- pisahkan `roles`, `permissions`, `role_permissions`
- tambahkan policy per model
- audit semua akses tenant dengan global scope dan policy

## Repository Pattern

Controller API tidak query Eloquent langsung untuk operasi list/detail utama. Gunakan contract:

```text
App\Contracts\Repositories\CustomerRepositoryInterface
```

Implementasi:

```text
App\Repositories\Eloquent\CustomerRepository
```

Binding ada di:

```text
App\Providers\RepositoryServiceProvider
```

## Service Layer

Business logic tetap berada di `app/Services`, misalnya:

- `CustomerService`
- `InvoiceService`
- `MikrotikService`
- `WalletService`

Controller hanya mengurus request/response.

## Response JSON Standard

Format sukses:

```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

Format error:

```json
{
  "success": false,
  "message": "Validation error",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {}
  }
}
```

Helper response:

```text
App\Support\ApiResponse
```

## Upload File & Storage

Gunakan `FileUploadService` dan disk public/S3-compatible.

Production local storage:

```bash
php artisan storage:link
```

Rekomendasi production besar:

- S3/MinIO untuk foto pelanggan dan bukti pembayaran
- validasi MIME, ukuran file, dan nama file acak
- jangan simpan file user di `public/` langsung

## Queue, Notification, Scheduler

Queue:

```env
QUEUE_CONNECTION=redis
```

Supervisor menjalankan:

```bash
php artisan queue:work redis --sleep=3 --tries=3 --timeout=120
```

Scheduler:

```bash
php artisan schedule:run
```

Notification target:

- WhatsApp gateway
- email
- push notification Flutter/Firebase

## Logging

Production:

```env
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

Gunakan context pada exception dan transaksi penting:

- payment callback
- status pelanggan
- invoice generate
- upload file
- queue failure

## Error Handling

Semua request `api/*` harus menerima JSON. Middleware `ForceJsonResponse` memastikan header `Accept: application/json`.

Tahap berikutnya: standarkan exception rendering di `bootstrap/app.php` untuk validation, auth, model not found, dan server error.

## Localization

Gunakan:

```text
lang/id
lang/en
```

Header client:

```http
Accept-Language: id
```

Target:

- pesan validasi API
- status invoice
- status pelanggan
- notifikasi WA/email

## Security Best Practice

- `APP_DEBUG=false`
- `.env` tidak masuk Git
- pakai HTTPS saja
- batasi permission folder `storage` dan `bootstrap/cache`
- rate limit login/API sensitif
- validasi semua upload file
- rotasi token API jika device hilang
- backup database terenkripsi
- jangan expose stack trace di production
- gunakan CORS domain Flutter Web yang jelas
- audit tenant isolation sebelum live

## Deployment Flow

```text
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart wifi-smartbilling-worker:*
sudo systemctl reload nginx
```

## Backup Database

Minimal:

- `mysqldump` harian
- kompres gzip
- simpan 7-30 hari
- upload ke storage eksternal
- uji restore berkala

Script contoh ada di:

```text
scripts/backup-db.sh
```

## API Documentation

OpenAPI awal ada di:

```text
docs/api/openapi.yaml
```

Tahap berikutnya bisa pakai Swagger UI atau generate dari annotation.
