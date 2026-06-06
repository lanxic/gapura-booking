<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['items.variant.product', 'payments'])
            ->when($request->status,  fn($q, $s) => $q->where('status', $s))
            ->when($request->search,  fn($q, $s) => $q->where('booking_code', 'like', "%$s%")
                ->orWhere('customer_name', 'like', "%$s%")
                ->orWhere('customer_email', 'like', "%$s%"))
            ->when($request->from, fn($q, $f) => $q->whereDate('created_at', '>=', $f))
            ->when($request->to,   fn($q, $t) => $q->whereDate('created_at', '<=', $t))
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'currentPage' => $orders->currentPage(),
                'lastPage'    => $orders->lastPage(),
                'perPage'     => $orders->perPage(),
                'total'       => $orders->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with(['items.variant.product', 'items.availabilitySlot', 'items.tickets', 'payments', 'vouchers'])
            ->findOrFail($id);

        return response()->json(['data' => $order]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $order->update($request->only(['notes', 'customer_name', 'customer_email', 'customer_phone']));

        return response()->json(['data' => $order->fresh()]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,awaiting_payment,dp_paid,paid,confirmed,cancelled,refunded,expired',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $order->update(['status' => $request->status]);

        return response()->json(['data' => $order->fresh()]);
    }

    public function refund(Request $request, int $id): JsonResponse
    {
        $order = Order::with('payments')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $refund = Payment::create([
            'order_id'   => $order->id,
            'gateway'      => 'cash',
            'payment_type' => 'remaining',
            'amount'       => -abs($request->amount),
            'status'       => 'refunded',
            'paid_at'      => now(),
            'collected_by' => auth()->id(),
        ]);

        $order->update(['status' => 'refunded']);

        return response()->json(['data' => $refund]);
    }
}
