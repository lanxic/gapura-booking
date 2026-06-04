<?php

namespace App\Http\Controllers\Api;

use App\Enums\CorrectionStatus;
use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CorrectionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'target_type'      => 'required|in:ticket,payment,order',
            'target_id'        => 'required|string',
            'reason'           => 'required|string|max:1000',
            'requested_value'  => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $correction = CorrectionRequest::create([
            'requested_by'    => auth()->id(),
            'target_type'     => $request->target_type,
            'target_id'       => $request->target_id,
            'reason'          => $request->reason,
            'old_value'       => [],
            'requested_value' => $request->requested_value,
            'status'          => CorrectionStatus::Pending,
        ]);

        return response()->json(['data' => $correction], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $corrections = CorrectionRequest::where('requested_by', auth()->id())
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $corrections->items(),
            'meta' => [
                'currentPage' => $corrections->currentPage(),
                'lastPage'    => $corrections->lastPage(),
                'perPage'     => $corrections->perPage(),
                'total'       => $corrections->total(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $corrections = CorrectionRequest::with(['requester'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $corrections->items(),
            'meta' => [
                'currentPage' => $corrections->currentPage(),
                'lastPage'    => $corrections->lastPage(),
                'perPage'     => $corrections->perPage(),
                'total'       => $corrections->total(),
            ],
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $correction = CorrectionRequest::findOrFail($id);

        if ($correction->status !== CorrectionStatus::Pending) {
            return response()->json(['message' => 'Koreksi sudah diproses.'], 422);
        }

        $correction->update([
            'status'       => CorrectionStatus::Approved,
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return response()->json(['data' => $correction->fresh()]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $correction = CorrectionRequest::findOrFail($id);

        if ($correction->status !== CorrectionStatus::Pending) {
            return response()->json(['message' => 'Koreksi sudah diproses.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'review_notes' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $correction->update([
            'status'       => CorrectionStatus::Rejected,
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return response()->json(['data' => $correction->fresh()]);
    }
}
