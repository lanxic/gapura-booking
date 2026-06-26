<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    /**
     * GET /api/v1/offers
     * Daftar semua penawaran aktif (PRD Section 4.3).
     */
    public function index(): JsonResponse
    {
        $offers = Offer::active()->with('activities:id,name,slug')->paginate(12)
            ->through(fn ($o) => [
                'id'             => $o->id,
                'title'          => $o->title,
                'slug'           => $o->slug,
                'image'          => $o->image,
                'start_date'     => $o->start_date,
                'end_date'       => $o->end_date,
                'discount_type'  => $o->discount_type,
                'discount_value' => $o->discount_value,
                'badge'          => $o->badge,
                'activities'     => $o->activities->map(fn ($a) => ['name' => $a->name, 'slug' => $a->slug]),
            ]);

        return response()->json($offers);
    }

    /**
     * GET /api/v1/offers/{slug}
     * Detail offer + T&C (PRD Section 4.3).
     */
    public function show(string $slug): JsonResponse
    {
        $offer = Offer::active()->with('activities:id,name,slug')->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => [
            'id'             => $offer->id,
            'title'          => $offer->title,
            'slug'           => $offer->slug,
            'description'    => $offer->description,
            'image'          => $offer->image,
            'start_date'     => $offer->start_date,
            'end_date'       => $offer->end_date,
            'discount_type'  => $offer->discount_type,
            'discount_value' => $offer->discount_value,
            'badge'          => $offer->badge,
            'activities'     => $offer->activities->map(fn ($a) => ['name' => $a->name, 'slug' => $a->slug]),
        ]]);
    }

    /**
     * POST /api/v1/promo/validate
     * Validasi promo code sebelum checkout (PRD Section 7.1).
     */
    public function validatePromo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'        => 'required|string',
            'amount'      => 'required|integer|min:1',
            'activity_id' => 'nullable|integer',
        ]);

        $promo = PromoCode::where('code', strtoupper($data['code']))->first();

        if (! $promo || ! $promo->isValid($data['amount'])) {
            return response()->json(['message' => 'Kode promo tidak valid atau sudah kadaluarsa.'], 422);
        }

        $discount = $promo->calculateDiscount($data['amount']);

        return response()->json(['data' => [
            'code'            => $promo->code,
            'discount_type'   => $promo->discount_type,
            'discount_value'  => $promo->discount_value,
            'discount_amount' => $discount,
            'final_amount'    => max(0, $data['amount'] - $discount),
        ]]);
    }
}
