<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivitySchedule;
use App\Models\ActivitySlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $activities = Activity::withTrashed()
            ->with(['media' => fn ($q) => $q->where('is_primary', true)])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($activities);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateActivity($request);
        $data['slug'] = Str::slug($data['name']);

        $activity = Activity::create($data);

        return response()->json(['data' => $activity], 201);
    }

    public function show(int $id): JsonResponse
    {
        $activity = Activity::withTrashed()
            ->with(['media', 'addons', 'schedules'])
            ->findOrFail($id);

        return response()->json(['data' => $activity]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);
        $data = $this->validateActivity($request, $id);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $activity->update($data);

        return response()->json(['data' => $activity->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);
        $activity->update(['status' => 'archived']);
        $activity->delete(); // soft delete

        return response()->json(['message' => 'Aktivitas diarsipkan.']);
    }

    /**
     * POST /admin/activities/{id}/generate-slots
     * Generate slot dari template schedule untuk periode tertentu (PRD Section 4.6.3).
     */
    public function slotsIndex(Request $request, int $id): JsonResponse
    {
        $from = $request->get('from', now()->toDateString());
        $to   = $request->get('to', now()->addMonth()->toDateString());

        $slots = ActivitySlot::where('activity_id', $id)
            ->whereBetween('date', [$from, $to])
            ->withCount('bookings')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($slot) => [
                'id'                  => $slot->id,
                'date'                => $slot->date,
                'start_time'          => $slot->start_time,
                'end_time'            => $slot->end_time,
                'capacity'            => $slot->capacity,
                'booked_count'        => $slot->bookings_count,
                'price'               => $slot->price,
                'status'              => $slot->status,
                'remaining_capacity'  => max(0, $slot->capacity - $slot->bookings_count),
            ]);

        return response()->json(['data' => $slots]);
    }

    public function slotUpdate(Request $request, int $slotId): JsonResponse
    {
        $slot = ActivitySlot::findOrFail($slotId);

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
        $activity  = Activity::findOrFail($id);
        $schedules = ActivitySchedule::where('activity_id', $id)->where('is_active', true)->get();

        if ($schedules->isEmpty()) {
            return response()->json(['message' => 'Tidak ada template jadwal aktif untuk aktivitas ini.'], 422);
        }

        // Accept either days or start_date/end_date
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
                ActivitySlot::firstOrCreate(
                    ['activity_id' => $id, 'date' => $date->toDateString(), 'start_time' => $schedule->start_time],
                    [
                        'schedule_id' => $schedule->id,
                        'end_time'    => $schedule->end_time,
                        'capacity'    => $schedule->default_capacity,
                        'price'       => $activity->base_price,
                        'status'      => 'available',
                    ]
                );
                $created++;
            }
        }

        return response()->json(['message' => "{$created} slot berhasil digenerate."]);
    }

    private function validateActivity(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'             => 'required|string|max:200',
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
