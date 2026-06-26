<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function home()
    {
        $featured = Activity::active()
            ->where('is_featured', true)
            ->with('media')
            ->latest()
            ->take(6)
            ->get();

        $latest = Activity::active()
            ->with('media')
            ->latest()
            ->take(8)
            ->get();

        return view('home', compact('featured', 'latest'));
    }

    public function index(Request $request)
    {
        $query = Activity::active();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        $activities = $query->with('media')->latest()->paginate(12)->withQueryString();

        $categories = Activity::active()
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return view('activities.index', compact('activities', 'categories'));
    }

    public function show(string $slug)
    {
        $activity = Activity::active()
            ->where('slug', $slug)
            ->with([
                'media',
                'schedules' => fn($q) => $q->where('is_active', true)->orderBy('day_of_week'),
                'addons'    => fn($q) => $q->where('is_active', true),
                'slots'     => fn($q) => $q->where('date', '>=', now()->toDateString())
                                           ->where('status', 'available')
                                           ->orderBy('date')->orderBy('start_time'),
            ])
            ->firstOrFail();

        return view('activities.show', compact('activity'));
    }
}
