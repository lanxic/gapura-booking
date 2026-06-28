<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductSlot;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $today     = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $slotsToday = ProductSlot::whereDate('date', $today)
            ->where('status', 'available')
            ->withCount('bookings')
            ->get()
            ->map(fn($s) => [
                'slot_id'      => $s->id,
                'product_name' => $s->product->name ?? '',
                'start_time'   => $s->start_time,
                'capacity'     => $s->capacity,
                'booked'       => $s->bookings_count,
            ]);

        $recentBookings = Booking::with(['invoice', 'slot.product'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($b) => [
                'booking_code'  => $b->booking_code,
                'guest_name'    => $b->guest_name,
                'product_name'  => $b->slot?->product?->name ?? '',
                'slot_date'     => $b->slot?->date,
                'slot_time'     => $b->slot?->start_time,
                'pax'           => $b->pax_count,
                'status'        => $b->status,
                'total_amount'  => $b->invoice?->total_amount,
                'created_at'    => $b->created_at,
            ]);

        $revenueChart = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'     => $date->toDateString(),
                'revenue'  => Invoice::where('status', 'paid')->whereDate('paid_at', $date)->sum('total_amount'),
                'bookings' => Booking::whereDate('created_at', $date)->count(),
            ];
        });

        return response()->json([
            'data' => [
                'summary' => [
                    'bookings_today'   => Booking::whereDate('created_at', $today)->count(),
                    'bookings_month'   => Booking::where('created_at', '>=', $thisMonth)->count(),
                    'revenue_today'    => (int) Invoice::where('status', 'paid')->whereDate('paid_at', $today)->sum('total_amount'),
                    'revenue_month'    => (int) Invoice::where('status', 'paid')->where('paid_at', '>=', $thisMonth)->sum('total_amount'),
                    'pending_invoices' => Invoice::where('status', 'pending')->count(),
                    'active_products'  => Product::where('status', 'active')->count(),
                ],
                'slots_today'     => $slotsToday,
                'recent_bookings' => $recentBookings,
                'revenue_chart'   => $revenueChart,
            ],
        ]);
    }
}
