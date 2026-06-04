<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user')
            ->when($request->role, fn($q, $r) => $q->where('role', $r))
            ->when($request->action, fn($q, $a) => $q->where('action', 'like', "%$a%"))
            ->when($request->date, fn($q, $d) => $q->whereDate('created_at', $d))
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
        $log = ActivityLog::with('user')->findOrFail($id);
        return response()->json(['data' => $log]);
    }
}
