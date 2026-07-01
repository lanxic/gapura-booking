<?php

use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Auth\AdminAuthController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\Web\OfferController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\LegalController;
use App\Http\Controllers\Web\Tenant\StorefrontController;
use App\Http\Controllers\Web\Tenant\ProductController as TenantProductController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\ProductAdminController;
use App\Http\Controllers\Web\Admin\BookingAdminController;
use App\Http\Controllers\Web\Admin\InvoiceAdminController;
use App\Http\Controllers\Web\Admin\CustomerAdminController;
use App\Http\Controllers\Web\Admin\UserAdminController;
use App\Http\Controllers\Web\Admin\OfferAdminController;
use App\Http\Controllers\Web\Admin\SettingsController;
use App\Http\Controllers\Web\Admin\AdminProfileController;
use App\Http\Controllers\Web\Admin\TenantController;
use App\Http\Controllers\Web\Tenant\Admin\DashboardController as TenantDashboardController;
use App\Http\Controllers\Web\Tenant\Admin\ProductAdminController as TenantProductAdminController;
use App\Http\Controllers\Web\Tenant\Admin\BookingAdminController as TenantBookingAdminController;
use App\Http\Controllers\Web\Tenant\Admin\InvoiceAdminController as TenantInvoiceAdminController;
use App\Http\Controllers\Web\Tenant\Admin\ProfileController as TenantProfileController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════════════════════
// TENANT ROUTES (subdomain: {tenant}.localhost)
// ═══════════════════════════════════════════════════════════════════════════════
$appHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

Route::domain('{tenantSlug}.' . $appHost)->middleware('tenant.identify')->group(function () {

    // ─── Tenant Storefront ─────────────────────────────────────────────────────
    Route::get('/', [StorefrontController::class, 'home'])->name('tenant.home');

    Route::prefix('products')->name('tenant.products.')->group(function () {
        Route::get('/',       [TenantProductController::class, 'index'])->name('index');
        Route::get('/{slug}', [TenantProductController::class, 'show'])->name('show');
    });

    Route::prefix('offers')->name('tenant.offers.')->group(function () {
        Route::get('/',       [OfferController::class, 'index'])->name('index');
        Route::get('/{slug}', [OfferController::class, 'show'])->name('show');
    });

    Route::get('/legal/{page}', [LegalController::class, 'show'])->name('tenant.legal.show');

    // ─── Storefront Auth (tenant-scoped) ──────────────────────────────────────
    Route::middleware('guest:web')->group(function () {
        Route::get('/login',    [AuthController::class, 'showLogin'])->name('tenant.login');
        Route::post('/login',   [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('tenant.register');
        Route::post('/register',[AuthController::class, 'register']);
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('tenant.logout')->middleware('auth:web');

    // ─── Cart & Checkout (tenant-scoped) ──────────────────────────────────────
    Route::get('/cart',            [CartController::class, 'index'])->name('tenant.cart.index');
    Route::post('/cart/add',       [CartController::class, 'add'])->name('tenant.cart.add');
    Route::delete('/cart/{index}', [CartController::class, 'remove'])->name('tenant.cart.remove');
    Route::delete('/cart',         [CartController::class, 'clear'])->name('tenant.cart.clear');

    Route::get('/checkout',  [CheckoutController::class, 'index'])->name('tenant.checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('tenant.checkout.store');

    Route::get('/invoice/{code}', [InvoiceController::class, 'show'])->name('tenant.invoice.show');

    Route::middleware('auth:web')->group(function () {
        Route::post('/invoice/{code}/retry', [InvoiceController::class, 'retry'])->name('tenant.invoice.retry');
    });

    Route::get('/payment/finish', [InvoiceController::class, 'finish'])->name('tenant.payment.finish');

    Route::prefix('account')->name('tenant.account.')->middleware('auth:web')->group(function () {
        Route::get('/bookings',        [AccountController::class, 'bookings'])->name('bookings');
        Route::get('/bookings/{code}', [AccountController::class, 'bookingDetail'])->name('booking.detail');
    });

    // ─── Tenant Admin Panel ────────────────────────────────────────────────────
    Route::prefix('admin')->name('tenant.admin.')->group(function () {

        Route::middleware('guest:admin_session')->group(function () {
            Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [AdminAuthController::class, 'login']);
        });

        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::middleware(['auth:admin_session', 'role:admin,tenant_admin,scanner'])->group(function () {

            Route::get('/', [TenantDashboardController::class, 'index'])->name('dashboard');

            // Products
            Route::resource('products', TenantProductAdminController::class)->names('products');
            Route::delete('products',            [TenantProductAdminController::class, 'bulkDestroy'])->name('products.bulk-destroy');
            Route::get('products/{id}/slots',    [TenantProductAdminController::class, 'slots'])->name('products.slots');
            Route::post('products/{id}/generate-slots', [TenantProductAdminController::class, 'generateSlots'])->name('products.generate-slots');
            Route::post('products/{id}/store-slot',  [TenantProductAdminController::class, 'storeSlot'])->name('products.store-slot');
            Route::post('slots/bulk-update',          [TenantProductAdminController::class, 'bulkUpdateSlots'])->name('slots.bulk-update');
            Route::put('slots/{slotId}',              [TenantProductAdminController::class, 'updateSlot'])->name('slots.update');

            // Bookings
            Route::get('bookings',        [TenantBookingAdminController::class, 'index'])->name('bookings.index');
            Route::get('bookings/{id}',   [TenantBookingAdminController::class, 'show'])->name('bookings.show');
            Route::put('bookings/{id}',   [TenantBookingAdminController::class, 'update'])->name('bookings.update');

            // Invoices
            Route::get('invoices',               [TenantInvoiceAdminController::class, 'index'])->name('invoices.index');
            Route::get('invoices/export',        [TenantInvoiceAdminController::class, 'export'])->name('invoices.export');
            Route::get('invoices/{code}',        [TenantInvoiceAdminController::class, 'show'])->name('invoices.show');

            // Profile
            Route::get('profile',  [TenantProfileController::class, 'show'])->name('profile.show');
            Route::put('profile',  [TenantProfileController::class, 'update'])->name('profile.update');
        });
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// MAIN DOMAIN ROUTES (localhost — global storefront auth + super admin)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Storefront Auth (global — customer login once, can buy from any tenant) ──
Route::middleware('guest:web')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [AuthController::class, 'login']);
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth:web');

Route::get('/legal/{page}', [LegalController::class, 'show'])->name('legal.show');

// ─── Admin Auth (main domain) ──────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware('guest:admin_session')->group(function () {
        Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // ─── Super Admin Portal ────────────────────────────────────────────────────
    Route::middleware(['auth:admin_session', 'role:super_admin'])->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Tenants management
        Route::resource('tenants', TenantController::class)->names('tenants');
        Route::patch('tenants/{tenant}/toggle', [TenantController::class, 'toggleActive'])->name('tenants.toggle');

        // Products (global view)
        Route::resource('products', ProductAdminController::class)->names('products');
        Route::get('products/{id}/slots', [ProductAdminController::class, 'slots'])->name('products.slots');
        Route::post('products/{id}/generate-slots', [ProductAdminController::class, 'generateSlots'])->name('products.generate-slots');
        Route::put('slots/{slotId}', [ProductAdminController::class, 'updateSlot'])->name('slots.update');

        // Offers (global)
        Route::resource('offers', OfferAdminController::class)->names('offers');
        Route::get('offers/{offer}/promo-codes',      [OfferAdminController::class, 'promoCodes'])->name('offers.promo-codes');
        Route::post('offers/{offer}/promo-codes',     [OfferAdminController::class, 'storePromoCode'])->name('offers.promo-codes.store');
        Route::patch('promo-codes/{promo}/toggle',    [OfferAdminController::class, 'togglePromoCode'])->name('promo-codes.toggle');

        // Bookings (global)
        Route::get('bookings',        [BookingAdminController::class, 'index'])->name('bookings.index');
        Route::get('bookings/export', [BookingAdminController::class, 'export'])->name('bookings.export');
        Route::get('bookings/{id}',   [BookingAdminController::class, 'show'])->name('bookings.show');
        Route::put('bookings/{id}',   [BookingAdminController::class, 'update'])->name('bookings.update');
        Route::post('bookings',       [BookingAdminController::class, 'storeManual'])->name('bookings.store-manual');

        // Invoices (global)
        Route::get('invoices',        [InvoiceAdminController::class, 'index'])->name('invoices.index');
        Route::get('invoices/export', [InvoiceAdminController::class, 'export'])->name('invoices.export');
        Route::get('invoices/{code}', [InvoiceAdminController::class, 'show'])->name('invoices.show');

        // Customers (global)
        Route::get('customers',                       [CustomerAdminController::class, 'index'])->name('customers.index');
        Route::get('customers/export',                [CustomerAdminController::class, 'export'])->name('customers.export');
        Route::get('customers/{id}',                  [CustomerAdminController::class, 'show'])->name('customers.show');
        Route::post('customers',                      [CustomerAdminController::class, 'store'])->name('customers.store');
        Route::put('customers/{id}',                  [CustomerAdminController::class, 'update'])->name('customers.update');
        Route::delete('customers/{id}',               [CustomerAdminController::class, 'destroy'])->name('customers.destroy');
        Route::post('customers/{id}/restore',         [CustomerAdminController::class, 'restore'])->name('customers.restore');
        Route::patch('customers/{id}/toggle-active',  [CustomerAdminController::class, 'toggleActive'])->name('customers.toggle-active');

        // Users (global)
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

        // Profile
        Route::get('profile',  [AdminProfileController::class, 'show'])->name('profile.show');
        Route::put('profile',  [AdminProfileController::class, 'update'])->name('profile.update');
    });
});
