<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class SupervisorDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'orders_today'        => Order::whereDate('created_at', today())->count(),
                'orders_pending'      => Order::where('status', 'pending')->count(),
                'tickets_used_today'  => Ticket::whereDate('used_at', today())->count(),
                'corrections_pending' => CorrectionRequest::where('status', 'pending')->count(),
            ],
        ]);
    }
}
