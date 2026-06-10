<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductAdminController extends Controller
{
    private ?CloudinaryService $cloudinary = null;

    private function cloudinary(): CloudinaryService
    {
        return $this->cloudinary ??= app(CloudinaryService::class);
    }

    public function index(Request $request): JsonResponse
    {
        $products = Product::where('is_deleted', false)
            ->with(['variants', 'addons'])
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'currentPage' => $products->currentPage(),
                'lastPage'    => $products->lastPage(),
                'perPage'     => $products->perPage(),
                'total'       => $products->total(),
            ],
        ]);
    }

    private function productFields(Request $request, bool $isCreate = false): array
    {
        $data = $request->only([
            'name', 'slug', 'description',
            'location', 'opening_hours', 'meeting_point',
            'usage_instructions', 'cancellation_policy', 'terms_conditions',
            'cloudinary_image_url', 'cloudinary_thumbnail_url',
            'cloudinary_gallery_urls',
            'is_active', 'instant_confirmation',
        ]);

        // JSON fields
        if ($request->has('highlights')) {
            $data['highlights'] = is_array($request->highlights) ? $request->highlights : [];
        }
        if ($request->has('cloudinary_gallery_urls') && is_array($request->cloudinary_gallery_urls)) {
            $data['cloudinary_gallery_urls'] = array_values(array_filter($request->cloudinary_gallery_urls));
        }

        // Booleans
        if ($request->has('is_active'))            $data['is_active']            = $request->boolean('is_active');
        if ($request->has('instant_confirmation'))  $data['instant_confirmation'] = $request->boolean('instant_confirmation');

        // Auto-generate slug if name provided and no explicit slug
        if (!empty($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);
        }

        return $data;
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'location'              => 'nullable|string',
            'opening_hours'         => 'nullable|string|max:100',
            'is_active'             => 'boolean',
            'instant_confirmation'  => 'boolean',
            'highlights'            => 'nullable|array',
            'cloudinary_gallery_urls' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $product = Product::create($this->productFields($request, true));

        return response()->json(['data' => $product->load(['variants', 'addons'])], 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['variants', 'addons'])->findOrFail($id);
        return response()->json(['data' => $product]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::where('is_deleted', false)->findOrFail($id);
        $product->update($this->productFields($request));

        return response()->json(['data' => $product->fresh(['variants', 'addons'])]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::where('is_deleted', false)->findOrFail($id);
        $product->update(['is_deleted' => true]);

        return response()->json(['message' => 'Produk dihapus.']);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|image|max:5120']);

        $folders = $this->cloudinary()->getFolders();
        $result  = $this->cloudinary()->uploadImage($request->file('file'), $folders['products']);

        return response()->json([
            'image_url'     => $result['secure_url'],
            'thumbnail_url' => $result['eager_url'],
            'public_id'     => $result['public_id'],
        ]);
    }
}
