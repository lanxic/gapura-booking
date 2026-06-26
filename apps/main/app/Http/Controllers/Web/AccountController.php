<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function __construct(private readonly QrCodeService $qrCodeService) {}

    public function bookings()
    {
        $user     = Auth::guard('web')->user();
        $bookings = Booking::where('customer_id', $user->id)
            ->with('slot.activity')
            ->latest()
            ->paginate(10);

        return view('account.bookings', compact('bookings'));
    }

    public function bookingDetail(string $code)
    {
        $user    = Auth::guard('web')->user();
        $booking = Booking::where('booking_code', $code)
            ->where('customer_id', $user->id)
            ->with('slot.activity')
            ->firstOrFail();

        $qrSvg = $this->qrCodeService->generate($booking->booking_code);

        return view('account.booking-detail', compact('booking', 'qrSvg'));
    }
}
