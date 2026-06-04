<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupervisorPaymentController extends Controller
{
    public function void(Request $request, int $id): JsonResponse
    {
        $payment = Payment::with('order')->findOrFail($id);

        if ($payment->status->value !== 'success') {
            return response()->json(['message' => 'Hanya pembayaran berstatus success yang bisa di-void.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $payment->update(['status' => 'refunded']);

        ActivityLog::create([
            'user_id'      => auth()->id(),
            'role'         => auth()->user()->role->value,
            'action'       => 'payment.void',
            'subject_type' => 'payment',
            'subject_id'   => $payment->id,
            'old_value'    => ['status' => 'success'],
            'new_value'    => ['status' => 'refunded', 'reason' => $request->reason],
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);

        return response()->json(['data' => $payment->fresh()]);
    }
}
