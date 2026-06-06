<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvailabilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 50), 500);

        $slots = AvailabilitySlot::with('product')
            ->when($request->product_id, fn($q, $id) => $q->where('product_id', $id))
            ->when($request->date,       fn($q, $d)  => $q->whereDate('date', $d))
            ->when($request->from,       fn($q, $f)  => $q->where('date', '>=', $f))
            ->when($request->to,         fn($q, $t)  => $q->where('date', '<=', $t))
            ->orderBy('date')
            ->orderByRaw('ISNULL(time_slot) DESC')
            ->orderBy('time_slot')
            ->paginate($perPage);

        return response()->json([
            'data' => $slots->items(),
            'meta' => [
                'currentPage' => $slots->currentPage(),
                'lastPage'    => $slots->lastPage(),
                'perPage'     => $slots->perPage(),
                'total'       => $slots->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id'  => 'required|integer|exists:products,id',
            'date'        => 'required|date',
            'time_slot'   => 'nullable|string|max:20',
            'total_quota' => 'required|integer|min:1',
            'is_blocked'  => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $slot = AvailabilitySlot::create($request->only(['product_id', 'date', 'time_slot', 'total_quota', 'is_blocked']));

        return response()->json(['data' => $slot], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $slot = AvailabilitySlot::findOrFail($id);
        $slot->update($request->only(['time_slot', 'total_quota', 'is_blocked']));

        return response()->json(['data' => $slot->fresh()]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id'  => 'required|integer|exists:products,id',
            'from'        => 'required|date',
            'to'          => 'required|date|after_or_equal:from',
            'time_slot'   => 'nullable|string|max:20',
            'total_quota' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $date = \Carbon\Carbon::parse($request->from);
        $to    = \Carbon\Carbon::parse($request->to);

        while ($date->lte($to)) {
            AvailabilitySlot::updateOrCreate(
                ['product_id' => $request->product_id, 'date' => $date->toDateString(), 'time_slot' => $request->time_slot],
                ['total_quota' => $request->total_quota, 'is_blocked' => false]
            );
            $date->addDay();
        }

        return response()->json(['message' => 'Slot ketersediaan berhasil dibuat/diperbarui.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $slot = AvailabilitySlot::findOrFail($id);

        if ($slot->booked_qty > 0) {
            return response()->json([
                'message' => 'Slot ini sudah memiliki booking dan tidak dapat dihapus.',
            ], 422);
        }

        $slot->delete();

        return response()->json(['message' => 'Slot berhasil dihapus.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|integer|exists:products,id',
            'from'       => 'required|date',
            'to'         => 'required|date|after_or_equal:from',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $query = AvailabilitySlot::whereBetween('date', [$request->from, $request->to])
            ->when($request->product_id, fn($q, $id) => $q->where('product_id', $id));

        $skipped = (clone $query)->where('booked_qty', '>', 0)->count();
        $deleted = (clone $query)->where('booked_qty', 0)->delete();

        $message = "{$deleted} slot berhasil direset.";
        if ($skipped > 0) {
            $message .= " {$skipped} slot dilewati karena sudah memiliki booking.";
        }

        return response()->json(['message' => $message]);
    }

    public function block(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'dates'      => 'required|array|min:1',
            'dates.*'    => 'date',
            'is_blocked' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        AvailabilitySlot::where('product_id', $request->product_id)
            ->whereIn('date', $request->dates)
            ->update(['is_blocked' => $request->is_blocked]);

        return response()->json(['message' => 'Status blokir berhasil diperbarui.']);
    }
}
