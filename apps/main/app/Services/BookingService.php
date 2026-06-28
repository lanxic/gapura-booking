<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class BookingService
{
    public function __construct(private readonly QrCodeService $qrCodeService) {}

    /**
     * Buat Booking setelah invoice PAID (PRD Section 13.3 Step 6).
     * booking_code = ACT-YYYYMMDD-XXXXX via Redis atomic INCR.
     */
    public function createFromInvoice(Invoice $invoice): Booking
    {
        // Idempotency — jangan buat booking duplikat
        if ($invoice->booking) {
            return $invoice->booking;
        }

        $bookingCode = $this->generateBookingCode();
        $qrToken     = $this->qrCodeService->generateToken();

        return DB::transaction(function () use ($invoice, $bookingCode, $qrToken) {
            $qrCodePath = $this->qrCodeService->generateAndUpload($qrToken, $bookingCode);

            $booking = Booking::create([
                'booking_code'   => $bookingCode,
                'invoice_id'     => $invoice->id,
                'slot_id'        => $invoice->checkout_slot_id,
                'customer_id'    => $invoice->customer_id,
                'guest_name'     => $invoice->guest_name,
                'guest_email'    => $invoice->guest_email,
                'guest_phone'    => $invoice->guest_phone,
                'pax_count'      => $invoice->pax_count,
                'status'         => 'confirmed',
                'total_amount'   => $invoice->total_amount,
                'paid_amount'    => $invoice->due_now,
                'payment_status' => $invoice->due_later > 0 ? 'partial' : 'paid',
                'qr_code_token'  => $qrToken,
                'qr_code_path'   => $qrCodePath,
                'confirmed_at'   => now(),
            ]);

            // Buat booking_addons dari items invoice yang bertipe addon
            foreach ($invoice->items ?? [] as $item) {
                if (($item['type'] ?? '') === 'addon' && isset($item['addon_id'])) {
                    BookingAddon::create([
                        'booking_id' => $booking->id,
                        'addon_id'   => $item['addon_id'],
                        'quantity'   => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal'   => $item['subtotal'],
                    ]);
                }
            }

            // Update slot inventory
            $slot = $booking->slot;
            $slot->increment('booked_count', $booking->pax_count);
            if ($slot->booked_count >= $slot->capacity) {
                $slot->update(['status' => 'full']);
            }

            // Hapus slot lock Redis
            Redis::del("slot_lock:{$invoice->checkout_slot_id}");

            return $booking;
        });
    }

    /**
     * Check-in tamu via QR scan (PRD Section 4.7.2).
     */
    public function validateQr(string $qrToken, int $slotId): array
    {
        $booking = Booking::where('qr_code_token', $qrToken)->first();

        if (! $booking) {
            return ['status' => 'invalid'];
        }

        if ($booking->slot_id !== $slotId) {
            return ['status' => 'invalid']; // SALAH SLOT
        }

        if ($booking->status === 'attended') {
            return [
                'status'        => 'already_scanned',
                'checked_in_at' => $booking->confirmed_at?->format('H:i'),
            ];
        }

        if ($booking->status !== 'confirmed') {
            return ['status' => 'invalid'];
        }

        $booking->update([
            'status'       => 'attended',
            'confirmed_at' => now(),
        ]);

        $slot = $booking->slot;

        return [
            'status'        => 'valid',
            'guest_name'    => $booking->guest_name,
            'activity_name' => $slot->activity->name,
            'pax_count'     => $booking->pax_count,
            'slot_time'     => $slot->start_time . '–' . $slot->end_time,
        ];
    }

    private function generateBookingCode(): string
    {
        $date = now()->format('Ymd');
        $seq  = Redis::incr("act_seq:{$date}");
        Redis::expire("act_seq:{$date}", 86400);

        return sprintf('ACT-%s-%05d', $date, $seq);
    }
}
