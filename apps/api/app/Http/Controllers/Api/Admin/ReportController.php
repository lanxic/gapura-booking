<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to', now()->toDateString());

        $orders = Order::with('payments')
            ->whereIn('status', ['paid', 'confirmed'])
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->get();

        $totalRevenue = $orders->sum('total');
        $totalOrders  = $orders->count();

        $daily = $orders->groupBy(fn($o) => $o->created_at->toDateString())
            ->map(fn($group) => [
                'date'    => $group->first()->created_at->toDateString(),
                'count'   => $group->count(),
                'revenue' => $group->sum('total'),
            ])
            ->values();

        return response()->json([
            'data' => [
                'from'           => $from,
                'to'             => $to,
                'total_orders'   => $totalOrders,
                'total_revenue'  => $totalRevenue,
                'daily'          => $daily,
            ],
        ]);
    }

    public function outstanding(Request $request): JsonResponse
    {
        $orders = Order::with('payments')
            ->where('payment_type', 'down_payment')
            ->where('status', 'dp_paid')
            ->get()
            ->map(function ($order) {
                $paid      = $order->payments->where('status', 'paid')->sum('amount');
                $remaining = $order->total - $paid;
                return [
                    'booking_code' => $order->booking_code,
                    'customer'     => $order->customer_name,
                    'total'        => $order->total,
                    'paid'         => $paid,
                    'remaining'    => $remaining,
                    'created_at'   => $order->created_at,
                ];
            })
            ->filter(fn($o) => $o['remaining'] > 0)
            ->values();

        return response()->json(['data' => $orders]);
    }

    public function export(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Laporan ekspor belum diimplementasikan. Gunakan endpoint sales untuk data.',
        ]);
    }
}
