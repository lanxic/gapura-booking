<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupervisorTicketController extends Controller
{
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:unused,used,expired,cancelled',
            'notes'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $ticket->update(['status' => $request->status]);

        return response()->json(['data' => $ticket->fresh()]);
    }
}
