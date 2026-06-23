<?php

namespace App\Http\Controllers\Api\Kasir;

use App\Http\Controllers\Controller;
use App\Jobs\SendBookingPaidEmail;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KasirOrderController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function show(string $bookingCode): JsonResponse
    {
        $order = Order::with(['items.product', 'items.variant', 'payments', 'vouchers'])
            ->where('booking_code', $bookingCode)
            ->firstOrFail();

        return response()->json(['data' => $order]);
    }

    public function paymentSummary(string $bookingCode): JsonResponse
    {
        $order = Order::with('payments')
            ->where('booking_code', $bookingCode)
            ->firstOrFail();

        $paid      = $order->payments->where('status', 'success')->sum('amount');
        $remaining = $order->total - $paid;

        return response()->json([
            'data' => [
                'booking_code'     => $order->booking_code,
                'total'            => $order->total,
                'paid'             => $paid,
                'remaining'        => $remaining,
                'payment_type'     => $order->payment_type,
                'status'           => $order->status,
            ],
        ]);
    }

    public function collect(Request $request, string $bookingCode): JsonResponse
    {
        $order = Order::with('payments')
            ->where('booking_code', $bookingCode)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'amount'  => 'required|integer|min:1',
            'gateway' => 'required|in:cash,midtrans,doku',
            'notes'   => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $paid      = $order->payments->where('status', 'success')->sum('amount');
        $remaining = $order->total - $paid;

        if ($request->amount > $remaining) {
            return response()->json(['message' => 'Jumlah melebihi sisa tagihan.'], 422);
        }

        $payment = Payment::create([
            'order_id'     => $order->id,
            'gateway'      => $request->gateway,
            'payment_type' => 'remaining',
            'amount'       => $request->amount,
            'status'       => 'success',
            'paid_at'      => now(),
            'collected_by' => auth()->id(),
        ]);

        $newPaid = $paid + $request->amount;

        $isFullyPaid = $newPaid >= $order->total;
        $isDpFirst   = $order->payment_type === 'down_payment' && $order->status->value === 'pending';

        if ($isFullyPaid) {
            $order->update(['status' => 'paid']);
            $this->ticketService->generateForOrder($order);
        } elseif ($isDpFirst) {
            $order->update(['status' => 'dp_paid']);
        }

        try {
            SendBookingPaidEmail::dispatch($order->fresh(), $payment);
        } catch (\Throwable) {
            // Email failure must not affect payment processing
        }

        ActivityLog::create([
            'user_id'      => auth()->id(),
            'role'         => auth()->user()->role->value,
            'action'       => 'payment.collect',
            'subject_type' => 'order',
            'subject_id'   => $order->id,
            'new_value'    => ['amount' => $request->amount, 'booking_code' => $bookingCode],
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);

        return response()->json(['data' => $payment]);
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = ActivityLog::where('user_id', auth()->id())
            ->where('action', 'like', 'payment.%')
            ->latest()
            ->paginate(50);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'currentPage' => $logs->currentPage(),
                'lastPage'    => $logs->lastPage(),
                'perPage'     => $logs->perPage(),
                'total'       => $logs->total(),
            ],
        ]);
    }
}
