<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OfferAdminController extends Controller
{
    public function index()
    {
        $offers = Offer::latest()->paginate(15);
        return view('admin.offers.index', compact('offers'));
    }

    public function create()
    {
        return view('admin.offers.form', ['offer' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']);
        Offer::create($data);

        return redirect()->route('admin.offers.index')->with('success', 'Penawaran berhasil dibuat.');
    }

    public function edit(Offer $offer)
    {
        return view('admin.offers.form', compact('offer'));
    }

    public function update(Request $request, Offer $offer)
    {
        $offer->update($this->validated($request));
        return redirect()->route('admin.offers.index')->with('success', 'Penawaran diupdate.');
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();
        return redirect()->route('admin.offers.index')->with('success', 'Penawaran dihapus.');
    }

    public function promoCodes(Offer $offer)
    {
        $promoCodes = $offer->promoCodes()->latest()->paginate(20);
        return view('admin.offers.promo-codes', compact('offer', 'promoCodes'));
    }

    public function storePromoCode(Request $request, Offer $offer)
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'unique:promo_codes,code'],
            'max_uses'  => ['nullable', 'integer', 'min:1'],
        ]);

        $offer->promoCodes()->create([...$data, 'is_active' => true, 'used_count' => 0]);

        return back()->with('success', 'Promo code dibuat.');
    }

    public function togglePromoCode(PromoCode $promo)
    {
        $promo->update(['is_active' => !$promo->is_active]);
        return back()->with('success', 'Status promo code diupdate.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'          => ['required', 'string'],
            'description'    => ['nullable', 'string'],
            'discount_type'  => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'valid_until'    => ['nullable', 'date'],
            'is_active'      => ['boolean'],
        ]);
    }
}
