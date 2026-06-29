<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['media' => fn ($q) => $q->where('is_primary', true)])->active();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('min_price')) {
            $query->where('price_adult', '>=', $request->integer('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price_adult', '<=', $request->integer('max_price'));
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $sortMap = [
            'price_asc'  => ['price_adult', 'asc'],
            'price_desc' => ['price_adult', 'desc'],
            'newest'     => ['created_at', 'desc'],
        ];
        [$col, $dir] = $sortMap[$request->query('sort', 'newest')] ?? ['created_at', 'desc'];
        $query->orderBy($col, $dir);

        $products = $query->paginate(12)->through(fn ($p) => [
            'id'               => $p->id,
            'name'             => $p->name,
            'slug'             => $p->slug,
            'category'         => $p->category,
            'duration_minutes' => $p->duration_minutes,
            'price_adult'       => $p->price_adult,
            'level'            => $p->level,
            'min_pax'          => $p->min_pax,
            'max_pax'          => $p->max_pax,
            'image'            => $p->media->first()?->url,
        ]);

        return response()->json($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::with(['media', 'addons' => fn ($q) => $q->where('is_active', true)])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => [
            'id'               => $product->id,
            'name'             => $product->name,
            'slug'             => $product->slug,
            'category'         => $product->category,
            'description'      => $product->description,
            'duration_minutes' => $product->duration_minutes,
            'min_pax'          => $product->min_pax,
            'max_pax'          => $product->max_pax,
            'level'            => $product->level,
            'min_age'          => $product->min_age,
            'price_adult'       => $product->price_adult,
            'meta'             => $product->meta,
            'media'            => $product->media->map(fn ($m) => ['url' => $m->url, 'is_primary' => $m->is_primary]),
            'addons'           => $product->addons->map(fn ($a) => [
                'id' => $a->id, 'name' => $a->name, 'price' => $a->price,
                'unit' => $a->unit, 'max_qty' => $a->max_qty,
            ]),
        ]]);
    }

    public function slots(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'pax'  => 'sometimes|integer|min:1|max:50',
        ]);

        $product  = Product::active()->where('slug', $slug)->firstOrFail();
        $pax      = $request->integer('pax', 1);
        $date     = $request->query('date');

        $cacheKey = "slots:{$product->id}:{$date}:{$pax}";

        $slots = Cache::remember($cacheKey, 60, function () use ($product, $date, $pax) {
            return ProductSlot::where('product_id', $product->id)
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
