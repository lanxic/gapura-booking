<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProductSlot;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    private function isTenant(): bool
    {
        return app()->bound('current_tenant');
    }

    public function index()
    {
        $items      = $this->cart->items();
        $grandTotal = $this->cart->grandTotal();
        $view = $this->isTenant() ? 'tenant.storefront.cart.index' : 'cart.index';
        return view($view, compact('items', 'grandTotal'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'slot_id'     => 'required|exists:product_slots,id',
            'pax_adult'   => 'required|integer|min:0',
            'pax_child'   => 'required|integer|min:0',
            'addons_json' => 'nullable|string',
        ]);

        $pax = (int) $data['pax_adult'] + (int) $data['pax_child'];
        if ($pax < 1) {
            return back()->with('error', 'Pilih minimal 1 peserta.');
        }

        $slot = ProductSlot::findOrFail($data['slot_id']);
        if (!$slot->isAvailableFor($pax)) {
            return back()->with('error', 'Slot tidak tersedia untuk jumlah peserta yang dipilih.');
        }

        $this->cart->add(
            (int) $data['slot_id'],
            (int) $data['pax_adult'],
            (int) $data['pax_child'],
            $data['addons_json'] ?? '[]',
        );

        $cartRoute = $this->isTenant() ? 'tenant.cart.index' : 'cart.index';
        return redirect()->route($cartRoute);
    }

    public function remove(string $tenantSlugOrIndex, ?int $index = null)
    {
        // When called from tenant domain, tenantSlugOrIndex = tenantSlug, index = actual index
        // When called from main domain, tenantSlugOrIndex = the index itself (as string)
        $actualIndex = $index !== null ? $index : (int) $tenantSlugOrIndex;
        $this->cart->remove($actualIndex);
        return back();
    }

    public function clear()
    {
        $this->cart->clear();
        $homeRoute = $this->isTenant() ? 'tenant.home' : 'home';
        return redirect()->route($homeRoute);
    }
}
