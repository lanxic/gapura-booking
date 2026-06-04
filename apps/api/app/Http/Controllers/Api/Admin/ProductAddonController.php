<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductAddonController extends Controller
{
    public function index(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $addons  = $product->addons()->orderByPivot('sort_order')->get();

        return response()->json(['data' => $addons]);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $product   = Product::findOrFail($productId);
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|integer|min:0',
            'max_qty'     => 'required|integer|min:1',
            'is_active'   => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $addon = Addon::create($request->only(['name', 'description', 'price', 'max_qty', 'is_active']));
        $product->addons()->attach($addon->id, ['is_active' => true, 'sort_order' => 0]);

        return response()->json(['data' => $addon], 201);
    }

    public function show(int $productId, int $id): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $addon   = $product->addons()->findOrFail($id);

        return response()->json(['data' => $addon]);
    }

    public function update(Request $request, int $productId, int $id): JsonResponse
    {
        $addon = Addon::findOrFail($id);
        $addon->update($request->only(['name', 'description', 'price', 'max_qty', 'is_active']));

        return response()->json(['data' => $addon->fresh()]);
    }

    public function destroy(int $productId, int $id): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $product->addons()->detach($id);

        return response()->json(['message' => 'Add-on dilepas dari produk.']);
    }
}
