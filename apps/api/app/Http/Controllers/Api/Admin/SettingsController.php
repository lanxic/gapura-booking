<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary) {}

    // ── General ─────────────────────────────────────────────────────────────

    /** Public — safe fields only (no secrets). Used by storefront layout. */
    public function publicGeneral(): JsonResponse
    {
        $all = SiteSetting::getCastGroup('general');

        return response()->json(['data' => [
            'app_name'         => $all['app_name']         ?? null,
            'app_description'  => $all['app_description']  ?? null,
            'logo_url'         => $all['logo_url']         ?? null,
            'contact_email'    => $all['contact_email']    ?? null,
            'contact_phone'    => $all['contact_phone']    ?? null,
            'contact_address'  => $all['contact_address']  ?? null,
            'copyright_text'   => $all['copyright_text']   ?? null,
            'footer_bg_color'  => $all['footer_bg_color']  ?? null,
            'facebook_url'     => $all['facebook_url']     ?? null,
            'instagram_url'    => $all['instagram_url']    ?? null,
            'twitter_url'      => $all['twitter_url']      ?? null,
            'youtube_url'      => $all['youtube_url']      ?? null,
            'tripadvisor_url'  => $all['tripadvisor_url']  ?? null,
        ]]);
    }

    public function general(): JsonResponse
    {
        return response()->json(['data' => SiteSetting::getCastGroup('general')]);
    }

    public function updateGeneral(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'app_name'        => 'nullable|string|max:100',
            'app_description' => 'nullable|string|max:500',
            'logo_url'        => 'nullable|string|max:500',
            'favicon_url'     => 'nullable|string|max:500',
            'contact_email'   => 'nullable|email|max:255',
            'contact_phone'   => 'nullable|string|max:50',
            'contact_address' => 'nullable|string|max:500',
            'copyright_text'  => 'nullable|string|max:200',
            'footer_bg_color' => 'nullable|string|max:20',
            'facebook_url'    => 'nullable|string|max:300',
            'instagram_url'   => 'nullable|string|max:300',
            'twitter_url'     => 'nullable|string|max:300',
            'youtube_url'     => 'nullable|string|max:300',
            'tripadvisor_url' => 'nullable|string|max:300',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        SiteSetting::setGroupEncoded('general', $request->only([
            'app_name', 'app_description', 'logo_url', 'favicon_url',
            'contact_email', 'contact_phone', 'contact_address', 'copyright_text',
            'footer_bg_color',
            'facebook_url', 'instagram_url', 'twitter_url', 'youtube_url', 'tripadvisor_url',
        ]));

        return response()->json(['data' => SiteSetting::getCastGroup('general')]);
    }

    /** Upload logo image to Cloudinary, save & return URL. */
    public function uploadLogoImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $result = $this->cloudinary->uploadImage(
                $request->file('file'),
                'site/logo',
                [['width' => 400, 'height' => 200, 'crop' => 'fit']],
            );

            $logoUrl = $result['secure_url'];

            SiteSetting::setGroupEncoded('general', ['logo_url' => $logoUrl]);

            return response()->json(['data' => ['logo_url' => $logoUrl]]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Upload gagal: ' . $e->getMessage()], 500);
        }
    }

    // ── Payment ──────────────────────────────────────────────────────────────

    public function paymentOptions(): JsonResponse
    {
        $data = SiteSetting::getCastGroup('payment');

        // Fallback defaults if DB is empty
        if (empty($data)) {
            $data = [
                'full_payment'     => true,
                'down_payment'     => true,
                'dp_percentages'   => [30, 50, 70],
                'midtrans_enabled' => true,
                'cash_enabled'     => true,
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function updatePaymentOptions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_payment'     => 'boolean',
            'down_payment'     => 'boolean',
            'dp_percentages'   => 'array',
            'dp_percentages.*' => 'integer|in:30,50,70',
            'midtrans_enabled' => 'boolean',
            'cash_enabled'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        SiteSetting::setGroupEncoded('payment', $request->only([
            'full_payment', 'down_payment', 'dp_percentages', 'midtrans_enabled', 'cash_enabled',
        ]));

        return response()->json(['data' => SiteSetting::getCastGroup('payment')]);
    }

    // ── Notifications ────────────────────────────────────────────────────────

    public function notifications(): JsonResponse
    {
        return response()->json(['data' => SiteSetting::getCastGroup('notifications')]);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_order'      => 'boolean',
            'email_payment'    => 'boolean',
            'whatsapp_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        SiteSetting::setGroupEncoded('notifications', $request->only([
            'email_order', 'email_payment', 'whatsapp_enabled',
        ]));

        return response()->json(['data' => SiteSetting::getCastGroup('notifications')]);
    }

    // ── Payment Gateways ─────────────────────────────────────────────────────

    public function gateways(): JsonResponse
    {
        $midtrans = SiteSetting::getCastGroup('gateway_midtrans');
        $doku     = SiteSetting::getCastGroup('gateway_doku');
        $cash     = SiteSetting::getCastGroup('gateway_cash');

        return response()->json([
            'data' => [
                'midtrans' => [
                    'enabled'              => $midtrans['enabled']     ?? false,
                    'environment'          => $midtrans['environment'] ?? 'sandbox',
                    'server_key'           => '',
                    'client_key'           => '',
                    'server_key_configured' => ! empty($midtrans['server_key']),
                    'client_key_configured' => ! empty($midtrans['client_key']),
                    'snap_url'             => $midtrans['snap_url']    ?? '',
                ],
                'doku' => [
                    'enabled'              => $doku['enabled']         ?? false,
                    'environment'          => $doku['environment']     ?? 'sandbox',
                    'mall_id'              => $doku['mall_id']         ?? '',
                    'client_id'            => $doku['client_id']       ?? '',
                    'secret_key'           => '',
                    'secret_key_configured' => ! empty($doku['secret_key']),
                ],
                'cash' => [
                    'enabled' => $cash['enabled'] ?? true,
                ],
            ],
        ]);
    }

    public function updateGatewayMidtrans(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled'     => 'boolean',
            'environment' => 'in:sandbox,production',
            'server_key'  => 'nullable|string|max:255',
            'client_key'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $payload = $request->only(['enabled', 'environment']);
        if ($request->filled('server_key')) $payload['server_key'] = $request->server_key;
        if ($request->filled('client_key')) $payload['client_key'] = $request->client_key;

        // Update snap_url sesuai environment
        $isProd = ($payload['environment'] ?? SiteSetting::getGroup('gateway_midtrans')['environment'] ?? 'sandbox') === 'production';
        $payload['snap_url'] = $isProd
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';

        SiteSetting::setGroupEncoded('gateway_midtrans', $payload);
        Cache::forget('gateway_midtrans_config');

        return response()->json(['data' => ['saved' => true]]);
    }

    public function testGatewayMidtrans(): JsonResponse
    {
        try {
            $cfg = SiteSetting::getGroup('gateway_midtrans');
            $serverKey = $cfg['server_key'] ?? '';

            if (empty($serverKey)) {
                return response()->json(['data' => ['connected' => false, 'error' => 'Server Key belum dikonfigurasi.']]);
            }

            $env     = ($cfg['environment'] ?? 'sandbox') === 'production' ? 'api' : 'api.sandbox';
            $url     = "https://{$env}.midtrans.com/v2/dummy-order-id/status";
            $encoded = base64_encode($serverKey . ':');

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => ["Authorization: Basic {$encoded}", 'Accept: application/json'],
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // 404 = key valid tapi order tidak ada (expected), 401 = unauthorized (key salah)
            $connected = in_array($httpCode, [404, 200]);

            return response()->json(['data' => [
                'connected' => $connected,
                'error'     => $connected ? null : 'Server Key tidak valid atau koneksi gagal.',
            ]]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['connected' => false, 'error' => $e->getMessage()]]);
        }
    }

    public function updateGatewayDoku(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled'     => 'boolean',
            'environment' => 'in:sandbox,production',
            'mall_id'     => 'nullable|string|max:100',
            'client_id'   => 'nullable|string|max:255',
            'secret_key'  => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $payload = $request->only(['enabled', 'environment', 'mall_id', 'client_id']);
        if ($request->filled('secret_key')) $payload['secret_key'] = $request->secret_key;

        SiteSetting::setGroupEncoded('gateway_doku', $payload);

        return response()->json(['data' => ['saved' => true]]);
    }

    public function testGatewayDoku(): JsonResponse
    {
        try {
            $cfg      = SiteSetting::getGroup('gateway_doku');
            $clientId = $cfg['client_id'] ?? '';
            $secretKey = $cfg['secret_key'] ?? '';

            if (empty($clientId) || empty($secretKey)) {
                return response()->json(['data' => ['connected' => false, 'error' => 'Client ID atau Secret Key belum dikonfigurasi.']]);
            }

            $env = ($cfg['environment'] ?? 'sandbox') === 'production'
                ? 'api.doku.com'
                : 'api-sandbox.doku.com';

            $url = "https://{$env}/orders/v1/payment";
            $ch  = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_NOBODY         => true,
                CURLOPT_HTTPHEADER     => ['Client-Id: ' . $clientId, 'Accept: application/json'],
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $connected = $httpCode > 0 && $httpCode !== 0;

            return response()->json(['data' => [
                'connected' => $connected,
                'http_code' => $httpCode,
                'error'     => $connected ? null : 'Tidak dapat terhubung ke server Doku.',
            ]]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['connected' => false, 'error' => $e->getMessage()]]);
        }
    }

    public function updateGatewayCash(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['enabled' => 'required|boolean']);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        SiteSetting::setGroupEncoded('gateway_cash', ['enabled' => $request->boolean('enabled')]);

        return response()->json(['data' => ['saved' => true]]);
    }

    // ── Email ────────────────────────────────────────────────────────────────

    public function email(): JsonResponse
    {
        $data = SiteSetting::getGroup('email');

        return response()->json([
            'data' => [
                'mailer'               => $data['mailer']       ?? 'smtp',
                'host'                 => $data['host']         ?? '',
                'port'                 => (int) ($data['port']  ?? 587),
                'username'             => $data['username']     ?? '',
                'password'             => '',
                'password_configured'  => ! empty($data['password']),
                'encryption'           => $data['encryption']   ?? 'tls',
                'from_address'         => $data['from_address'] ?? '',
                'from_name'            => $data['from_name']    ?? '',
            ],
        ]);
    }

    public function updateEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mailer'       => 'nullable|in:smtp,log,array',
            'host'         => 'nullable|string|max:255',
            'port'         => 'nullable|integer|min:1|max:65535',
            'username'     => 'nullable|string|max:255',
            'password'     => 'nullable|string|max:500',
            'encryption'   => 'nullable|in:tls,ssl,none',
            'from_address' => 'nullable|email|max:255',
            'from_name'    => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $payload = $request->only(['mailer', 'host', 'port', 'username', 'encryption', 'from_address', 'from_name']);
        if ($request->filled('password')) {
            $payload['password'] = $request->password;
        }

        SiteSetting::setGroupEncoded('email', $payload);
        Cache::forget('email_config');

        return response()->json(['data' => ['saved' => true]]);
    }

    public function testEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            Mail::raw('Ini adalah email uji coba dari Amartha eTicket Admin.', function ($message) use ($request) {
                $message->to($request->to)->subject('Test Email — Amartha eTicket');
            });

            return response()->json(['data' => ['sent' => true]]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['sent' => false, 'error' => $e->getMessage()]]);
        }
    }

    // ── Cloudinary ───────────────────────────────────────────────────────────

    public function cloudinary(): JsonResponse
    {
        $data = SiteSetting::getCastGroup('cloudinary');

        // Mask sensitive values — return only "configured" flag, not the actual value
        return response()->json([
            'data' => [
                'cloud_name'      => $data['cloud_name']    ?? '',
                'api_key'         => '',
                'api_secret'      => '',
                'api_key_configured'    => ! empty($data['api_key']),
                'api_secret_configured' => ! empty($data['api_secret']),
                'upload_preset'   => $data['upload_preset']    ?? '',
                'folder_products' => $data['folder_products']  ?? 'amartha/products',
                'folder_tickets'  => $data['folder_tickets']   ?? 'amartha/tickets',
                'folder_avatars'  => $data['folder_avatars']   ?? 'amartha/avatars',
                'auto_quality'    => $data['auto_quality']     ?? true,
                'auto_format'     => $data['auto_format']      ?? true,
                'max_width'       => (int) ($data['max_width']   ?? 1920),
                'thumb_width'     => (int) ($data['thumb_width'] ?? 400),
            ],
        ]);
    }

    public function updateCloudinary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cloud_name'      => 'nullable|string|max:100',
            'api_key'         => 'nullable|string|max:255',
            'api_secret'      => 'nullable|string|max:255',
            'upload_preset'   => 'nullable|string|max:100',
            'folder_products' => 'nullable|string|max:200',
            'folder_tickets'  => 'nullable|string|max:200',
            'folder_avatars'  => 'nullable|string|max:200',
            'auto_quality'    => 'boolean',
            'auto_format'     => 'boolean',
            'max_width'       => 'integer|min:100|max:5000',
            'thumb_width'     => 'integer|min:50|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $payload = $request->only([
            'cloud_name', 'upload_preset',
            'folder_products', 'folder_tickets', 'folder_avatars',
            'auto_quality', 'auto_format', 'max_width', 'thumb_width',
        ]);

        // Only update secrets if user actually provided a new value
        if ($request->filled('api_key'))    $payload['api_key']    = $request->api_key;
        if ($request->filled('api_secret')) $payload['api_secret'] = $request->api_secret;

        SiteSetting::setGroupEncoded('cloudinary', $payload);
        CloudinaryService::clearConfigCache();

        return response()->json(['data' => ['saved' => true]]);
    }

    public function testCloudinary(): JsonResponse
    {
        try {
            $ok = app(CloudinaryService::class)->testConnection();
            return response()->json(['data' => ['connected' => $ok]]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['connected' => false, 'error' => $e->getMessage()]]);
        }
    }

    // ── Legal ────────────────────────────────────────────────────────────────

    public function legal(): JsonResponse
    {
        return response()->json(['data' => SiteSetting::getCastGroup('legal')]);
    }

    public function updateLegal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'privacy_policy'   => 'nullable|string',
            'terms_of_service' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        SiteSetting::setGroupEncoded('legal', $request->only([
            'privacy_policy', 'terms_of_service',
        ]));

        return response()->json(['data' => SiteSetting::getCastGroup('legal')]);
    }
}
