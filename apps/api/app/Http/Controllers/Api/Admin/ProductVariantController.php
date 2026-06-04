<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductVariantController extends Controller
{
    public function index(int $productId): JsonResponse
    {
        $product  = Product::findOrFail($productId);
        $variants = $product->variants()->orderBy('id')->get();

        return response()->json(['data' => $variants]);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $product   = Product::findOrFail($productId);
        $validator = Validator::make($request->all(), [
            'label'         => 'required|string|max:100',
            'description'   => 'nullable|string',
            'price_adult'   => 'required|integer|min:0',
            'price_child'   => 'required|integer|min:0',
            'min_qty'       => 'integer|min:1',
            'max_qty'       => 'integer|min:1',
            'adult_min_age' => 'integer|min:0',
            'adult_max_age' => 'integer|min:0',
            'child_min_age' => 'integer|min:0',
            'child_max_age' => 'integer|min:0',
            'is_active'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $variant = $product->variants()->create($request->only([
            'label', 'description', 'price_adult', 'price_child',
            'min_qty', 'max_qty', 'is_active',
            'adult_min_age', 'adult_max_age', 'child_min_age', 'child_max_age',
        ]));

        return response()->json(['data' => $variant], 201);
    }

    public function show(int $productId, int $id): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);
        return response()->json(['data' => $variant]);
    }

    public function update(Request $request, int $productId, int $id): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);
        $variant->update($request->only([
            'label', 'description', 'price_adult', 'price_child',
            'min_qty', 'max_qty', 'is_active',
            'adult_min_age', 'adult_max_age', 'child_min_age', 'child_max_age',
        ]));

        return response()->json(['data' => $variant->fresh()]);
    }

    public function destroy(int $productId, int $id): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);
        $variant->delete();

        return response()->json(['message' => 'Varian dihapus.']);
    }
}
