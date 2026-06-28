<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSchedule;
use App\Models\ProductSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::withTrashed()
            ->with(['media' => fn ($q) => $q->where('is_primary', true)])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateProduct($request);
        $data['slug'] = Str::slug($data['name']);

        $product = Product::create($data);

        return response()->json(['data' => $product], 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::withTrashed()
            ->with(['media', 'addons', 'schedules'])
            ->findOrFail($id);

        return response()->json(['data' => $product]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $data    = $this->validateProduct($request, $id);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $product->update($data);

        return response()->json(['data' => $product->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update(['status' => 'archived']);
        $product->delete();

        return response()->json(['message' => 'Produk diarsipkan.']);
    }

    public function slotsIndex(Request $request, int $id): JsonResponse
    {
        $from = $request->get('from', now()->toDateString());
        $to   = $request->get('to', now()->addMonth()->toDateString());

        $slots = ProductSlot::where('product_id', $id)
            ->whereBetween('date', [$from, $to])
            ->withCount('bookings')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($slot) => [
                'id'                 => $slot->id,
                'date'               => $slot->date,
                'start_time'         => $slot->start_time,
                'end_time'           => $slot->end_time,
                'capacity'           => $slot->capacity,
                'booked_count'       => $slot->bookings_count,
                'price'              => $slot->price,
                'status'             => $slot->status,
                'remaining_capacity' => max(0, $slot->capacity - $slot->bookings_count),
            ]);

        return response()->json(['data' => $slots]);
    }

    public function slotUpdate(Request $request, int $slotId): JsonResponse
    {
        $slot = ProductSlot::findOrFail($slotId);

        $data = $request->validate([
            'capacity' => 'sometimes|integer|min:1',
            'price'    => 'sometimes|integer|min:0',
            'status'   => 'sometimes|in:available,full,cancelled',
        ]);

        $slot->update($data);

        return response()->json(['data' => $slot->fresh()]);
    }

    public function generateSlots(Request $request, int $id): JsonResponse
    {
        $product   = Product::findOrFail($id);
        $schedules = ProductSchedule::where('product_id', $id)->where('is_active', true)->get();

        if ($schedules->isEmpty()) {
            return response()->json(['message' => 'Tidak ada template jadwal aktif untuk produk ini.'], 422);
        }

        if ($request->filled('days')) {
            $start = \Carbon\Carbon::today();
            $end   = $start->copy()->addDays((int) $request->days - 1);
        } else {
            $request->validate([
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
            ]);
            $start = \Carbon\Carbon::parse($request->start_date);
            $end   = \Carbon\Carbon::parse($request->end_date);
        }
        $created = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dow = $date->dayOfWeek;

            foreach ($schedules->where('day_of_week', $dow) as $schedule) {
                ProductSlot::firstOrCreate(
                    ['product_id' => $id, 'date' => $date->toDateString(), 'start_time' => $schedule->start_time],
                    [
                        'tenant_id'   => $product->tenant_id,
                        'schedule_id' => $schedule->id,
                        'end_time'    => $schedule->end_time,
                        'capacity'    => $schedule->default_capacity,
                        'price'       => $product->base_price,
                        'status'      => 'available',
                    ]
                );
                $created++;
            }
        }

        return response()->json(['message' => "{$created} slot berhasil digenerate."]);
    }

    private function validateProduct(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'tenant_id'        => 'sometimes|exists:tenants,id',
            'name'             => 'required|string|max:200',
            'type'             => 'nullable|in:aktivitas',
            'category'         => 'required|in:indoor,outdoor',
            'description'      => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'min_pax'          => 'required|integer|min:1',
            'max_pax'          => 'required|integer|gte:min_pax',
            'level'            => 'nullable|in:beginner,intermediate,advanced',
            'min_age'          => 'nullable|integer|min:0',
            'base_price'       => 'required|integer|min:0',
            'status'           => 'nullable|in:active,inactive,archived',
            'meta'             => 'nullable|array',
        ]);
    }
}
