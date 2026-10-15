# rapikan-backup.ps1
# Memindahkan file backup manual (Nama2.php, Nama(2).php, dll.) dan file test/sementara
# KELUAR dari folder project. File TIDAK dihapus: semuanya dipindah ke C:\laragon\backup-kode\...
# Database tidak disentuh sama sekali.
#
# Cara pakai (PowerShell, dari folder project):
#   powershell -ExecutionPolicy Bypass -File scripts\rapikan-backup.ps1 -DryRun   # lihat dulu, tidak memindah apa pun
#   powershell -ExecutionPolicy Bypass -File scripts\rapikan-backup.ps1           # jalankan
param([switch]$DryRun)

$ErrorActionPreference = 'Stop'
$root   = Split-Path -Parent $PSScriptRoot
$stamp  = Get-Date -Format 'yyyyMMdd_HHmm'
$target = "C:\laragon\backup-kode\wifi_smart_wifi\$stamp"

$items = @(
    'app\Models\Customer2.php'
    'app\Models\Expense2.php'
    'app\Models\Invoice2.php'
    'app\Models\Olt(2).php'
    'app\Models\Router(2).php'
    'app\Models\Ticket(2).php'
    'app\Models\User(2).php'
    'app\Models\User(3).php'
    'app\Providers\AppServiceProvider2.php'
    'app\Services\CustomerService(2).php'
    'app\Services\CustomerService(3).php'
    'app\Services\InvoiceService2.php'
    'app\Services\InvoiceService3.php'
    'app\Console\Commands\RunBillingAutomation2.php'
    'app\Console\Commands\RunBillingAutomation3.php'
    'app\Http\Controllers\AuthController(2).php'
    'app\Http\Controllers\CustomerController2.php'
    'app\Http\Controllers\DeviceStockController2.php'
    'app\Http\Controllers\ExpenseController2.php'
    'app\Http\Controllers\InvoiceAutomationSettingController2.php'
    'app\Http\Controllers\InvoiceAutomationSettingController3.php'
    'app\Http\Controllers\InvoiceController2.php'
    'app\Http\Controllers\InvoiceController3.php'
    'app\Http\Controllers\InvoiceController4.php'
    'app\Http\Controllers\NetworkMapController(2).php'
    'app\Http\Controllers\OdpController(2).php'
    'app\Http\Controllers\OdpController(3).php'
    'app\Http\Controllers\OdpController(4).php'
    'app\Http\Controllers\OdpController(5).php'
    'app\Http\Controllers\OltController(2).php'
    'app\Http\Controllers\PppoeController(2).php'
    'app\Http\Controllers\PppoeController(3).php'
    'app\Http\Controllers\RouterController(2).php'
    'app\Http\Controllers\SettingsController.2php'
    'app\Http\Controllers\SettingsController2.php'
    'app\Http\Controllers\TicketController(2).php'
    'app\Http\Controllers\WaGatewayController2.php'
    'app\Http\Middleware\SaasAdminMiddleware(2).php'
    'app\Http\Requests\Customer\StoreCustomerRequest2.php'
    'app\Http\Requests\Customer\UpdateCustomerRequest(2).php'
    'app\Http\Requests\Customer\UpdateCustomerRequest3.php'
    'app\Http\Requests\Invoice\GenerateInvoiceRequest(2).php'
    'app\Http\Requests\Invoice\GenerateInvoiceRequest3.php'
    'app\Http\Requests\Invoice\PayBatchInvoiceRequest2.php'
    'app\Http\Resources\V1\OltResource(2).php'
    'app\Http\Controllers\Api\V1\OltController(2).php'
    'routes\web2.php'
    'routes\console2a.php'
    'database\seeders\DatabaseSeeder(2).php'
    'database\seeders\DatabaseSeeder(3).php'
    'database\seeders\RbacSeeder(2).php'
    'database\seeders\WaTemplateSeeder2.php'
    'scripts\deploy-ubuntu2.sh'
    'public\sw2.js'
    'public\manifest.webmanifest2'
    'resources\css\app2.css'
    'resources\css\app3.css'
    'resources\css\app4.css'
    'resources\views\dashboard.blade(2).php'
    'resources\views\dashboard.blade3.php'
    'resources\views\customers\index.blade2.php'
    'resources\views\device_stocks\index.blade2.php'
    'resources\views\expenses\index.blade2.php'
    'resources\views\invoices\automation_settings.blade2.php'
    'resources\views\invoices\automation_settings.blade3.php'
    'resources\views\invoices\index.blade2.php'
    'resources\views\invoices\index.blade3.php'
    'resources\views\invoices\index.blade4.php'
    'resources\views\layouts\app.blade2.php'
    'resources\views\layouts\app.blade3.php'
    'resources\views\layouts\app.blade4.php'
    'resources\views\network_map\index.blade(2).php'
    'resources\views\odps\create.blade(2).php'
    'resources\views\odps\index.blade2.php'
    'resources\views\olts\index.blade(2).php'
    'resources\views\pppoe\_row.blade(2).php'
    'resources\views\pppoe\create.blade(2).php'
    'resources\views\pppoe\index.blade(2).php'
    'resources\views\settings\index.blade2.php'
    'resources\views\tickets\client_index.blade(2).php'
    'resources\views\tickets\client_show.blade(2).php'
    'resources\views\tickets\create.blade(2).php'
    'resources\views\tickets\create.blade(3).php'
    'resources\views\tickets\index.blade(2).php'
    '_wifi_smartbilling.zip'
    '.cache.swp'
    '.phpunit.result.cache'
    'apply_traits.php'
    'curl_output.txt'
    'maps.html'
    'temp.html'
    'test.js'
    'test_cust.js'
    'test_cust2.js'
    'test2.php'
    'test_curl.php'
    'test_google.php'
    'test_maps.php'
    'test_maps2.php'
    'test_maps3.php'
    'test_maps4.php'
    'test_maps5.php'
    'test_maps6.php'
    'test_maps7.php'
    'test_maps8.php'
    'test_maps9.php'
    'test_maps10.php'
    'test_mikrotik.php'
    'test_regex.php'
    'test_regex2.php'
    'test_unshorten.php'
    'scratch'
)

$moved = 0; $skip = 0
foreach ($rel in $items) {
    $src = Join-Path $root $rel
    if (-not (Test-Path -LiteralPath $src)) { $skip++; continue }
    $dst = Join-Path $target $rel
    if ($DryRun) { Write-Host "[DRY] $rel"; $moved++; continue }
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $dst) | Out-Null
    Move-Item -LiteralPath $src -Destination $dst
    Write-Host "Dipindah: $rel"
    $moved++
}

Write-Host ""
if ($DryRun) {
    Write-Host "DRY RUN: $moved item akan dipindah ke $target ($skip tidak ditemukan / sudah dipindah)." -ForegroundColor Yellow
} else {
    Write-Host "Selesai: $moved item dipindah ke $target ($skip tidak ditemukan / sudah dipindah)." -ForegroundColor Green
}
