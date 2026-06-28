<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user')
            ->when($request->role,    fn($q, $r) => $q->where('role', $r))
            ->when($request->action,  fn($q, $a) => $q->where('action', 'like', "%$a%"))
            ->when($request->user_id, fn($q, $u) => $q->where('user_id', $u))
            ->when($request->from,    fn($q, $f) => $q->whereDate('created_at', '>=', $f))
            ->when($request->to,      fn($q, $t) => $q->whereDate('created_at', '<=', $t))
            ->latest()
            ->paginate(50);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'currentPage' => $logs->currentPage(),
                'lastPage'    => $logs->lastPage(),
                'perPage'     => $logs->perPage(),
                'total'       => $logs->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => ActivityLog::with('user')->findOrFail($id)]);
    }

    public function export(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Ekspor log aktivitas belum diimplementasikan.',
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to', now()->toDateString());

        $summary = ActivityLog::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw('action, role, COUNT(*) as count')
            ->groupBy('action', 'role')
            ->orderByDesc('count')
            ->get();

        return response()->json(['data' => $summary]);
    }
}
