<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Jobs\SendBookingPaidEmail;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BookingService;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    // ── Inisiasi pembayaran (dispatcher) ──────────────────────────────────────

    public function initiate(Request $request, string $bookingCode): JsonResponse
    {
        $order = Order::with('payments')
            ->where('booking_code', $bookingCode)
            ->firstOrFail();

        if (! in_array($order->status->value, ['pending', 'awaiting_payment'])) {
            return response()->json(['message' => 'Pesanan tidak bisa dibayar pada status ini.'], 422);
        }

        // Tentukan gateway yang diminta (default: midtrans jika tidak diisi)
        $gateway = $request->input('gateway', 'midtrans');

        if (! in_array($gateway, ['midtrans', 'doku'])) {
            return response()->json(['message' => 'Gateway tidak didukung.'], 422);
        }

        // Kembalikan pending payment yang sudah ada untuk gateway yang sama
        $existing = $order->payments
            ->where('status', 'pending')
            ->where('gateway', $gateway)
            ->whereNotNull('invoice_number')
            ->first();

        if ($existing) {
            return $this->existingPaymentResponse($gateway, $existing);
        }

        // Hitung tipe dan jumlah pembayaran
        $paidAmount = $order->payments->where('status', 'success')->sum('amount');

        if ($order->payment_type === 'down_payment' && $paidAmount === 0) {
            $paymentType = 'dp';
            $amount      = $order->dp_amount;
        } elseif ($order->payment_type === 'down_payment' && $paidAmount > 0) {
            $paymentType = 'remaining';
            $amount      = $order->remaining_amount;
        } else {
            $paymentType = 'full';
            $amount      = $order->total;
        }

        return match ($gateway) {
            'midtrans' => $this->initiateWithMidtrans($order, $paymentType, $amount),
            'doku'     => $this->initiateWithDoku($order, $paymentType, $amount),
        };
    }

    // ── Midtrans Snap ─────────────────────────────────────────────────────────

    private function initiateWithMidtrans(Order $order, string $paymentType, int $amount): JsonResponse
    {
        $serverKey = config('services.midtrans.server_key');

        if (empty($serverKey)) {
            return response()->json(['message' => 'Midtrans belum dikonfigurasi.'], 503);
        }

        $invoiceNumber = BookingService::generateInvoiceNumber();
        $isProd        = config('services.midtrans.is_production');

        $response = Http::withBasicAuth($serverKey, '')
            ->post(
                $isProd
                    ? 'https://app.midtrans.com/snap/v1/transactions'
                    : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
                [
                    'transaction_details' => [
                        'order_id'     => $invoiceNumber,
                        'gross_amount' => $amount,
                    ],
                    'customer_details' => [
                        'first_name' => $order->customer_name,
                        'email'      => $order->customer_email,
                        'phone'      => $order->customer_phone,
                    ],
                    'callbacks' => [
                        'finish' => env('FRONTEND_URL', 'http://localhost:3000')
                            . '/payment?code=' . $order->booking_code,
                    ],
                ]
            );

        if (! $response->successful()) {
            Log::error('Midtrans Snap error', [
                'invoice' => $invoiceNumber,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return response()->json(['message' => 'Gagal membuat sesi pembayaran Midtrans.'], 502);
        }

        $snapData = $response->json();

        $payment = Payment::create([
            'order_id'       => $order->id,
            'invoice_number' => $invoiceNumber,
            'gateway'        => 'midtrans',
            'snap_token'     => $snapData['token'],
            'payment_type'   => $paymentType,
            'amount'         => $amount,
            'status'         => 'pending',
        ]);

        $order->update(['status' => 'awaiting_payment']);

        return response()->json([
            'data' => [
                'gateway'        => 'midtrans',
                'snap_token'     => $payment->snap_token,
                'snap_url'       => config('services.midtrans.snap_url'),
                'invoice_number' => $payment->invoice_number,
            ],
        ]);
    }

    // ── DOKU Checkout ────────────────────────────────────────────────────────

    private function initiateWithDoku(Order $order, string $paymentType, int $amount): JsonResponse
    {
        $clientId  = config('services.doku.client_id');
        $secretKey = config('services.doku.secret_key');
        $baseUrl   = config('services.doku.base_url', 'https://api-sandbox.doku.com');

        if (empty($clientId) || empty($secretKey)) {
            return response()->json(['message' => 'DOKU belum dikonfigurasi.'], 503);
        }

        $invoiceNumber = BookingService::generateInvoiceNumber();
        $requestTarget = '/checkout/v1/payment';
        $requestId     = Str::uuid()->toString();
        $timestamp     = now()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');

        $payload = [
            'order' => [
                'invoice_number'  => $invoiceNumber,
                'line_items'      => [[
                    'name'     => 'Pesanan ' . $order->booking_code,
                    'price'    => $amount,
                    'quantity' => 1,
                ]],
                'amount'          => $amount,
                'currency'        => 'IDR',
                'callback_url'    => env('FRONTEND_URL', 'http://localhost:3000')
                    . '/payment?code=' . $order->booking_code,
                'callback_url_cancel' => env('FRONTEND_URL', 'http://localhost:3000')
                    . '/payment?code=' . $order->booking_code,
            ],
            'payment' => [
                'payment_due_date' => 60, // menit
            ],
            'customer' => [
                'id'    => 'cust-' . $order->id,
                'name'  => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
        ];

        $signature = $this->buildDokuSignature(
            $clientId, $secretKey, $requestId, $timestamp, $requestTarget, json_encode($payload)
        );

        $response = Http::withHeaders([
            'Client-Id'         => $clientId,
            'Request-Id'        => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature'         => $signature,
        ])->post($baseUrl . $requestTarget, $payload);

        if (! $response->successful()) {
            Log::error('DOKU Checkout error', [
                'invoice' => $invoiceNumber,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return response()->json(['message' => 'Gagal membuat sesi pembayaran DOKU.'], 502);
        }

        $dokuData   = $response->json();
        $paymentUrl = $dokuData['payment']['url'] ?? null;

        if (empty($paymentUrl)) {
            Log::error('DOKU response missing payment URL', ['response' => $dokuData]);
            return response()->json(['message' => 'Respons DOKU tidak valid.'], 502);
        }

        $payment = Payment::create([
            'order_id'       => $order->id,
            'invoice_number' => $invoiceNumber,
            'gateway'        => 'doku',
            'ref_id'         => $paymentUrl,   // URL redirect pembayaran
            'payment_type'   => $paymentType,
            'amount'         => $amount,
            'status'         => 'pending',
        ]);

        $order->update(['status' => 'awaiting_payment']);

        return response()->json([
            'data' => [
                'gateway'        => 'doku',
                'payment_url'    => $payment->ref_id,
                'invoice_number' => $payment->invoice_number,
            ],
        ]);
    }

    // ── Webhook Midtrans ──────────────────────────────────────────────────────

    public function midtransWebhook(Request $request): JsonResponse
    {
        $serverKey = config('services.midtrans.server_key');
        $payload   = $request->all();

        $signatureKey = hash('sha512',
            ($payload['order_id'] ?? '') .
            ($payload['status_code'] ?? '') .
            ($payload['gross_amount'] ?? '') .
            $serverKey
        );

        if ($signatureKey !== ($payload['signature_key'] ?? '')) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $invoiceNumber = $payload['order_id'] ?? '';
        $transStatus   = $payload['transaction_status'] ?? '';
        $fraudStatus   = $payload['fraud_status'] ?? 'accept';

        $payment = Payment::with('order')->where('invoice_number', $invoiceNumber)->first();
        if (! $payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        $order  = $payment->order;
        $isPaid = in_array($transStatus, ['capture', 'settlement']) && $fraudStatus !== 'deny';

        if ($isPaid) {
            $payment->update([
                'status'  => 'success',
                'paid_at' => now(),
                'payload' => $payload,
            ]);

            $this->handleSuccessfulPayment($order);
        } elseif (in_array($transStatus, ['cancel', 'expire', 'deny'])) {
            $payment->update([
                'status'  => $transStatus === 'expire' ? 'expired' : 'failed',
                'payload' => $payload,
            ]);

            $this->handleFailedPayment($order, $transStatus === 'expire');
        }

        Log::info('Midtrans webhook', ['invoice' => $invoiceNumber, 'status' => $transStatus]);

        return response()->json(['message' => 'OK']);
    }

    // ── Webhook DOKU ──────────────────────────────────────────────────────────

    public function dokuWebhook(Request $request): JsonResponse
    {
        $clientId  = config('services.doku.client_id');
        $secretKey = config('services.doku.secret_key');

        if (empty($clientId) || empty($secretKey)) {
            return response()->json(['message' => 'Gateway not configured.'], 503);
        }

        // Verifikasi signature dari DOKU
        $requestId  = $request->header('Request-Id', '');
        $timestamp  = $request->header('Request-Timestamp', '');
        $dokuSig    = $request->header('Signature', '');
        $bodyRaw    = $request->getContent();

        $expectedSig = $this->buildDokuSignature(
            $clientId, $secretKey, $requestId, $timestamp, '/payment/notify', $bodyRaw
        );

        if (! hash_equals($expectedSig, $dokuSig)) {
            Log::warning('DOKU webhook invalid signature', ['request_id' => $requestId]);
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $payload       = $request->all();
        $invoiceNumber = $payload['order']['invoice_number'] ?? '';
        $transStatus   = strtoupper($payload['transaction']['status'] ?? '');

        $payment = Payment::with('order')->where('invoice_number', $invoiceNumber)->first();
        if (! $payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        $order = $payment->order;

        if ($transStatus === 'SUCCESS') {
            $payment->update([
                'status'  => 'success',
                'paid_at' => now(),
                'payload' => $payload,
            ]);

            $this->handleSuccessfulPayment($order);
        } elseif (in_array($transStatus, ['FAILED', 'EXPIRED'])) {
            $payment->update([
                'status'  => $transStatus === 'EXPIRED' ? 'expired' : 'failed',
                'payload' => $payload,
            ]);

            $this->handleFailedPayment($order, $transStatus === 'EXPIRED');
        }

        Log::info('DOKU webhook', ['invoice' => $invoiceNumber, 'status' => $transStatus]);

        return response()->json(['message' => 'OK']);
    }

    // ── Verifikasi tiket ──────────────────────────────────────────────────────

    public function verifyTicket(string $qrCode): JsonResponse
    {
        try {
            $result = $this->ticketService->verifyQrCode($qrCode);
            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Update status order dan generate tiket setelah pembayaran sukses.
     * Berlaku untuk semua gateway.
     */
    private function handleSuccessfulPayment(Order $order): void
    {
        $successPayments = $order->payments()->where('status', 'success')->get();
        $isDpPaid        = $order->payment_type === 'down_payment' && $successPayments->count() === 1;

        $order->update(['status' => $isDpPaid ? 'dp_paid' : 'paid']);

        if (! $isDpPaid) {
            $this->ticketService->generateForOrder($order);
        }

        $latestPayment = $successPayments->sortByDesc('paid_at')->first();

        try {
            SendBookingPaidEmail::dispatch($order->fresh(), $latestPayment);
        } catch (\Throwable) {
            // Email failure must not affect payment processing
        }
    }

    /**
     * Update status order saat pembayaran gagal/expire.
     * Tidak mengubah order jika sudah ada pembayaran sukses sebelumnya.
     */
    private function handleFailedPayment(Order $order, bool $isExpired): void
    {
        if ($order->payments()->where('status', 'success')->doesntExist()) {
            $order->update(['status' => $isExpired ? 'expired' : 'cancelled']);
        }
    }

    /**
     * Kembalikan response untuk pending payment yang sudah ada.
     */
    private function existingPaymentResponse(string $gateway, Payment $payment): JsonResponse
    {
        $data = ['gateway' => $gateway, 'invoice_number' => $payment->invoice_number];

        if ($gateway === 'midtrans') {
            $data['snap_token'] = $payment->snap_token;
            $data['snap_url']   = config('services.midtrans.snap_url');
        } else {
            $data['payment_url'] = $payment->ref_id;
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Bangun DOKU request/notification signature.
     * Format: Base64(HMAC-SHA256(secret, "Client-Id:...\nRequest-Id:...\n...Digest:..."))
     */
    private function buildDokuSignature(
        string $clientId,
        string $secretKey,
        string $requestId,
        string $timestamp,
        string $requestTarget,
        string $body
    ): string {
        $digest     = base64_encode(hash('sha256', $body, true));
        $components = implode("\n", [
            "Client-Id:{$clientId}",
            "Request-Id:{$requestId}",
            "Request-Timestamp:{$timestamp}",
            "Request-Target:{$requestTarget}",
            "Digest:{$digest}",
        ]);

        return base64_encode(hash_hmac('sha256', $components, $secretKey, true));
    }
}
