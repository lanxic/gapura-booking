<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OfferAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Offer::with('activities:id,name,slug')
            ->when($request->search, fn($query, $s) => $query->where('title', 'like', "%$s%"))
            ->when($request->status, function ($query, $s) {
                $now = now();
                match ($s) {
                    'active'   => $query->where('start_date', '<=', $now)->where('end_date', '>=', $now)->where('is_active', true),
                    'inactive' => $query->where('is_active', false),
                    'expired'  => $query->where('end_date', '<', $now),
                    default    => null,
                };
            })
            ->latest()
            ->paginate(15);

        return response()->json($q);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'slug'           => 'nullable|string|unique:offers,slug',
            'image'          => 'nullable|string',
            'description'    => 'nullable|string',
            'discount_type'  => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => 'required|numeric|min:0',
            'min_pax'        => 'nullable|integer|min:1',
            'max_uses'       => 'nullable|integer|min:1',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'badge'          => 'nullable|string|max:50',
            'is_active'      => 'boolean',
            'activity_ids'   => 'nullable|array',
            'activity_ids.*' => 'integer|exists:activities,id',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['title']) . '-' . Str::random(4);

        $offer = Offer::create($data);

        if (! empty($data['activity_ids'])) {
            $offer->activities()->sync($data['activity_ids']);
        }

        return response()->json(['data' => $offer->load('activities:id,name,slug')], 201);
    }

    public function show(Offer $offer): JsonResponse
    {
        return response()->json(['data' => $offer->load('activities:id,name,slug', 'promoCodes')]);
    }

    public function update(Request $request, Offer $offer): JsonResponse
    {
        $data = $request->validate([
            'title'          => 'sometimes|string|max:255',
            'slug'           => ['sometimes', 'string', Rule::unique('offers', 'slug')->ignore($offer->id)],
            'image'          => 'nullable|string',
            'description'    => 'nullable|string',
            'discount_type'  => ['sometimes', Rule::in(['percent', 'fixed'])],
            'discount_value' => 'sometimes|numeric|min:0',
            'min_pax'        => 'nullable|integer|min:1',
            'max_uses'       => 'nullable|integer|min:1',
            'start_date'     => 'sometimes|date',
            'end_date'       => 'sometimes|date',
            'badge'          => 'nullable|string|max:50',
            'is_active'      => 'boolean',
            'activity_ids'   => 'nullable|array',
            'activity_ids.*' => 'integer|exists:activities,id',
        ]);

        $offer->update($data);

        if (array_key_exists('activity_ids', $data)) {
            $offer->activities()->sync($data['activity_ids'] ?? []);
        }

        return response()->json(['data' => $offer->fresh()->load('activities:id,name,slug')]);
    }

    public function destroy(Offer $offer): JsonResponse
    {
        $offer->delete();
        return response()->json(['message' => 'Offer dihapus.']);
    }

    // ─── Promo Codes ─────────────────────────────────────────────────────────

    public function promoCodes(Offer $offer): JsonResponse
    {
        return response()->json(['data' => $offer->promoCodes()->latest()->get()]);
    }

    public function storePromoCode(Request $request, Offer $offer): JsonResponse
    {
        $data = $request->validate([
            'code'     => 'required|string|unique:promo_codes,code',
            'max_uses' => 'nullable|integer|min:1',
        ]);

        $promo = $offer->promoCodes()->create([
            'code'      => strtoupper($data['code']),
            'max_uses'  => $data['max_uses'] ?? null,
            'used_count' => 0,
            'is_active'  => true,
        ]);

        return response()->json(['data' => $promo], 201);
    }

    public function togglePromoCode(PromoCode $promo): JsonResponse
    {
        $promo->update(['is_active' => ! $promo->is_active]);
        return response()->json(['data' => $promo]);
    }
}
