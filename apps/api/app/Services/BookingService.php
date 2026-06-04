<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\AvailabilitySlot;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(private TicketService $ticketService) {}

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $slot = AvailabilitySlot::lockForUpdate()->findOrFail($data['slot_id']);

            $totalQty  = ($data['qty_adult'] ?? 0) + ($data['qty_child'] ?? 0);
            $available = $slot->total_quota - $slot->booked_qty;

            if ($slot->is_blocked || $available < $totalQty) {
                throw new \RuntimeException('Slot tidak tersedia atau kuota habis.');
            }

            $subtotal        = $this->calculateSubtotal($data);
            $discount        = 0;
            $total           = $subtotal;
            $dpAmount        = null;
            $remainingAmount = 0;

            if ($data['payment_type'] === 'down_payment') {
                $dpAmount        = (int) ceil($total * ($data['dp_percent'] / 100));
                $remainingAmount = $total - $dpAmount;
            }

            $order = Order::create([
                'booking_code'     => self::generateBookingCode(),
                'user_id'          => $data['user_id'] ?? null,
                'customer_name'    => $data['customer_name'],
                'customer_email'   => $data['customer_email'],
                'customer_phone'   => $data['customer_phone'],
                'payment_type'     => $data['payment_type'],
                'dp_percent'       => $data['dp_percent'] ?? null,
                'dp_amount'        => $dpAmount,
                'remaining_amount' => $remainingAmount,
                'status'           => OrderStatus::Pending,
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'total'            => $total,
                'expires_at'       => now()->addSeconds(config('app.seat_hold_ttl', 600)),
            ]);

            OrderItem::create([
                'order_id'         => $order->id,
                'variant_id'       => $data['variant_id'],
                'slot_id'          => $slot->id,
                'qty_adult'        => $data['qty_adult'] ?? 0,
                'qty_child'        => $data['qty_child'] ?? 0,
                'unit_price_adult' => $data['unit_price_adult'],
                'unit_price_child' => $data['unit_price_child'] ?? 0,
                'subtotal'         => $subtotal,
            ]);

            $slot->increment('booked_qty', $totalQty);

            $ttl = config('app.seat_hold_ttl', 600);
            Redis::setex("seat_hold:{$order->id}", $ttl, $order->id);

            return $order;
        });
    }

    /**
     * Hasilkan invoice number baru untuk satu transaksi Midtrans.
     * Format: INV-YYYYMMDD-XXXXXXXX
     * Unik per payment, dikirim ke Midtrans sebagai order_id.
     */
    public static function generateInvoiceNumber(): string
    {
        do {
            $candidate = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
        } while (DB::table('payments')->where('invoice_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Hasilkan booking code customer-facing.
     * Format: AMT-XXXXXXXX (8 alphanum, huruf kapital)
     * Digunakan customer untuk tracking pesanan, TIDAK dikirim ke Midtrans.
     */
    public static function generateBookingCode(): string
    {
        do {
            $candidate = 'AMT-' . strtoupper(Str::random(8));
        } while (DB::table('orders')->where('booking_code', $candidate)->exists());

        return $candidate;
    }

    private function calculateSubtotal(array $data): int
    {
        $adultTotal = ($data['qty_adult'] ?? 0) * ($data['unit_price_adult'] ?? 0);
        $childTotal = ($data['qty_child'] ?? 0) * ($data['unit_price_child'] ?? 0);
        $addonTotal = collect($data['addons'] ?? [])
            ->sum(fn($a) => $a['qty'] * $a['unit_price']);

        return $adultTotal + $childTotal + $addonTotal;
    }
}
