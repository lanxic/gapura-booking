<?php

namespace App\Http\Controllers\Api\Scanner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScannerController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function scan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $result = $this->ticketService->verifyQrCode($request->qr_code);

            ActivityLog::create([
                'user_id'      => auth()->id(),
                'role'         => auth()->user()->role->value,
                'action'       => 'ticket.scan',
                'subject_type' => 'ticket',
                'subject_id'   => $result['ticket']['id'] ?? null,
                'new_value'    => $result,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
            ]);

            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => 'MERAH'], 422);
        }
    }

    public function preview(string $qrCode): JsonResponse
    {
        $ticket = Ticket::with(['orderItem.product', 'orderItem.variant', 'orderItem.order'])
            ->where('qr_code', $qrCode)
            ->firstOrFail();

        return response()->json(['data' => $ticket]);
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = ActivityLog::where('user_id', auth()->id())
            ->where('action', 'like', 'ticket.%')
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
}
