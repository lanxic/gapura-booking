<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivitySlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ActivityController extends Controller
{
    /**
     * GET /api/v1/activities
     * Katalog aktivitas dengan filter & sort (PRD Section 4.1.1).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with(['media' => fn ($q) => $q->where('is_primary', true)])
            ->active();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->integer('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->integer('max_price'));
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $sortMap = [
            'popularity' => ['booked_count', 'desc'],  // subquery jika diperlukan
            'price_asc'  => ['base_price', 'asc'],
            'price_desc' => ['base_price', 'desc'],
            'newest'     => ['created_at', 'desc'],
        ];
        [$col, $dir] = $sortMap[$request->query('sort', 'newest')] ?? ['created_at', 'desc'];
        $query->orderBy($col, $dir);

        $activities = $query->paginate(12)->through(fn ($a) => [
            'id'               => $a->id,
            'name'             => $a->name,
            'slug'             => $a->slug,
            'category'         => $a->category,
            'duration_minutes' => $a->duration_minutes,
            'base_price'       => $a->base_price,
            'level'            => $a->level,
            'min_pax'          => $a->min_pax,
            'max_pax'          => $a->max_pax,
            'image'            => $a->media->first()?->url,
        ]);

        return response()->json($activities);
    }

    /**
     * GET /api/v1/activities/{slug}
     * Detail aktivitas lengkap + kalender availability (PRD Section 4.1.2).
     */
    public function show(string $slug): JsonResponse
    {
        $activity = Activity::with(['media', 'addons' => fn ($q) => $q->where('is_active', true)])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => [
            'id'               => $activity->id,
            'name'             => $activity->name,
            'slug'             => $activity->slug,
            'category'         => $activity->category,
            'description'      => $activity->description,
            'duration_minutes' => $activity->duration_minutes,
            'min_pax'          => $activity->min_pax,
            'max_pax'          => $activity->max_pax,
            'level'            => $activity->level,
            'min_age'          => $activity->min_age,
            'base_price'       => $activity->base_price,
            'meta'             => $activity->meta,
            'media'            => $activity->media->map(fn ($m) => ['url' => $m->url, 'is_primary' => $m->is_primary]),
            'addons'           => $activity->addons->map(fn ($a) => [
                'id' => $a->id, 'name' => $a->name, 'price' => $a->price,
                'unit' => $a->unit, 'max_qty' => $a->max_qty,
            ]),
        ]]);
    }

    /**
     * GET /api/v1/activities/{slug}/slots?date=YYYY-MM-DD&pax=2
     * Slot tersedia untuk tanggal tertentu (PRD Section 4.1.3).
     * Cache 60 detik via Redis.
     */
    public function slots(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'pax'  => 'sometimes|integer|min:1|max:50',
        ]);

        $activity = Activity::active()->where('slug', $slug)->firstOrFail();
        $pax      = $request->integer('pax', 1);
        $date     = $request->query('date');

        $cacheKey = "slots:{$activity->id}:{$date}:{$pax}";

        $slots = Cache::remember($cacheKey, 60, function () use ($activity, $date, $pax) {
            return ActivitySlot::where('activity_id', $activity->id)
                ->where('date', $date)
                ->where('status', '!=', 'cancelled')
                ->orderBy('start_time')
                ->get()
                ->map(fn ($s) => [
                    'id'                 => $s->id,
                    'start_time'         => $s->start_time,
                    'end_time'           => $s->end_time,
                    'price'              => $s->price,
                    'capacity'           => $s->capacity,
                    'remaining_capacity' => $s->remaining_capacity,
                    'status'             => $s->status === 'available' && $s->isAvailableFor($pax)
                        ? 'available'
                        : ($s->status === 'full' || ! $s->isAvailableFor($pax) ? 'full' : $s->status),
                ]);
        });

        return response()->json(['data' => $slots]);
    }
}
