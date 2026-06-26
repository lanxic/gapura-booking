<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivitySlot;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffSlotController extends Controller
{
    /**
     * GET /api/v1/staff/slots?date=today
     * Daftar slot aktivitas hari ini untuk instruktur yang login (PRD Section 4.7.1).
     */
    public function index(Request $request): JsonResponse
    {
        $date = $request->query('date') === 'today'
            ? now()->toDateString()
            : $request->query('date', now()->toDateString());

        $slots = ActivitySlot::with('activity')
            ->where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($slot) => [
                'id'            => $slot->id,
                'activity_name' => $slot->activity->name,
                'start_time'    => $slot->start_time,
                'end_time'      => $slot->end_time,
                'capacity'      => $slot->capacity,
                'booked_count'  => $slot->booked_count,
                'status'        => $slot->status,
            ]);

        return response()->json(['data' => $slots]);
    }

    /**
     * GET /api/v1/staff/slots/{slotId}/checkins
     * Riwayat check-in per slot (PRD Section 4.7.1 - Riwayat scan).
     */
    public function checkins(int $slotId): JsonResponse
    {
        $checkins = Booking::with('slot.activity')
            ->where('slot_id', $slotId)
            ->where('status', 'attended')
            ->orderByDesc('confirmed_at')
            ->get()
            ->map(fn ($b) => [
                'booking_code'  => $b->booking_code,
                'guest_name'    => $b->guest_name,
                'pax_count'     => $b->pax_count,
                'checked_in_at' => $b->confirmed_at?->format('H:i'),
            ]);

        return response()->json(['data' => $checkins]);
    }
}
