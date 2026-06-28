<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function home()
    {
        // Main domain home — redirect ke tenant storefront jika hanya ada 1 tenant,
        // atau tampilkan landing page multi-tenant
        return view('home', [
            'featured' => Product::active()->where('is_featured', true)->with('media', 'tenant')->take(6)->get(),
            'latest'   => Product::active()->with('media', 'tenant')->latest()->take(8)->get(),
        ]);
    }

    public function index(Request $request)
    {
        $query = Product::active();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        $products   = $query->with('media', 'tenant')->latest()->paginate(12)->withQueryString();
        $categories = Product::active()->distinct()->pluck('category')->filter()->values();

        return view('activities.index', compact('products', 'categories'));
    }

    public function show(string $slug)
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->with([
                'media',
                'tenant',
                'schedules' => fn($q) => $q->where('is_active', true)->orderBy('day_of_week'),
                'addons'    => fn($q) => $q->where('is_active', true),
                'slots'     => fn($q) => $q->where('date', '>=', now()->toDateString())
                                           ->where('status', 'available')
                                           ->orderBy('date')->orderBy('start_time'),
            ])
            ->firstOrFail();

        return view('activities.show', compact('product'));
    }
}
