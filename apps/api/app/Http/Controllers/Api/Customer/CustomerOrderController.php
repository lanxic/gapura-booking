<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['items.product', 'payments'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

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

    public function show(string $bookingCode): JsonResponse
    {
        $order = Order::with(['items.product', 'items.variant', 'payments', 'vouchers', 'tickets'])
            ->where('booking_code', $bookingCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json(['data' => $order]);
    }

    public function reschedule(Request $request, string $bookingCode): JsonResponse
    {
        $order = Order::where('booking_code', $bookingCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (! in_array($order->status->value, ['paid', 'confirmed'])) {
            return response()->json(['message' => 'Pesanan tidak dapat dijadwal ulang.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'items'                        => 'required|array|min:1',
            'items.*.order_item_id'        => 'required|integer',
            'items.*.slot_id' => 'required|integer|exists:availability_slots,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        foreach ($request->items as $item) {
            $orderItem = $order->items()->find($item['order_item_id']);
            if ($orderItem) {
                $slot = AvailabilitySlot::find($item['slot_id']);
                if ($slot && ! $slot->is_blocked && $slot->booked_qty < $slot->total_quota) {
                    $orderItem->update(['slot_id' => $item['slot_id']]);
                }
            }
        }

        return response()->json(['data' => $order->fresh(['items.product', 'items.variant'])]);
    }
}
