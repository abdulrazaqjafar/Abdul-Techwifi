<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\ClickPesaWebhookController;
use App\Http\Controllers\CustomerOfferController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HotspotController::class, 'index'])->name('hotspot.index');
Route::post('/hotspot/login', [HotspotController::class, 'login'])->name('hotspot.login');
Route::post('/hotspot/purchase', [HotspotController::class, 'purchase'])->name('hotspot.purchase');
Route::get('/hotspot/payment/{reference}/status', [HotspotController::class, 'paymentStatus'])->name('hotspot.payment.status');
Route::post('/hotspot/connect', [HotspotController::class, 'connect'])->name('hotspot.connect');
Route::post('/hotspot/auto-connect', [HotspotController::class, 'autoConnect'])->name('hotspot.auto-connect');
Route::post('/hotspot/locale', [HotspotController::class, 'setLocale'])->name('hotspot.locale');
Route::post('/webhooks/clickpesa', [ClickPesaWebhookController::class, 'handle'])->name('webhooks.clickpesa');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::get('forgot-password', [LoginController::class, 'createForgotPassword'])->name('password.request');
    Route::post('forgot-password', [LoginController::class, 'storeForgotPassword'])->name('password.email');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('admin:cashier,admin,super_admin')->prefix('cashier')->name('cashier.')->group(function (): void {
        Route::get('/', [CashierController::class, 'index'])->name('index');
        Route::get('/sell', [CashierController::class, 'create'])->name('sell');
        Route::post('/sell', [CashierController::class, 'store'])->name('sell.store');
        Route::get('/sales', [CashierController::class, 'sales'])->name('sales');
        Route::get('/sales/{sale}', [CashierController::class, 'showSale'])->name('sales.show');
        Route::get('/sales/{sale}/print', [CashierController::class, 'printReceipt'])->name('sales.print');
    });

    Route::middleware('admin:admin,super_admin')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/live', [DashboardController::class, 'live'])->name('dashboard.live');

        Route::resource('packages', PackageController::class);

        Route::prefix('vouchers')->name('vouchers.')->group(function (): void {
            Route::get('/', [VoucherController::class, 'index'])->name('index');
            Route::view('/generate', 'vouchers.generate')->name('generate.form');
            Route::post('/generate', [VoucherController::class, 'generate'])->name('generate');
            Route::post('/generate-bulk', [VoucherController::class, 'generateBulk'])->name('generate-bulk');
            Route::get('/print', [VoucherController::class, 'print'])->name('print');
            Route::get('/export/pdf', [VoucherController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/excel', [VoucherController::class, 'exportExcel'])->name('export.excel');
            Route::patch('/{voucher}/status', [VoucherController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{voucher}', [VoucherController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('sales')->name('sales.')->group(function (): void {
            Route::get('/', [SaleController::class, 'index'])->name('index');
            Route::view('/create', 'sales.create')->name('create');
            Route::post('/', [SaleController::class, 'store'])->name('store');
            Route::get('/export', [SaleController::class, 'export'])->name('export');
            Route::get('/{sale}', [SaleController::class, 'show'])->name('show');
        });

        Route::resource('customers', CustomerController::class);
        Route::post('customers/{customer}/refresh-service', [CustomerController::class, 'refreshServiceStatus'])->name('customers.refresh-service');
        Route::get('customers-export', [CustomerController::class, 'export'])->name('customers.export');

        Route::get('offers', [CustomerOfferController::class, 'index'])->name('offers.index');
        Route::post('offers/dispatch', [CustomerOfferController::class, 'dispatchNow'])->name('offers.dispatch');

        Route::resource('staff', StaffController::class);

        Route::prefix('router')->name('router.')->group(function (): void {
            Route::get('/', [RouterController::class, 'index'])->name('index');
            Route::post('/connect', [RouterController::class, 'connect'])->name('connect');
            Route::post('/disconnect', [RouterController::class, 'disconnect'])->name('disconnect');
            Route::post('/test', [RouterController::class, 'test'])->name('test');
            Route::get('/users/online', [RouterController::class, 'onlineUsers'])->name('users.online');
            Route::get('/users/offline', [RouterController::class, 'offlineUsers'])->name('users.offline');
            Route::post('/users/disconnect', [RouterController::class, 'disconnectUser'])->name('users.disconnect');
            Route::post('/sync', [RouterController::class, 'sync'])->name('sync');
        });

        Route::prefix('reports')->name('reports.')->group(function (): void {
            Route::view('/', 'reports.index')->name('index');
            Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
            Route::get('/weekly', [ReportController::class, 'weekly'])->name('weekly');
            Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
            Route::get('/vouchers', [ReportController::class, 'vouchers'])->name('vouchers');
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
            Route::get('/{type}/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/{type}/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
        });

        Route::prefix('settings')->name('settings.')->group(function (): void {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/business', [SettingsController::class, 'updateBusiness'])->name('business.update');
            Route::put('/offers', [SettingsController::class, 'updateOffers'])->name('offers.update');
            Route::put('/router', [SettingsController::class, 'updateRouter'])->name('router.update');
        });
    });
});
