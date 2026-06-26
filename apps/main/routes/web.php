<?php

use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Auth\AdminAuthController;
use App\Http\Controllers\Web\ActivityController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\Web\OfferController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\LegalController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\ActivityAdminController;
use App\Http\Controllers\Web\Admin\BookingAdminController;
use App\Http\Controllers\Web\Admin\InvoiceAdminController;
use App\Http\Controllers\Web\Admin\CustomerAdminController;
use App\Http\Controllers\Web\Admin\UserAdminController;
use App\Http\Controllers\Web\Admin\OfferAdminController;
use App\Http\Controllers\Web\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

// ─── Storefront Auth ───────────────────────────────────────────────────────────
Route::middleware('guest:web')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [AuthController::class, 'login']);
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth:web');

// ─── Storefront Public ─────────────────────────────────────────────────────────
Route::get('/', [ActivityController::class, 'home'])->name('home');

Route::prefix('activities')->name('activities.')->group(function () {
    Route::get('/',       [ActivityController::class, 'index'])->name('index');
    Route::get('/{slug}', [ActivityController::class, 'show'])->name('show');
});

Route::prefix('offers')->name('offers.')->group(function () {
    Route::get('/',       [OfferController::class, 'index'])->name('index');
    Route::get('/{slug}', [OfferController::class, 'show'])->name('show');
});

Route::get('/legal/{page}', [LegalController::class, 'show'])->name('legal.show');

// ─── Cart (no auth required — login only enforced at checkout) ────────────────
Route::get('/cart',             [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add',        [CartController::class, 'add'])->name('cart.add');
Route::delete('/cart/{index}',  [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart',          [CartController::class, 'clear'])->name('cart.clear');

// ─── Checkout & Invoice ────────────────────────────────────────────────────────
Route::get('/checkout',  [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

Route::middleware('auth:web')->group(function () {
    Route::get('/invoice/{code}',        [InvoiceController::class, 'show'])->name('invoice.show');
    Route::post('/invoice/{code}/retry', [InvoiceController::class, 'retry'])->name('invoice.retry');
});

Route::get('/payment/finish', [InvoiceController::class, 'finish'])->name('payment.finish');

// ─── Customer Account ──────────────────────────────────────────────────────────
Route::prefix('account')->name('account.')->middleware('auth:web')->group(function () {
    Route::get('/bookings',        [AccountController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{code}', [AccountController::class, 'bookingDetail'])->name('booking.detail');
});

// ─── Admin Auth ────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware('guest:admin_session')->group(function () {
        Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // ─── Admin Portal (protected) ──────────────────────────────────────────────
    Route::middleware(['auth:admin_session', 'role:super_admin,admin'])->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Activities
        Route::resource('activities', ActivityAdminController::class)->names('activities');
        Route::get('activities/{id}/slots',           [ActivityAdminController::class, 'slots'])->name('activities.slots');
        Route::post('activities/{id}/generate-slots', [ActivityAdminController::class, 'generateSlots'])->name('activities.generate-slots');
        Route::put('slots/{slotId}',                  [ActivityAdminController::class, 'updateSlot'])->name('slots.update');

        // Offers
        Route::resource('offers', OfferAdminController::class)->names('offers');
        Route::get('offers/{offer}/promo-codes',      [OfferAdminController::class, 'promoCodes'])->name('offers.promo-codes');
        Route::post('offers/{offer}/promo-codes',     [OfferAdminController::class, 'storePromoCode'])->name('offers.promo-codes.store');
        Route::patch('promo-codes/{promo}/toggle',    [OfferAdminController::class, 'togglePromoCode'])->name('promo-codes.toggle');

        // Bookings
        Route::get('bookings',        [BookingAdminController::class, 'index'])->name('bookings.index');
        Route::get('bookings/export', [BookingAdminController::class, 'export'])->name('bookings.export');
        Route::get('bookings/{id}',   [BookingAdminController::class, 'show'])->name('bookings.show');
        Route::put('bookings/{id}',   [BookingAdminController::class, 'update'])->name('bookings.update');
        Route::post('bookings',       [BookingAdminController::class, 'storeManual'])->name('bookings.store-manual');

        // Invoices
        Route::get('invoices',        [InvoiceAdminController::class, 'index'])->name('invoices.index');
        Route::get('invoices/export', [InvoiceAdminController::class, 'export'])->name('invoices.export');
        Route::get('invoices/{code}', [InvoiceAdminController::class, 'show'])->name('invoices.show');

        // Customers
        Route::get('customers',                       [CustomerAdminController::class, 'index'])->name('customers.index');
        Route::get('customers/export',                [CustomerAdminController::class, 'export'])->name('customers.export');
        Route::get('customers/{id}',                  [CustomerAdminController::class, 'show'])->name('customers.show');
        Route::post('customers',                      [CustomerAdminController::class, 'store'])->name('customers.store');
        Route::put('customers/{id}',                  [CustomerAdminController::class, 'update'])->name('customers.update');
        Route::delete('customers/{id}',               [CustomerAdminController::class, 'destroy'])->name('customers.destroy');
        Route::post('customers/{id}/restore',         [CustomerAdminController::class, 'restore'])->name('customers.restore');
        Route::patch('customers/{id}/toggle-active',  [CustomerAdminController::class, 'toggleActive'])->name('customers.toggle-active');

        // Users
        Route::resource('users', UserAdminController::class)->names('users');

        // Settings
        Route::get('settings',                                   [SettingsController::class, 'general'])->name('settings.general');
        Route::post('settings/general',                          [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
        Route::get('settings/storefront',                        [SettingsController::class, 'storefront'])->name('settings.storefront');
        Route::post('settings/storefront',                       [SettingsController::class, 'updateStorefront'])->name('settings.storefront.update');
        Route::get('settings/social',                            [SettingsController::class, 'social'])->name('settings.social');
        Route::post('settings/social',                           [SettingsController::class, 'updateSocial'])->name('settings.social.update');
        Route::get('settings/payment-gateways',                  [SettingsController::class, 'paymentGateways'])->name('settings.payment-gateways');
        Route::put('settings/payment-gateways/{name}',           [SettingsController::class, 'updateGateway'])->name('settings.payment-gateways.update');
        Route::post('settings/payment-gateways/{name}/activate', [SettingsController::class, 'activateGateway'])->name('settings.payment-gateways.activate');
        Route::get('settings/legal',                             [SettingsController::class, 'legal'])->name('settings.legal');
        Route::post('settings/legal',                            [SettingsController::class, 'updateLegal'])->name('settings.legal.update');
    });
});
