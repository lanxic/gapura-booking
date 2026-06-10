<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Voucher;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_name'          => 'required|string|max:255',
            'customer_email'         => 'required|email',
            'customer_phone'         => 'required|string|max:20',
            'payment_type'           => 'required|in:full,down_payment',
            'dp_percent'             => 'nullable|integer|in:30,50,70',
            'notes'                  => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|integer|exists:products,id',
            'items.*.variant_id'     => 'required|integer|exists:product_variants,id',
            'items.*.slot_id' => 'required|integer|exists:availability_slots,id',
            'items.*.qty_adult'      => 'required|integer|min:0',
            'items.*.qty_child'      => 'required|integer|min:0',
            'items.*.addons'         => 'nullable|array',
            'items.*.addons.*.addon_id' => 'required|integer|exists:addons,id',
            'items.*.addons.*.qty'   => 'required|integer|min:1',
            'voucher_code'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $order = $this->bookingService->createOrder($request->all());
            return response()->json(['data' => $order->load(['items', 'payments', 'vouchers'])], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function applyVoucher(Request $request, string $bookingCode): JsonResponse
    {
        $order = Order::where('booking_code', $bookingCode)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'voucher_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $voucher = Voucher::where('code', $request->voucher_code)
            ->where('is_active', true)
            ->first();

        if (! $voucher) {
            return response()->json(['message' => 'Voucher tidak ditemukan atau tidak aktif.'], 404);
        }

        if ($order->status->value !== 'pending') {
            return response()->json(['message' => 'Voucher hanya bisa diterapkan pada pesanan pending.'], 422);
        }

        $discount = min($voucher->discount_amount ?? ($order->subtotal * $voucher->discount_percent / 100), $voucher->max_discount ?? PHP_INT_MAX);

        $order->vouchers()->syncWithoutDetaching([
            $voucher->id => ['discount_amount' => $discount, 'applied_at' => now()],
        ]);
        $order->update(['discount' => $order->discount + $discount, 'total' => $order->total - $discount]);

        return response()->json(['data' => $order->fresh(['items', 'payments', 'vouchers'])]);
    }

    public function show(string $bookingCode): JsonResponse
    {
        $order = Order::with(['items.product', 'items.variant', 'payments', 'vouchers'])
            ->where('booking_code', $bookingCode)
            ->firstOrFail();

        return response()->json(['data' => $order]);
    }
}
