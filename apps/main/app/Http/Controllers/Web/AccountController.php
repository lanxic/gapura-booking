<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function __construct(private readonly QrCodeService $qrCodeService) {}

    public function bookings(string $tenantSlug)
    {
        $user     = Auth::guard('web')->user();
        $bookings = Booking::where('customer_id', $user->id)
            ->with('slot.product')
            ->latest()
            ->paginate(10);

        return view('tenant.storefront.account.bookings', compact('bookings'));
    }

    public function bookingDetail(string $tenantSlug, string $code)
    {
        $user    = Auth::guard('web')->user();
        $booking = Booking::where('booking_code', $code)
            ->where('customer_id', $user->id)
            ->with('slot.product', 'addons', 'invoice')
            ->firstOrFail();

        $qrSvg = $this->qrCodeService->generate($booking->booking_code);

        return view('tenant.storefront.account.booking-detail', compact('booking', 'qrSvg'));
    }
}
