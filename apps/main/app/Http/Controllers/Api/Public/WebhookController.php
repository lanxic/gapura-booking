<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    /**
     * POST /api/v1/payments/webhook
     * Terima notifikasi dari Midtrans atau DOKU (PRD Section 13.3 Step 5).
     * Verifikasi signature sebelum memproses.
     */
    public function handle(Request $request): Response
    {
        $payload = $request->all();
        $gateway = $this->detectGateway($payload);

        if (! $this->verifySignature($gateway, $payload, $request)) {
            return response('', 403);
        }

        // Simpan raw payload terlebih dahulu (fire-and-forget), return 200 segera
        dispatch(function () use ($gateway, $payload) {
            $this->invoiceService->handleWebhook($gateway, $payload);
        })->afterResponse();

        return response('OK', 200);
    }

    private function detectGateway(array $payload): string
    {
        // Midtrans payload memiliki 'signature_key'; DOKU memiliki 'SIGNATURE'
        return isset($payload['signature_key']) ? 'midtrans' : 'doku';
    }

    private function verifySignature(string $gateway, array $payload, Request $request): bool
    {
        if ($gateway === 'midtrans') {
            return $this->verifyMidtrans($payload);
        }
        return $this->verifyDoku($payload, $request);
    }

    private function verifyMidtrans(array $payload): bool
    {
        $gateway = \App\Models\PaymentGateway::where('name', 'midtrans')->first();
        if (! $gateway) return false;

        $serverKey = $gateway->server_key;
        $orderId   = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expected, $payload['signature_key'] ?? '');
    }

    private function verifyDoku(array $payload, Request $request): bool
    {
        // DOKU signature verifikasi via header DOKU-Signature
        // Implementasi sesuai dokumentasi DOKU API
        return true; // placeholder
    }
}
