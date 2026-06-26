<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerBookingController extends Controller
{
    public function __construct(private readonly QrCodeService $qrService) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $bookings = Booking::with(['slot.activity', 'invoice', 'addons.activityAddon'])
            ->whereHas('invoice', fn($q) => $q->where('customer_email', $user->email))
            ->latest()
            ->paginate(10);

        return response()->json($bookings);
    }

    public function show(string $code, Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $booking = Booking::with(['slot.activity.media', 'invoice', 'addons.activityAddon', 'participants'])
            ->where('booking_code', $code)
            ->whereHas('invoice', fn($q) => $q->where('customer_email', $user->email))
            ->firstOrFail();

        return response()->json(['data' => $this->formatBooking($booking)]);
    }

    public function qr(string $code, Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $booking = Booking::where('booking_code', $code)
            ->whereHas('invoice', fn($q) => $q->where('customer_email', $user->email))
            ->firstOrFail();

        if (! in_array($booking->status, ['confirmed', 'attended'])) {
            return response()->json(['message' => 'Booking belum dikonfirmasi.'], 422);
        }

        $qrSvg = $this->qrService->generateSvg($booking->booking_code);

        return response()->json([
            'data' => [
                'booking_code' => $booking->booking_code,
                'qr_svg'       => $qrSvg,
                'status'       => $booking->status,
            ],
        ]);
    }

    private function formatBooking(Booking $booking): array
    {
        $slot     = $booking->slot;
        $activity = $slot?->activity;

        return [
            'booking_code'     => $booking->booking_code,
            'status'           => $booking->status,
            'pax'              => $booking->pax,
            'guest_name'       => $booking->guest_name,
            'guest_email'      => $booking->guest_email,
            'guest_phone'      => $booking->guest_phone,
            'notes'            => $booking->notes,
            'created_at'       => $booking->created_at,
            'activity'         => $activity ? [
                'id'       => $activity->id,
                'name'     => $activity->name,
                'slug'     => $activity->slug,
                'category' => $activity->category,
                'image'    => $activity->media->where('is_primary', true)->first()?->url
                           ?? $activity->media->first()?->url,
            ] : null,
            'slot'             => $slot ? [
                'date'       => $slot->date,
                'start_time' => $slot->start_time,
                'end_time'   => $slot->end_time,
            ] : null,
            'invoice'          => $booking->invoice ? [
                'invoice_code'  => $booking->invoice->invoice_code,
                'total_amount'  => $booking->invoice->total_amount,
                'payment_plan'  => $booking->invoice->payment_plan,
                'paid_at'       => $booking->invoice->paid_at,
            ] : null,
            'addons'           => $booking->addons->map(fn($a) => [
                'name'  => $a->activityAddon?->name,
                'qty'   => $a->qty,
                'price' => $a->unit_price,
            ]),
        ];
    }
}
