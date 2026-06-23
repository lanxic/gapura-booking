<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Jobs\SendBookingConfirmationEmail;
use App\Models\AvailabilitySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(private TicketService $ticketService) {}

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $subtotal       = 0;
            $processedItems = [];

            foreach ($data['items'] as $item) {
                $variant = ProductVariant::findOrFail($item['variant_id']);

                // Use item-level slot_id if provided and numeric, otherwise find all-day slot
                $slotId = $item['slot_id'] ?? null;
                if ($slotId && is_numeric($slotId)) {
                    $slot = AvailabilitySlot::lockForUpdate()->find($slotId);
                    if (! $slot) {
                        throw new \RuntimeException('Slot tidak ditemukan.');
                    }
                } else {
                    // Prefer all-day slot (time_slot IS NULL); if none, use first time-specific slot
                    $slot = AvailabilitySlot::lockForUpdate()
                        ->where('product_id', $variant->product_id)
                        ->whereDate('date', $data['date'])
                        ->orderByRaw('(time_slot IS NULL) DESC')
                        ->first();
                    if (! $slot) {
                        throw new \RuntimeException('Tidak ada slot tersedia untuk tanggal yang dipilih.');
                    }
                }

                $qtyAdult = (int) ($item['qty_adult'] ?? 0);
                $qtyChild = (int) ($item['qty_child'] ?? 0);
                $totalQty = $qtyAdult + $qtyChild;
                $available = $slot->total_quota - $slot->booked_qty;

                if ($slot->is_blocked || $available < $totalQty) {
                    throw new \RuntimeException('Slot tidak tersedia atau kuota habis.');
                }

                $itemSubtotal = ($qtyAdult * $variant->price_adult) + ($qtyChild * $variant->price_child);
                $subtotal    += $itemSubtotal;

                $processedItems[] = [
                    'variant'   => $variant,
                    'slot'      => $slot,
                    'qty_adult' => $qtyAdult,
                    'qty_child' => $qtyChild,
                    'subtotal'  => $itemSubtotal,
                ];
            }

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

            foreach ($processedItems as $pi) {
                OrderItem::create([
                    'order_id'         => $order->id,
                    'variant_id'       => $pi['variant']->id,
                    'slot_id'          => $pi['slot']->id,
                    'qty_adult'        => $pi['qty_adult'],
                    'qty_child'        => $pi['qty_child'],
                    'unit_price_adult' => $pi['variant']->price_adult,
                    'unit_price_child' => $pi['variant']->price_child,
                    'subtotal'         => $pi['subtotal'],
                ]);

                $pi['slot']->increment('booked_qty', $pi['qty_adult'] + $pi['qty_child']);
            }

            $ttl = config('app.seat_hold_ttl', 600);
            Redis::setex("seat_hold:{$order->id}", $ttl, $order->id);

            try {
                SendBookingConfirmationEmail::dispatch($order);
            } catch (\Throwable) {
                // Email failure must not abort the booking
            }

            return $order;
        });
    }

    public static function generateInvoiceNumber(): string
    {
        do {
            $candidate = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
        } while (DB::table('payments')->where('invoice_number', $candidate)->exists());

        return $candidate;
    }

    public static function generateBookingCode(): string
    {
        do {
            $candidate = 'AMT-' . strtoupper(Str::random(8));
        } while (DB::table('orders')->where('booking_code', $candidate)->exists());

        return $candidate;
    }
}
