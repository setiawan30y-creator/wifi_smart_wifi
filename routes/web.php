<?php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OdpController;
use App\Http\Controllers\OltController;
use App\Http\Controllers\NetworkMapController;
use App\Http\Controllers\DeviceStockController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\InstallationRequestController;
use App\Http\Controllers\WaTemplateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\InvoiceAutomationSettingController;

// Auth Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Registration (SaaS Tenant)
    Route::get('register', [\App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [\App\Http\Controllers\Auth\RegisterController::class, 'register'])->middleware('throttle:5,1');

    // Forgot Password via WA
    Route::get('forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:5,1');
    
    Route::get('verify-otp', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showVerifyOtpForm'])->name('password.verify');
    Route::post('verify-otp', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'verifyOtp'])->name('password.verify.post')->middleware('throttle:10,1');
    
    Route::get('reset-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:10,1');
});
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Auth Required)
Route::middleware(['auth'])->group(function () {
    
    // SaaS Admin Routes
    Route::middleware([\App\Http\Middleware\SaasAdminMiddleware::class])->prefix('saas')->name('saas.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Saas\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('tenants', \App\Http\Controllers\Saas\TenantController::class)->except(['create', 'show', 'edit']);
        Route::patch('tenants/{tenant}/toggle-status', [\App\Http\Controllers\Saas\TenantController::class, 'toggleStatus'])->name('tenants.toggle-status');
        
        // SaaS PPOB Master Routes
        Route::prefix('ppob')->name('ppob.')->group(function () {
            Route::resource('suppliers', \App\Http\Controllers\Saas\PpobSupplierController::class)->except(['create', 'show', 'edit']);
            Route::resource('products', \App\Http\Controllers\Saas\PpobProductController::class)->except(['create', 'show', 'edit']);
            Route::post('products/{product}/mapping', [\App\Http\Controllers\Saas\PpobProductController::class, 'mapping'])->name('products.mapping');
            Route::delete('mapping/{mapping}', [\App\Http\Controllers\Saas\PpobProductController::class, 'destroyMapping'])->name('mapping.destroy');
            Route::get('transactions', [\App\Http\Controllers\Saas\PpobTransactionController::class, 'index'])->name('transactions.index');
        });

        // SaaS Billing Routes (Tagihan Aplikasi)
        Route::get('invoices', [\App\Http\Controllers\Saas\SaasInvoiceController::class, 'index'])->name('invoices.index');
        Route::post('invoices/{invoice}/mark-paid', [\App\Http\Controllers\Saas\SaasInvoiceController::class, 'markAsPaid'])->name('invoices.mark_paid');
        Route::post('invoices/{invoice}/send-wa', [\App\Http\Controllers\Saas\SaasInvoiceController::class, 'sendWa'])->name('invoices.send_wa');
        Route::get('invoices/{invoice}/print', [\App\Http\Controllers\Saas\SaasInvoiceController::class, 'print'])->name('invoices.print');
        
        // SaaS Global Settings (Gateways)
        Route::get('settings', [\App\Http\Controllers\Saas\SaasSettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\Saas\SaasSettingController::class, 'store'])->name('settings.store');
    });

    // Subscription Locked Page
    Route::get('subscription/locked', function() {
        return view('saas.subscription.locked');
    })->name('subscription.locked');

    // Ekstrak koordinat dari link Google Maps (dipakai form ODP & Pelanggan).
    // Resource 'odps' cukup didaftarkan sekali di bagian "ODP CRUD Route" (dengan can:odps).
    Route::post('odps/extract-coordinates', [App\Http\Controllers\OdpController::class, 'extractCoordinatesApi'])->name('api.extract-coordinates')->middleware('can:coordinates');

    // Router MikroTik Routes
    Route::resource('routers', App\Http\Controllers\RouterController::class)->middleware('can:routers');
    Route::get('routers/{router}/test', [App\Http\Controllers\RouterController::class, 'testConnection'])->name('routers.test')->middleware('can:routers');

    // OLT Routes
    Route::get('olts/{olt}/test', [OltController::class, 'testConnection'])->name('olts.test')->middleware('can:olts');
    Route::post('olts/{olt}/sync-customers', [OltController::class, 'syncCustomers'])->name('olts.sync-customers')->middleware('can:olts');
    Route::post('olts/{olt}/sync-pppoe', [OltController::class, 'syncPppoe'])->name('olts.sync-pppoe')->middleware('can:olts');
    Route::post('olts/{olt}/onus', [OltController::class, 'storeOnu'])->name('olts.onus.store')->middleware('can:olts');
    Route::put('olts/{olt}/onus/{onu}', [OltController::class, 'updateOnu'])->name('olts.onus.update')->middleware('can:olts');
    Route::delete('olts/{olt}/onus/{onu}', [OltController::class, 'destroyOnu'])->name('olts.onus.destroy')->middleware('can:olts');
    Route::resource('olts', OltController::class)->middleware('can:olts')->except(['create', 'edit']);

    // PPPoE Routes
    Route::post('pppoe/import', [App\Http\Controllers\PppoeController::class, 'import'])->name('pppoe.import')->middleware('can:pppoe');
    Route::post('pppoe/import-file', [App\Http\Controllers\PppoeController::class, 'importFile'])->name('pppoe.importFile')->middleware('can:pppoe');
    Route::delete('pppoe/reset-table', [App\Http\Controllers\PppoeController::class, 'resetTable'])->name('pppoe.reset-table')->middleware('can:pppoe');
    Route::post('pppoe/{id}/toggle', [App\Http\Controllers\PppoeController::class, 'toggle'])->name('pppoe.toggle')->middleware('can:pppoe');
    Route::resource('pppoe', App\Http\Controllers\PppoeController::class)->except(['show'])->middleware('can:pppoe');
    
    // Keuangan & Tagihan didefinisikan di bawah agar route aksi khusus tidak bentrok dengan {invoice}.

    // Tenant PPOB Routes (Markup & Transaksi Penyewa)
    Route::group(['prefix' => 'admin/ppob', 'as' => 'admin.ppob.', 'middleware' => 'can:settings'], function () {
        Route::get('markup', [\App\Http\Controllers\Admin\Ppob\TenantPpobMarkupController::class, 'index'])->name('markup.index');
        Route::post('markup', [\App\Http\Controllers\Admin\Ppob\TenantPpobMarkupController::class, 'store'])->name('markup.store');
        Route::get('transactions', [\App\Http\Controllers\Admin\Ppob\TenantPpobTransactionController::class, 'index'])->name('transactions.index');
    });

    // Tagihan SaaS (Penyewa membayar aplikasi)
    Route::get('admin/saas-invoices', [\App\Http\Controllers\Admin\SaasInvoiceController::class, 'index'])->name('admin.saas_invoices.index')->middleware('can:settings');
    Route::post('admin/saas-invoices/{invoice}/upload-proof', [\App\Http\Controllers\Admin\SaasInvoiceController::class, 'uploadProof'])->name('admin.saas_invoices.upload_proof')->middleware('can:settings');

    // Client/Customer Portal Routes
    Route::group(['prefix' => 'client', 'as' => 'client.'], function () {
        Route::get('dashboard', [\App\Http\Controllers\Client\DashboardController::class, 'index'])->name('dashboard')->middleware('can:dashboard');
        
        // Wallet / Saldo
        Route::post('wallet/topup', [\App\Http\Controllers\Client\WalletController::class, 'topup'])->name('wallet.topup')->middleware('can:wallet');
        Route::post('wallet/pay-wifi', [\App\Http\Controllers\Client\WalletController::class, 'payWifi'])->name('wallet.pay-wifi')->middleware('can:wallet');
        
        // PPOB Catalog
        Route::get('ppob', [\App\Http\Controllers\Client\PpobController::class, 'index'])->name('ppob.index')->middleware('can:ppob');
        Route::post('ppob/buy', [\App\Http\Controllers\Client\PpobController::class, 'buy'])->name('ppob.buy')->middleware('can:ppob');
    });

    // Client App Preview (Mockup - to be replaced by actual dashboard above)

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('can:dashboard');
    
    // Network Map Route
    Route::get('network-map', [NetworkMapController::class, 'index'])->name('network-map')->middleware('can:network_map');
    
    // Packages CRUD (Except create/edit/show since we use modals on the index page)
    Route::resource('packages', PackageController::class)->middleware('can:packages')->except(['create', 'show', 'edit']);
    
    // Customers CRUD
    Route::patch('installation-requests/{installationRequest}/status', [InstallationRequestController::class, 'updateStatus'])->name('installation-requests.status')->middleware('can:customers');
    Route::post('installation-requests/{installationRequest}/convert', [InstallationRequestController::class, 'convert'])->name('installation-requests.convert')->middleware('can:customers');
    Route::resource('installation-requests', InstallationRequestController::class)
        ->parameters(['installation-requests' => 'installationRequest'])
        ->middleware('can:customers')
        ->except(['create', 'show', 'edit']);
    Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export')->middleware('can:customers');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import')->middleware('can:customers');
    Route::delete('customers/bulk-delete', [CustomerController::class, 'bulkDestroy'])->name('customers.bulk-destroy')->middleware('can:customers');
    Route::patch('customers/bulk-billing-mode', [CustomerController::class, 'bulkUpdateBillingMode'])->name('customers.bulk-billing-mode')->middleware('can:customers');
    Route::delete('customers/destroy-all', [CustomerController::class, 'destroyAll'])->name('customers.destroy-all')->middleware('can:customers');
    Route::resource('customers', CustomerController::class)->middleware('can:customers')->except(['create', 'show', 'edit']);
    Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status')->middleware('can:customers');
    Route::patch('customers/{customer}/isolate', [CustomerController::class, 'isolate'])->name('customers.isolate')->middleware('can:customers');
    Route::patch('customers/{customer}/stop-service', [CustomerController::class, 'stopService'])->name('customers.stop-service')->middleware('can:customers');
    Route::patch('customers/{customer}/reactivate', [CustomerController::class, 'reactivate'])->name('customers.reactivate')->middleware('can:customers');
    Route::get('customers/{customer}/print', [CustomerController::class, 'print'])->name('customers.print')->middleware('can:customers');

    
    // Invoices Actions
    Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export')->middleware('can:invoices');
    Route::post('invoices/import', [InvoiceController::class, 'import'])->name('invoices.import')->middleware('can:invoices');
    Route::get('invoices/automation-settings', [InvoiceAutomationSettingController::class, 'index'])->name('invoices.automation-settings')->middleware('can:invoices');
    Route::post('invoices/automation-settings', [InvoiceAutomationSettingController::class, 'update'])->name('invoices.automation-settings.update')->middleware('can:invoices');
    Route::post('invoices/automation-settings/run-now', [InvoiceAutomationSettingController::class, 'runNow'])->name('invoices.automation-settings.run-now')->middleware('can:invoices');
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index')->middleware('can:invoices');
    Route::post('invoices/bulk-wa-qris', [\App\Http\Controllers\TripayController::class, 'requestGroupTransaction'])->name('invoices.bulkWaQris')->middleware('can:invoices');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show')->middleware('can:invoices')->whereNumber('invoice');
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print')->middleware('can:invoices')->whereNumber('invoice');

    // Tripay Payment Gateway
    Route::post('invoices/{invoice}/pay', [\App\Http\Controllers\TripayController::class, 'requestTransaction'])->name('invoices.pay')->middleware('can:invoices')->whereNumber('invoice');

    // Live Traffic API
    Route::get('api/mikrotik/traffic', [\App\Http\Controllers\DashboardController::class, 'traffic'])->name('api.mikrotik.traffic')->middleware('can:dashboard_card_traffic_live');
    
    Route::post('invoices/pay-batch', [InvoiceController::class, 'payBatch'])->name('invoices.pay-batch')->middleware('can:invoices');
    Route::post('invoices/generate-customer', [InvoiceController::class, 'generateForCustomer'])->name('invoices.generate-customer')->middleware('can:invoices');
    Route::delete('invoices/bulk-delete', [InvoiceController::class, 'bulkDestroy'])->name('invoices.bulk-delete')->middleware('can:invoices');
    Route::post('invoices/broadcast-overdue', [InvoiceController::class, 'broadcastOverdue'])->name('invoices.broadcast-overdue')->middleware('can:invoices');
    Route::post('invoices/advance-payment', [InvoiceController::class, 'advancePayment'])->name('invoices.advance-payment')->middleware('can:invoices');
    Route::post('invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate')->middleware('can:invoices');
    Route::get('invoices/{invoice}/payment-proof', [InvoiceController::class, 'paymentProof'])->name('invoices.payment-proof')->middleware('can:invoices')->whereNumber('invoice');
    Route::patch('invoices/{invoice}/payment', [InvoiceController::class, 'updatePayment'])->name('invoices.payment.update')->middleware('can:invoices')->whereNumber('invoice');
    Route::patch('invoices/{invoice}/payment-promise', [InvoiceController::class, 'updatePaymentPromise'])->name('invoices.payment-promise.update')->middleware('can:invoices')->whereNumber('invoice');
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware('can:invoices')->whereNumber('invoice');

    
    // ODP CRUD Route
    Route::post('device-specifications', [OdpController::class, 'storeDeviceSpecification'])->name('device-specifications.store')->middleware('can:odps');
    Route::resource('odps', OdpController::class)->middleware('can:odps');

    // Helpdesk / Ticketing Routes
    Route::get('tickets', [\App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index')->middleware('can:tickets');
    Route::get('tickets/create', [\App\Http\Controllers\TicketController::class, 'create'])->name('tickets.create')->middleware('can:tickets');
    Route::post('tickets', [\App\Http\Controllers\TicketController::class, 'store'])->name('tickets.store')->middleware('can:tickets');
    Route::get('tickets/{ticket}', [\App\Http\Controllers\TicketController::class, 'show'])->name('tickets.show')->middleware('can:tickets');
    Route::post('tickets/{ticket}/reply', [\App\Http\Controllers\TicketController::class, 'reply'])->name('tickets.reply')->middleware('can:tickets');
    Route::post('tickets/{ticket}/take', [\App\Http\Controllers\TicketController::class, 'take'])->name('tickets.take')->middleware('can:tickets');
    Route::post('tickets/{ticket}/complete', [\App\Http\Controllers\TicketController::class, 'complete'])->name('tickets.complete')->middleware('can:tickets');
    Route::patch('tickets/{ticket}/status', [\App\Http\Controllers\TicketController::class, 'updateStatus'])->name('tickets.status')->middleware('can:tickets');
    
    // Device Stock CRUD
    Route::get('device-stocks/{deviceStock}/image', [DeviceStockController::class, 'image'])->name('device-stocks.image')->middleware('can:device_stocks')->whereNumber('deviceStock');
    Route::resource('device-stocks', DeviceStockController::class)->middleware('can:device_stocks')->except(['create', 'show', 'edit']);
    
    // API untuk Route Waypoints (Edit Peta Jaringan)
    Route::post('/api/route-waypoints/odp/{odp}', [\App\Http\Controllers\Api\RouteWaypointController::class, 'updateOdp'])->name('api.route-waypoints.odp')->middleware('can:network_map');
    Route::post('/api/route-waypoints/customer/{customer}', [\App\Http\Controllers\Api\RouteWaypointController::class, 'updateCustomer'])->name('api.route-waypoints.customer')->middleware('can:network_map');
    
    // Expenses CRUD
    Route::get('expenses/{expense}/receipt-photo', [ExpenseController::class, 'receiptPhoto'])->name('expenses.receipt-photo')->middleware('can:expenses')->whereNumber('expense');
    Route::resource('expenses', ExpenseController::class)->middleware('can:expenses')->except(['create', 'show', 'edit']);
    
    // Financial Reports Route
    Route::get('financial-reports', [FinancialReportController::class, 'index'])->name('financial-reports.index')->middleware('can:financial_reports');
    
    // WA Message Templates CRUD
    Route::resource('wa-templates', WaTemplateController::class)->middleware('can:wa_templates')->except(['create', 'show', 'edit']);

    // Settings CRUD Page & Tabs
    Route::get('settings/company-logo', [SettingsController::class, 'companyLogo'])->name('settings.company-logo')->middleware('can:settings');
    Route::get('settings/company-favicon', [SettingsController::class, 'companyFavicon'])->name('settings.company-favicon')->middleware('can:settings');
    Route::get('settings/users/{user}/photo', [SettingsController::class, 'userPhoto'])->name('settings.users.photo')->middleware('can:settings');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('can:settings');
    Route::post('settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company')->middleware('can:settings');
    Route::post('settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store')->middleware('can:settings');
    Route::put('settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update')->middleware('can:settings');
    Route::put('settings/accounts/{user}', [SettingsController::class, 'updateAccount'])->name('settings.accounts.update')->middleware('can:settings');
    Route::delete('settings/users/{user}', [SettingsController::class, 'destroyUser'])->name('settings.users.destroy')->middleware('can:settings');
    Route::post('settings/permissions', [SettingsController::class, 'updatePermissions'])->name('settings.permissions')->middleware('can:settings');
    Route::post('settings/mikrotik-webhook/regenerate', [SettingsController::class, 'regenerateMikrotikWebhookToken'])->name('settings.mikrotik-webhook.regenerate')->middleware('can:settings');
    Route::post('settings/dashboard-interfaces', [SettingsController::class, 'updateDashboardInterfaces'])->name('settings.dashboard_interfaces')->middleware('can:settings');

    // Fonnte WA Gateway
    Route::post('wa/send', [\App\Http\Controllers\WaGatewayController::class, 'send'])->name('wa.send')->middleware('can:whatsapp');
    Route::post('wa/broadcast', [\App\Http\Controllers\WaGatewayController::class, 'broadcast'])->name('wa.broadcast')->middleware('can:whatsapp');
});

// Tripay Callback/Webhook (Public Route)
Route::post('payment/tripay/callback', [\App\Http\Controllers\TripayController::class, 'callback'])->name('tripay.callback');
