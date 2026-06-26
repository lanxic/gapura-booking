<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffScanController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    /**
     * POST /api/v1/staff/bookings/validate
     * Validasi QR code tamu & mark as attended (PRD Section 4.7.2).
     *
     * Body: { qr_token: string, slot_id: int }
     * Response:
     *   { status: 'valid', guest_name, activity_name, pax_count, slot_time }
     *   { status: 'already_scanned', checked_in_at }
     *   { status: 'invalid' }
     */
    public function validate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qr_token' => 'required|string',
            'slot_id'  => 'required|integer|exists:activity_slots,id',
        ]);

        $result = $this->bookingService->validateQr($data['qr_token'], (int) $data['slot_id']);

        return response()->json($result);
    }
}
