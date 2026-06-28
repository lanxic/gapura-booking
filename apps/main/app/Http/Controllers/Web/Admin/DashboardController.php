<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_bookings'  => Booking::count(),
            'total_revenue'   => Invoice::where('status', 'paid')->sum('total_amount'),
            'total_customers' => User::where('role', 'customer')->count(),
            'total_products'  => Product::active()->count(),
            'total_tenants'   => Tenant::active()->count(),
        ];

        $revenueChart = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'   => $date->format('d M'),
                'amount' => Invoice::where('status', 'paid')
                    ->whereDate('updated_at', $date)
                    ->sum('total_amount'),
            ];
        });

        $recentBookings = Booking::with('slot.product', 'slot.product.tenant')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'revenueChart', 'recentBookings'));
    }
}
