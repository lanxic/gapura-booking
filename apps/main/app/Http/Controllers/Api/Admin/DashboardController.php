<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\Booking;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $today     = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $bookingsToday = Booking::whereDate('created_at', $today)->count();
        $bookingsMonth = Booking::where('created_at', '>=', $thisMonth)->count();

        $revenueToday = Invoice::where('status', 'paid')
            ->whereDate('paid_at', $today)
            ->sum('total_amount');

        $revenueMonth = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', $thisMonth)
            ->sum('total_amount');

        $pendingInvoices = Invoice::where('status', 'pending')->count();

        $slotsToday = ActivitySlot::whereDate('date', $today)
            ->where('status', 'available')
            ->withCount('bookings')
            ->get()
            ->map(fn($s) => [
                'slot_id'          => $s->id,
                'activity_name'    => $s->activity->name ?? '',
                'start_time'       => $s->start_time,
                'capacity'         => $s->capacity,
                'booked'           => $s->bookings_count,
            ]);

        // Recent bookings (last 5)
        $recentBookings = Booking::with(['invoice', 'slot.activity'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($b) => [
                'booking_code'   => $b->booking_code,
                'guest_name'     => $b->guest_name,
                'activity_name'  => $b->slot?->activity?->name ?? '',
                'slot_date'      => $b->slot?->date,
                'slot_time'      => $b->slot?->start_time,
                'pax'            => $b->pax,
                'status'         => $b->status,
                'total_amount'   => $b->invoice?->total_amount,
                'created_at'     => $b->created_at,
            ]);

        // Revenue chart: last 7 days
        $revenueChart = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'    => $date->toDateString(),
                'revenue' => Invoice::where('status', 'paid')
                    ->whereDate('paid_at', $date)
                    ->sum('total_amount'),
                'bookings' => Booking::whereDate('created_at', $date)->count(),
            ];
        });

        return response()->json([
            'data' => [
                'summary' => [
                    'bookings_today'    => $bookingsToday,
                    'bookings_month'    => $bookingsMonth,
                    'revenue_today'     => (int) $revenueToday,
                    'revenue_month'     => (int) $revenueMonth,
                    'pending_invoices'  => $pendingInvoices,
                    'active_activities' => Activity::where('status', 'active')->count(),
                ],
                'slots_today'     => $slotsToday,
                'recent_bookings' => $recentBookings,
                'revenue_chart'   => $revenueChart,
            ],
        ]);
    }
}
