<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Enums\CorrectionStatus;
use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupervisorCorrectionController extends Controller
{
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

    public function show(int $id): JsonResponse
    {
        $correction = CorrectionRequest::with(['requester', 'reviewer'])->findOrFail($id);
        return response()->json(['data' => $correction]);
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
