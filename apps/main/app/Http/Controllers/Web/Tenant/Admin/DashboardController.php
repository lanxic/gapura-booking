<?php

namespace App\Http\Controllers\Web\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

class DashboardController extends Controller
{
    public function index(string $tenantSlug)
    {
        $tenant = App::make('current_tenant');

        $stats = [
            'total_bookings' => Booking::where('tenant_id', $tenant->id)->count(),
            'total_revenue'  => Invoice::where('tenant_id', $tenant->id)
                ->where('status', 'paid')->sum('total_amount'),
            'total_products' => Product::where('tenant_id', $tenant->id)->active()->count(),
        ];

        $revenueChart = collect(range(6, 0))->map(function ($daysAgo) use ($tenant) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'   => $date->format('d M'),
                'amount' => Invoice::where('tenant_id', $tenant->id)
                    ->where('status', 'paid')
                    ->whereDate('updated_at', $date)
                    ->sum('total_amount'),
            ];
        });

        $recentBookings = Booking::with('slot.product')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->take(5)
            ->get();

        return view('tenant.admin.dashboard', compact('stats', 'revenueChart', 'recentBookings', 'tenant'));
    }
}
