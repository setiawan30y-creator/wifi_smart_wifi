<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController as ApiAuthController;
use App\Http\Controllers\Api\V1\CustomerController as ApiCustomerController;
use App\Http\Controllers\Api\V1\DeviceStockController as ApiDeviceStockController;
use App\Http\Controllers\Api\V1\ExpenseController as ApiExpenseController;
use App\Http\Controllers\Api\V1\FinancialReportController as ApiFinancialReportController;
use App\Http\Controllers\Api\V1\InvoiceController as ApiInvoiceController;
use App\Http\Controllers\Api\V1\OdpController as ApiOdpController;
use App\Http\Controllers\Api\V1\OltController as ApiOltController;
use App\Http\Controllers\Api\V1\PackageController as ApiPackageController;
use App\Http\Controllers\Api\V1\PpobController as ApiPpobController;
use App\Http\Controllers\Api\V1\TicketController as ApiTicketController;
use App\Http\Controllers\Api\V1\WalletController as ApiWalletController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'tenant.user']);

// MikroTik Webhook for Client Status
Route::any('/mikrotik/client-status', [\App\Http\Controllers\MikrotikWebhookController::class, 'clientStatus']);

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', [ApiAuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'tenant.user'])->group(function () {
        Route::get('auth/me', [ApiAuthController::class, 'me']);
        Route::post('auth/logout', [ApiAuthController::class, 'logout']);

        Route::apiResource('customers', ApiCustomerController::class)
            ->middleware('api.permission:customers');

        Route::apiResource('packages', ApiPackageController::class)
            ->middleware('api.permission:packages');

        Route::apiResource('odps', ApiOdpController::class)
            ->middleware('api.permission:odps');

        Route::apiResource('olts', ApiOltController::class)
            ->middleware('api.permission:olts');
        Route::post('olts/{olt}/test', [ApiOltController::class, 'testConnection'])
            ->name('olts.test')
            ->middleware('api.permission:olts');
        Route::get('olts/{olt}/onus', [ApiOltController::class, 'onus'])
            ->name('olts.onus.index')
            ->middleware('api.permission:olts');
        Route::post('olts/{olt}/onus', [ApiOltController::class, 'storeOnu'])
            ->name('olts.onus.store')
            ->middleware('api.permission:olts');
        Route::put('olts/{olt}/onus/{onu}', [ApiOltController::class, 'updateOnu'])
            ->name('olts.onus.update')
            ->middleware('api.permission:olts');
        Route::delete('olts/{olt}/onus/{onu}', [ApiOltController::class, 'destroyOnu'])
            ->name('olts.onus.destroy')
            ->middleware('api.permission:olts');
        Route::post('olts/{olt}/sync-customers', [ApiOltController::class, 'syncCustomers'])
            ->name('olts.sync-customers')
            ->middleware('api.permission:olts');
        Route::post('olts/{olt}/sync-pppoe', [ApiOltController::class, 'syncPppoe'])
            ->name('olts.sync-pppoe')
            ->middleware('api.permission:olts');

        Route::apiResource('tickets', ApiTicketController::class)->only(['index', 'store', 'show'])
            ->middleware('api.permission:tickets');
        Route::post('tickets/{ticket}/reply', [ApiTicketController::class, 'reply'])
            ->middleware('api.permission:tickets');
        Route::patch('tickets/{ticket}/status', [ApiTicketController::class, 'updateStatus'])
            ->middleware('api.permission:tickets');

        Route::apiResource('device-stocks', ApiDeviceStockController::class)
            ->parameters(['device-stocks' => 'deviceStock'])
            ->middleware('api.permission:device_stocks');

        Route::apiResource('expenses', ApiExpenseController::class)
            ->middleware('api.permission:expenses');

        Route::get('financial-reports/summary', [ApiFinancialReportController::class, 'summary'])
            ->middleware('api.permission:financial_reports');

        Route::get('wallet/balance', [ApiWalletController::class, 'balance'])
            ->middleware('api.permission:wallet');
        Route::get('wallet/transactions', [ApiWalletController::class, 'transactions'])
            ->middleware('api.permission:wallet');
        Route::post('wallet/topup', [ApiWalletController::class, 'topup'])
            ->middleware('api.permission:wallet');

        Route::get('ppob/products', [ApiPpobController::class, 'products'])
            ->middleware('api.permission:ppob');
        Route::get('ppob/transactions', [ApiPpobController::class, 'transactions'])
            ->middleware('api.permission:ppob');
        Route::post('ppob/purchase', [ApiPpobController::class, 'purchase'])
            ->middleware('api.permission:ppob');

        Route::get('invoices', [ApiInvoiceController::class, 'index'])
            ->middleware('api.permission:invoices');
        Route::get('invoices/{invoice}', [ApiInvoiceController::class, 'show'])
            ->middleware('api.permission:invoices');
        Route::delete('invoices/{invoice}', [ApiInvoiceController::class, 'destroy'])
            ->middleware('api.permission:invoices');
        Route::post('invoices/generate', [ApiInvoiceController::class, 'generate'])
            ->middleware('api.permission:invoices');
        Route::post('invoices/pay-batch', [ApiInvoiceController::class, 'payBatch'])
            ->middleware('api.permission:invoices');
    });
});
