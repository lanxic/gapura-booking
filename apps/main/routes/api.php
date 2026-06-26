<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Public\ActivityController;
use App\Http\Controllers\Api\Public\InvoiceController;
use App\Http\Controllers\Api\Public\OfferController;
use App\Http\Controllers\Api\Public\WebhookController;
use App\Http\Controllers\Api\Staff\StaffSlotController;
use App\Http\Controllers\Api\Staff\StaffScanController;
use App\Http\Controllers\Api\Admin\ActivityAdminController;
use App\Http\Controllers\Api\Admin\ActivityLogAdminController;
use App\Http\Controllers\Api\Admin\BookingAdminController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\GatewaySettingsController;
use App\Http\Controllers\Api\Admin\OfferAdminController;
use App\Http\Controllers\Api\Admin\CustomerAdminController;
use App\Http\Controllers\Api\Admin\InvoiceAdminController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Public\CustomerBookingController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

// ─── Health Check ─────────────────────────────────────────────────────────────
Route::get('v1/health', function () {
    $db = $redis = 'ok';
    try { DB::connection()->getPdo(); } catch (\Throwable) { $db = 'error'; }
    try { Redis::ping(); }            catch (\Throwable) { $redis = 'error'; }
    $healthy = $db === 'ok' && $redis === 'ok';
    return response()->json(['status' => $healthy ? 'ok' : 'degraded', 'db' => $db, 'redis' => $redis], $healthy ? 200 : 503);
});

// ─── Auth ─────────────────────────────────────────────────────────────────────
Route::prefix('v1/auth')->group(function () {
    Route::post('customer/register',            [AuthController::class, 'registerCustomer']);
    Route::get('customer/verify/{token}',       [AuthController::class, 'verifyEmail']);
    Route::post('customer/resend-verification', [AuthController::class, 'resendVerification']);
    Route::post('customer/login',               [AuthController::class, 'loginCustomer']);
    Route::post('admin/login',                  [AuthController::class, 'loginAdmin']);
    Route::post('scanner/login',                [AuthController::class, 'loginScanner']);

    Route::middleware('auth:api')->group(function () {
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::get('me',       [AuthController::class, 'me']);
    });
});

// ─── Public — Activity Booking (PRD Section 7.1) ──────────────────────────────
Route::prefix('v1')->group(function () {
    // Activities
    Route::get('activities',              [ActivityController::class, 'index']);
    Route::get('activities/{slug}',       [ActivityController::class, 'show']);
    Route::get('activities/{slug}/slots', [ActivityController::class, 'slots']);

    // Invoices (PRD Section 13)
    Route::post('invoices',                                  [InvoiceController::class, 'store']);
    Route::get('invoices/{invoice_code}',                    [InvoiceController::class, 'show']);
    Route::post('invoices/{invoice_code}/retry-payment',     [InvoiceController::class, 'retryPayment']);

    // Payments
    Route::post('payments/webhook', [WebhookController::class, 'handle'])->name('payment.webhook');
    Route::get('payments/finish',   [InvoiceController::class, 'finish'])->name('payment.finish');

    // Offers & Promo
    Route::get('offers',              [OfferController::class, 'index']);
    Route::get('offers/{slug}',       [OfferController::class, 'show']);
    Route::post('promo/validate',     [OfferController::class, 'validatePromo']);
});

// ─── Customer — Booking History (auth required) ───────────────────────────────
Route::prefix('v1/me')->middleware('auth:api')->group(function () {
    Route::get('bookings',                  [CustomerBookingController::class, 'index']);
    Route::get('bookings/{code}',           [CustomerBookingController::class, 'show']);
    Route::get('bookings/{code}/qr',        [CustomerBookingController::class, 'qr']);
});

// ─── Staff / Scanner (apps/scanner PWA) ──────────────────────────────────────
Route::prefix('v1/staff')->middleware('auth:scanner')->group(function () {
    Route::get('slots',                    [StaffSlotController::class, 'index']);
    Route::get('slots/{slotId}/checkins',  [StaffSlotController::class, 'checkins']);
    Route::post('bookings/validate',       [StaffScanController::class, 'validate']);
});

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::prefix('v1/admin')->middleware('auth:admin')->group(function () {
    // Dashboard
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // Users
    Route::apiResource('users', UserManagementController::class);

    // Activity Logs
    Route::get('activity-logs',         [ActivityLogAdminController::class, 'index']);
    Route::get('activity-logs/export',  [ActivityLogAdminController::class, 'export']);
    Route::get('activity-logs/summary', [ActivityLogAdminController::class, 'summary']);

    // Activities + Slots
    Route::apiResource('activities', ActivityAdminController::class);
    Route::post('activities/{id}/generate-slots',   [ActivityAdminController::class, 'generateSlots']);
    Route::get('activities/{id}/slots',             [ActivityAdminController::class, 'slotsIndex']);
    Route::put('slots/{slotId}',                    [ActivityAdminController::class, 'slotUpdate']);

    // Bookings
    Route::get('bookings',         [BookingAdminController::class, 'index']);
    Route::get('bookings/export',  [BookingAdminController::class, 'export']);
    Route::get('bookings/{id}',    [BookingAdminController::class, 'show']);
    Route::put('bookings/{id}',    [BookingAdminController::class, 'update']);
    Route::post('bookings',        [BookingAdminController::class, 'storeManual']);

    // Customers (PRD Section 4.5)
    Route::get('customers',                         [CustomerAdminController::class, 'index']);
    Route::post('customers',                        [CustomerAdminController::class, 'store']);
    Route::get('customers/export',                  [CustomerAdminController::class, 'export']);
    Route::get('customers/{id}',                    [CustomerAdminController::class, 'show']);
    Route::put('customers/{id}',                    [CustomerAdminController::class, 'update']);
    Route::delete('customers/{id}',                 [CustomerAdminController::class, 'destroy']);
    Route::post('customers/{id}/restore',           [CustomerAdminController::class, 'restore']);
    Route::patch('customers/{id}/toggle-active',    [CustomerAdminController::class, 'toggleActive']);

    // Invoices (PRD Section 4.4.1a)
    Route::get('invoices',         [InvoiceAdminController::class, 'index']);
    Route::get('invoices/export',  [InvoiceAdminController::class, 'export']);
    Route::get('invoices/{code}',  [InvoiceAdminController::class, 'show']);

    // Payment Gateways
    Route::get('settings/payment-gateways',                  [GatewaySettingsController::class, 'gateways']);
    Route::put('settings/payment-gateways/{name}',           [GatewaySettingsController::class, 'updateGateway']);
    Route::post('settings/payment-gateways/{name}/activate', [GatewaySettingsController::class, 'activateGateway']);

    // Payment Plans
    Route::get('settings/payment-plans',        [GatewaySettingsController::class, 'paymentPlans']);
    Route::put('settings/payment-plans/{code}', [GatewaySettingsController::class, 'updatePaymentPlan']);

    // Storage Providers
    Route::get('settings/storage',                  [GatewaySettingsController::class, 'storageProviders']);
    Route::put('settings/storage/{name}',            [GatewaySettingsController::class, 'updateStorageProvider']);
    Route::post('settings/storage/{name}/activate',  [GatewaySettingsController::class, 'activateStorageProvider']);

    // System Settings test endpoints (before generic {group} so they take priority)
    Route::post('settings/email/test',      [GatewaySettingsController::class, 'testEmail']);
    Route::post('settings/cloudinary/test', [GatewaySettingsController::class, 'testCloudinarySettings']);
    Route::post('settings/aws/test',        [GatewaySettingsController::class, 'testAwsSettings']);

    // System Settings (email, whatsapp, booking, general, notifications, hero, cloudinary, aws)
    Route::get('settings/{group}', [GatewaySettingsController::class, 'getSystemSettings']);
    Route::put('settings/{group}', [GatewaySettingsController::class, 'updateSystemSettings']);

    // Offers & Promo Codes
    Route::apiResource('offers', OfferAdminController::class);
    Route::get('offers/{offer}/promo-codes',        [OfferAdminController::class, 'promoCodes']);
    Route::post('offers/{offer}/promo-codes',       [OfferAdminController::class, 'storePromoCode']);
    Route::patch('promo-codes/{promo}/toggle',      [OfferAdminController::class, 'togglePromoCode']);
});
