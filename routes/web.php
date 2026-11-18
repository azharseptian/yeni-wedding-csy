<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AgreementController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Tim\TimController;
use App\Http\Controllers\Vendor\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('katalog');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/beranda', [AuthController::class, 'home'])->name('home');
    Route::get('/pesanan/{booking}/kesepakatan.pdf', AgreementController::class)->name('kesepakatan.pdf');

    // Owner / Admin: akses penuh
    Route::middleware('role:owner')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::get('/pesanan', [Admin\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/pesanan/baru', [Admin\BookingController::class, 'create'])->name('bookings.create');
        Route::post('/pesanan', [Admin\BookingController::class, 'store'])->name('bookings.store');
        Route::get('/pesanan/{booking}', [Admin\BookingController::class, 'show'])->name('bookings.show');
        Route::put('/pesanan/{booking}/rincian', [Admin\BookingController::class, 'revise'])->name('bookings.revise');
        Route::post('/pesanan/{booking}/batal', [Admin\BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::get('/pesanan/{booking}/pengingat-wa', [Admin\BookingController::class, 'reminder'])->name('bookings.reminder');
        Route::post('/pesanan/{booking}/inventaris', [Admin\BookingController::class, 'attachInventory'])->name('bookings.inventory');
        Route::post('/pesanan/{booking}/pembayaran', [Admin\PaymentController::class, 'store'])->name('payments.store');
        Route::post('/pembayaran/{payment}/verifikasi', [Admin\PaymentController::class, 'verify'])->name('payments.verify');
        Route::get('/pembayaran/{payment}/bukti', [Admin\PaymentController::class, 'proof'])->name('payments.proof');

        Route::get('/jadwal', [Admin\ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/jadwal', [Admin\ScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('/jadwal/{schedule}', [Admin\ScheduleController::class, 'destroy'])->name('schedules.destroy');

        Route::get('/harga', [Admin\PricingController::class, 'index'])->name('pricing.index');
        Route::post('/harga/paket', [Admin\PricingController::class, 'storePackage'])->name('pricing.package');
        Route::put('/harga/komponen/{component}', [Admin\PricingController::class, 'updateComponent'])->name('pricing.component');
        Route::post('/harga/komponen', [Admin\PricingController::class, 'storeComponent'])->name('pricing.component.store');
        Route::put('/harga/vendor/{service}', [Admin\PricingController::class, 'updateService'])->name('pricing.service');
        Route::post('/harga/vendor', [Admin\PricingController::class, 'storeService'])->name('pricing.service.store');

        Route::get('/pengaturan', [Admin\SettingController::class, 'index'])->name('settings.index');
        Route::put('/pengaturan/parameter', [Admin\SettingController::class, 'updateParameters'])->name('settings.parameters');
        Route::put('/pengaturan/waktu-tempuh', [Admin\SettingController::class, 'updateTravelTimes'])->name('settings.travel');
        Route::post('/pengaturan/lokasi', [Admin\SettingController::class, 'storeLocation'])->name('settings.location');

        Route::get('/ketersediaan', [Admin\AvailabilityController::class, 'index'])->name('availability.index');
        Route::post('/ketersediaan/{availability}', [Admin\AvailabilityController::class, 'review'])->name('availability.review');

        Route::get('/inventaris', [Admin\InventoryController::class, 'index'])->name('inventories.index');
        Route::post('/inventaris', [Admin\InventoryController::class, 'store'])->name('inventories.store');

        Route::get('/laporan', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/pdf', [Admin\ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/laporan/excel', [Admin\ReportController::class, 'excel'])->name('reports.excel');

        Route::get('/log', Admin\AuditLogController::class)->name('audit.index');
    });

    // Tim MUA / Dekorasi: jadwal pribadi + checklist
    Route::middleware('role:tim_internal')->prefix('tim')->name('tim.')->group(function () {
        Route::get('/', [TimController::class, 'index'])->name('jadwal');
        Route::get('/acara/{booking}', [TimController::class, 'show'])->name('acara');
        Route::patch('/checklist/{item}', [TimController::class, 'check'])->name('checklist');
    });

    // Vendor Keluarga: tampilan paling sederhana
    Route::middleware('role:vendor_keluarga')->prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/', [VendorController::class, 'index'])->name('beranda');
        Route::post('/ketersediaan', [VendorController::class, 'storeAvailability'])->name('ketersediaan');
    });

    // Calon Pengantin: portal mandiri
    Route::middleware('role:klien')->prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'index'])->name('beranda');
        Route::get('/pesan', [PortalController::class, 'create'])->name('pesan');
        Route::post('/pesan', [PortalController::class, 'store'])->name('pesan.store');
        Route::get('/pesanan/{booking}', [PortalController::class, 'show'])->name('pesanan');
        Route::post('/pesanan/{booking}/bayar', [PortalController::class, 'pay'])->name('bayar');
    });
});
