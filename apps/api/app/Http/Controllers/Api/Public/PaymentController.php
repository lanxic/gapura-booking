<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

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

        $bookingCode  = $payload['order_id'] ?? '';
        $transStatus  = $payload['transaction_status'] ?? '';
        $fraudStatus  = $payload['fraud_status'] ?? 'accept';

        $order = Order::where('booking_code', $bookingCode)->first();
        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $isPaid = in_array($transStatus, ['capture', 'settlement']) && $fraudStatus !== 'deny';

        if ($isPaid) {
            $payment = $order->payments()->where('status', 'pending')->first();
            if ($payment) {
                $payment->update(['status' => 'paid', 'paid_at' => now()]);
            }

            $isDpPaid = $order->payment_type === 'down_payment' && $order->payments()->where('status', 'paid')->count() === 1;

            $order->update(['status' => $isDpPaid ? 'dp_paid' : 'paid']);

            if (! $isDpPaid) {
                $this->ticketService->generateForOrder($order);
            }
        } elseif (in_array($transStatus, ['cancel', 'expire', 'deny'])) {
            $order->update(['status' => $transStatus === 'expire' ? 'expired' : 'cancelled']);
        }

        Log::info('Midtrans webhook', ['booking' => $bookingCode, 'status' => $transStatus]);

        return response()->json(['message' => 'OK']);
    }

    public function verifyTicket(string $qrCode): JsonResponse
    {
        try {
            $result = $this->ticketService->verifyQrCode($qrCode);
            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
