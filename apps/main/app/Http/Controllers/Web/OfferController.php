<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Offer;

class OfferController extends Controller
{
    public function index()
    {
        $offers = Offer::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->latest()
            ->paginate(12);

        return view('offers.index', compact('offers'));
    }

    public function show(string $slug)
    {
        $offer = Offer::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('offers.show', compact('offer'));
    }
}
