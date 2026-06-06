<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Public\ProductController;
use App\Http\Controllers\Api\Public\OrderController as PublicOrderController;
use App\Http\Controllers\Api\Public\PaymentController as PublicPaymentController;
use App\Http\Controllers\Api\Customer\CustomerOrderController;
use App\Http\Controllers\Api\Customer\CustomerTicketController;
use App\Http\Controllers\Api\Scanner\ScannerController;
use App\Http\Controllers\Api\Kasir\KasirOrderController;
use App\Http\Controllers\Api\Supervisor\SupervisorCorrectionController;
use App\Http\Controllers\Api\Supervisor\SupervisorTicketController;
use App\Http\Controllers\Api\Supervisor\SupervisorOrderController;
use App\Http\Controllers\Api\Supervisor\SupervisorPaymentController;
use App\Http\Controllers\Api\Supervisor\SupervisorDashboardController;
use App\Http\Controllers\Api\Supervisor\ActivityLogController as SupervisorLogController;
use App\Http\Controllers\Api\Admin\ProductAdminController;
use App\Http\Controllers\Api\Admin\AvailabilityController;
use App\Http\Controllers\Api\Admin\OrderAdminController;
use App\Http\Controllers\Api\Admin\VoucherController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\ActivityLogAdminController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\CorrectionController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

// ─── v1 Routes ───────────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Health Check
    Route::get('health', function () {
        $db = 'ok';
        $redis = 'ok';

        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $db = 'error';
        }

        try {
            Redis::ping();
        } catch (\Throwable) {
            $redis = 'error';
        }

        $healthy = $db === 'ok' && $redis === 'ok';

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'db'     => $db,
            'redis'  => $redis,
        ], $healthy ? 200 : 503);
    });

    // ─── Public Routes ──────────────────────────────────────────────────────

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);
    Route::get('products/{slug}/availability', [ProductController::class, 'availability']);
    Route::get('payment-options', [SettingsController::class, 'paymentOptions']);
    Route::get('settings/general', [SettingsController::class, 'publicGeneral']);

    Route::post('orders', [PublicOrderController::class, 'store']);
    Route::post('orders/{bookingCode}/apply-voucher', [PublicOrderController::class, 'applyVoucher']);
    Route::get('orders/{bookingCode}', [PublicOrderController::class, 'show']);

    Route::post('orders/{bookingCode}/pay', [PublicPaymentController::class, 'initiate']);
    Route::post('payments/midtrans/notification', [PublicPaymentController::class, 'midtransWebhook']);
    Route::post('payments/doku/notification',     [PublicPaymentController::class, 'dokuWebhook']);
    Route::get('tickets/{qrCode}/verify', [PublicPaymentController::class, 'verifyTicket']);

    // ─── Auth Routes ────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('customer/register', [AuthController::class, 'registerCustomer']);
        Route::post('customer/login', [AuthController::class, 'loginCustomer']);
        Route::post('admin/login', [AuthController::class, 'loginAdmin']);
        Route::post('supervisor/login', [AuthController::class, 'loginSupervisor']);
        Route::post('scanner/login', [AuthController::class, 'loginScanner']);
        Route::post('kasir/login', [AuthController::class, 'loginKasir']);

        Route::middleware('auth:api')->group(function () {
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // ─── Customer Routes ────────────────────────────────────────────────────
    Route::prefix('customer')->middleware('auth:customer')->group(function () {
        Route::get('orders', [CustomerOrderController::class, 'index']);
        Route::get('orders/{bookingCode}', [CustomerOrderController::class, 'show']);
        Route::put('orders/{bookingCode}/reschedule', [CustomerOrderController::class, 'reschedule']);
        Route::get('tickets', [CustomerTicketController::class, 'index']);
        Route::get('tickets/{qrCode}/download', [CustomerTicketController::class, 'download']);
    });

    // ─── Scanner Routes ─────────────────────────────────────────────────────
    Route::prefix('scanner')->middleware('auth:scanner')->group(function () {
        Route::post('tickets/scan', [ScannerController::class, 'scan']);
        Route::get('tickets/{qrCode}', [ScannerController::class, 'preview']);
        Route::get('logs', [ScannerController::class, 'logs']);
    });

    // ─── Kasir Routes ────────────────────────────────────────────────────────
    Route::prefix('kasir')->middleware('auth:kasir')->group(function () {
        Route::get('orders/{bookingCode}', [KasirOrderController::class, 'show']);
        Route::get('orders/{bookingCode}/payment-summary', [KasirOrderController::class, 'paymentSummary']);
        Route::post('orders/{bookingCode}/collect', [KasirOrderController::class, 'collect']);
        Route::get('logs', [KasirOrderController::class, 'logs']);
    });

    // ─── Correction Requests (Scanner & Kasir submit; Supervisor+ review) ───
    Route::middleware('auth:api')->group(function () {
        Route::post('corrections', [CorrectionController::class, 'store']);
        Route::get('corrections/mine', [CorrectionController::class, 'mine']);
        Route::get('corrections', [CorrectionController::class, 'index']);
        Route::post('corrections/{id}/approve', [CorrectionController::class, 'approve']);
        Route::post('corrections/{id}/reject', [CorrectionController::class, 'reject']);
    });

    // ─── Supervisor Routes ───────────────────────────────────────────────────
    Route::prefix('supervisor')->middleware('auth:supervisor')->group(function () {
        Route::get('dashboard', [SupervisorDashboardController::class, 'index']);
        Route::get('corrections', [SupervisorCorrectionController::class, 'index']);
        Route::get('corrections/{id}', [SupervisorCorrectionController::class, 'show']);
        Route::post('corrections/{id}/approve', [SupervisorCorrectionController::class, 'approve']);
        Route::post('corrections/{id}/reject', [SupervisorCorrectionController::class, 'reject']);
        Route::put('tickets/{id}/status', [SupervisorTicketController::class, 'updateStatus']);
        Route::put('orders/{id}/status', [SupervisorOrderController::class, 'updateStatus']);
        Route::post('payments/{id}/void', [SupervisorPaymentController::class, 'void']);
        Route::get('activity-logs', [SupervisorLogController::class, 'index']);
        Route::get('activity-logs/{id}', [SupervisorLogController::class, 'show']);
    });

    // ─── Activity Logs (Supervisor / Admin / Super Admin) ───────────────────
    Route::prefix('activity-logs')->middleware('auth:api')->group(function () {
        Route::get('/', [ActivityLogAdminController::class, 'index']);
        Route::get('/export', [ActivityLogAdminController::class, 'export']);
        Route::get('/summary', [ActivityLogAdminController::class, 'summary']);
        Route::get('/{id}', [ActivityLogAdminController::class, 'show']);
    });

    // ─── Admin Routes ────────────────────────────────────────────────────────
    Route::prefix('admin')->middleware('auth:admin')->group(function () {
        Route::apiResource('products', ProductAdminController::class);
        Route::apiResource('products.variants', 'App\Http\Controllers\Api\Admin\ProductVariantController');
        Route::apiResource('products.addons', 'App\Http\Controllers\Api\Admin\ProductAddonController');

        Route::get('availability', [AvailabilityController::class, 'index']);
        Route::post('availability', [AvailabilityController::class, 'store']);
        Route::post('availability/bulk', [AvailabilityController::class, 'bulk']);
        Route::post('availability/block', [AvailabilityController::class, 'block']);
        Route::post('availability/reset', [AvailabilityController::class, 'reset']);
        Route::put('availability/{id}', [AvailabilityController::class, 'update']);
        Route::delete('availability/{id}', [AvailabilityController::class, 'destroy']);

        Route::post('settings/general/upload-logo',        [SettingsController::class, 'uploadLogoImage']);
        Route::get('settings/gateways',                    [SettingsController::class, 'gateways']);
        Route::put('settings/gateways/midtrans',           [SettingsController::class, 'updateGatewayMidtrans']);
        Route::post('settings/gateways/midtrans/test',     [SettingsController::class, 'testGatewayMidtrans']);
        Route::put('settings/gateways/doku',               [SettingsController::class, 'updateGatewayDoku']);
        Route::post('settings/gateways/doku/test',         [SettingsController::class, 'testGatewayDoku']);
        Route::put('settings/gateways/cash',               [SettingsController::class, 'updateGatewayCash']);
        Route::get('settings/general',                     [SettingsController::class, 'general']);
        Route::put('settings/general',         [SettingsController::class, 'updateGeneral']);
        Route::get('settings/payment-options', [SettingsController::class, 'paymentOptions']);
        Route::put('settings/payment-options', [SettingsController::class, 'updatePaymentOptions']);
        Route::get('settings/notifications',   [SettingsController::class, 'notifications']);
        Route::put('settings/notifications',   [SettingsController::class, 'updateNotifications']);
        Route::get('settings/legal',           [SettingsController::class, 'legal']);
        Route::put('settings/legal',           [SettingsController::class, 'updateLegal']);
        Route::get('settings/email',            [SettingsController::class, 'email']);
        Route::put('settings/email',            [SettingsController::class, 'updateEmail']);
        Route::post('settings/email/test',      [SettingsController::class, 'testEmail']);
        Route::get('settings/cloudinary',        [SettingsController::class, 'cloudinary']);
        Route::put('settings/cloudinary',        [SettingsController::class, 'updateCloudinary']);
        Route::post('settings/cloudinary/test',  [SettingsController::class, 'testCloudinary']);
        Route::get('settings/storage/driver',    [SettingsController::class, 'storageDriver']);
        Route::put('settings/storage/driver',    [SettingsController::class, 'updateStorageDriver']);
        Route::get('settings/aws',               [SettingsController::class, 'getAws']);
        Route::put('settings/aws',               [SettingsController::class, 'updateAws']);
        Route::post('settings/aws/test',         [SettingsController::class, 'testAws']);

        Route::apiResource('orders', OrderAdminController::class)->only(['index', 'show', 'update']);
        Route::put('orders/{id}/status', [OrderAdminController::class, 'updateStatus']);
        Route::post('orders/{id}/refund', [OrderAdminController::class, 'refund']);

        Route::apiResource('vouchers', VoucherController::class);

        Route::get('reports/sales', [ReportController::class, 'sales']);
        Route::get('reports/outstanding', [ReportController::class, 'outstanding']);
        Route::get('reports/export', [ReportController::class, 'export']);

        Route::apiResource('users', UserManagementController::class);

        Route::get('activity-logs', [ActivityLogAdminController::class, 'index']);
        Route::get('activity-logs/export', [ActivityLogAdminController::class, 'export']);
        Route::get('activity-logs/summary', [ActivityLogAdminController::class, 'summary']);
    });
});
