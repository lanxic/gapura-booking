<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilitySlotResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['variants', 'addons'])
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => ProductResource::collection($products)]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::with(['variants', 'addons'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->firstOrFail();

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function availability(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->where('is_deleted', false)->firstOrFail();

        $from = $request->query('from', now()->toDateString());
        $to   = $request->query('to', now()->addDays(30)->toDateString());

        $slots = $product->availabilitySlots()
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        return response()->json(['data' => AvailabilitySlotResource::collection($slots)]);
    }
}
