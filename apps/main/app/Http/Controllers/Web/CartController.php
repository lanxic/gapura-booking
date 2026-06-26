<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivitySlot;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index()
    {
        $items      = $this->cart->items();
        $grandTotal = $this->cart->grandTotal();
        return view('cart.index', compact('items', 'grandTotal'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'slot_id'     => 'required|exists:activity_slots,id',
            'pax_adult'   => 'required|integer|min:0',
            'pax_child'   => 'required|integer|min:0',
            'addons_json' => 'nullable|string',
        ]);

        $pax = (int) $data['pax_adult'] + (int) $data['pax_child'];
        if ($pax < 1) {
            return back()->with('error', 'Pilih minimal 1 peserta.');
        }

        $slot = ActivitySlot::findOrFail($data['slot_id']);
        if (!$slot->isAvailableFor($pax)) {
            return back()->with('error', 'Slot tidak tersedia untuk jumlah peserta yang dipilih.');
        }

        $this->cart->add(
            (int) $data['slot_id'],
            (int) $data['pax_adult'],
            (int) $data['pax_child'],
            $data['addons_json'] ?? '[]',
        );

        return redirect()->route('cart.index');
    }

    public function remove(int $index)
    {
        $this->cart->remove($index);
        return back();
    }

    public function clear()
    {
        $this->cart->clear();
        return redirect()->route('activities.index');
    }
}
