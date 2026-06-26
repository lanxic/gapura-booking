<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivitySlot;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityAdminController extends Controller
{
    public function index(Request $request)
    {
        $activities = Activity::when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.activities.index', compact('activities'));
    }

    public function create()
    {
        return view('admin.activities.form', ['activity' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);
        Activity::create($data);

        return redirect()->route('admin.activities.index')->with('success', 'Aktivitas berhasil dibuat.');
    }

    public function edit(Activity $activity)
    {
        return view('admin.activities.form', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        $data = $this->validated($request, $activity->id);
        $activity->update($data);

        return redirect()->route('admin.activities.index')->with('success', 'Aktivitas berhasil diupdate.');
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();
        return redirect()->route('admin.activities.index')->with('success', 'Aktivitas dihapus.');
    }

    public function slots(int $id)
    {
        $activity = Activity::findOrFail($id);
        $slots    = ActivitySlot::where('activity_id', $id)->orderBy('date')->orderBy('start_time')->paginate(20);

        return view('admin.activities.slots', compact('activity', 'slots'));
    }

    public function generateSlots(Request $request, int $id)
    {
        $activity = Activity::findOrFail($id);

        $data = $request->validate([
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date', 'after_or_equal:start_date'],
            'days_of_week' => ['required', 'array'],
            'start_time'   => ['required'],
            'end_time'     => ['required'],
            'capacity'     => ['required', 'integer', 'min:1'],
            'price'        => ['required', 'integer', 'min:0'],
        ]);

        $start   = \Carbon\Carbon::parse($data['start_date']);
        $end     = \Carbon\Carbon::parse($data['end_date']);
        $created = 0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!in_array($d->dayOfWeek, $data['days_of_week'])) continue;

            ActivitySlot::firstOrCreate([
                'activity_id' => $activity->id,
                'date'        => $d->toDateString(),
                'start_time'  => $data['start_time'],
            ], [
                'end_time'    => $data['end_time'],
                'capacity'    => $data['capacity'],
                'booked_count'=> 0,
                'price'       => $data['price'],
                'status'      => 'available',
            ]);
            $created++;
        }

        return redirect()->route('admin.activities.slots', $id)
            ->with('success', "{$created} slot berhasil dibuat.");
    }

    public function updateSlot(Request $request, int $slotId)
    {
        $slot = ActivitySlot::findOrFail($slotId);
        $slot->update($request->validate([
            'capacity' => ['sometimes', 'integer', 'min:0'],
            'price'    => ['sometimes', 'numeric', 'min:0'],
            'status'   => ['sometimes', 'in:available,full,blocked,cancelled'],
        ]));

        return back()->with('success', 'Slot diupdate.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'category'         => ['required', 'in:indoor,outdoor'],
            'base_price'       => ['required', 'numeric', 'min:0'],
            'max_pax'          => ['required', 'integer', 'min:1'],
            'min_pax'          => ['nullable', 'integer', 'min:1'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'level'            => ['nullable', 'in:beginner,intermediate,advanced'],
            'min_age'          => ['nullable', 'integer', 'min:0'],
            'status'           => ['nullable', 'in:active,inactive,archived'],
            'is_featured'      => ['boolean'],
        ]);

        // Checkbox "is_active" dari form lama → petakan ke status
        if (!isset($data['status']) && $request->has('is_active')) {
            $data['status'] = $request->boolean('is_active') ? 'active' : 'inactive';
        }

        return $data;
    }
}
