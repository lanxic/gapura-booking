<?php

namespace App\Services;

use App\Models\ProductAddon;
use App\Models\ProductSlot;
use Illuminate\Support\Collection;

class CartService
{
    public function items(): Collection
    {
        return $this->resolve(session('cart', []));
    }

    public function add(int $slotId, int $paxAdult, int $paxChild, string $addonsJson = '[]'): void
    {
        $cart = session('cart', []);

        foreach ($cart as $i => $item) {
            $existingSlotId = $item['slotId'] ?? $item['slot_id'] ?? null;
            if ((int) $existingSlotId === $slotId) {
                $cart[$i] = compact('slotId', 'paxAdult', 'paxChild', 'addonsJson');
                session(['cart' => $cart]);
                return;
            }
        }

        $cart[] = compact('slotId', 'paxAdult', 'paxChild', 'addonsJson');
        session(['cart' => $cart]);
    }

    public function remove(int $index): void
    {
        $cart = session('cart', []);
        unset($cart[$index]);
        session(['cart' => array_values($cart)]);
    }

    public function clear(): void
    {
        session()->forget('cart');
    }

    public function isEmpty(): bool
    {
        return empty(session('cart', []));
    }

    public function grandTotal(): int
    {
        return $this->items()->sum('subtotal');
    }

    public function count(): int
    {
        return count(session('cart', []));
    }

    private function resolve(array $cartItems): Collection
    {
        return collect($cartItems)->map(function ($item, $index) {
            $slot = ProductSlot::with('product')->find($item['slotId'] ?? $item['slot_id'] ?? null);
            if (!$slot) return null;

            $paxAdult = (int) ($item['paxAdult'] ?? $item['pax_adult'] ?? 0);
            $paxChild = (int) ($item['paxChild'] ?? $item['pax_child'] ?? 0);
            $pax      = $paxAdult + $paxChild;

            $addonsJson = $item['addonsJson'] ?? $item['addons_json'] ?? '[]';
            $addons = collect(json_decode($addonsJson, true) ?? [])
                ->filter(fn($a) => !empty($a['addon_id']) && ($a['quantity'] ?? 0) > 0)
                ->map(function ($a) {
                    $addon = ProductAddon::find($a['addon_id']);
                    if (!$addon) return null;
                    return [
                        'name'     => $addon->name,
                        'qty'      => (int) $a['quantity'],
                        'price'    => (int) $addon->price,
                        'subtotal' => (int) $addon->price * (int) $a['quantity'],
                    ];
                })->filter()->values();

            $priceAdult = $slot->price_adult ?? 0;
            $priceChild = $slot->price_child ?? $priceAdult;

            return [
                'index'       => $index,
                'slot'        => $slot,
                'slot_id'     => $slot->id,
                'pax_adult'   => $paxAdult,
                'pax_child'   => $paxChild,
                'pax'         => $pax,
                'price_adult' => $priceAdult,
                'price_child' => $priceChild,
                'addons'      => $addons,
                'addons_json' => $addonsJson,
                'subtotal'    => ($priceAdult * $paxAdult) + ($priceChild * $paxChild) + $addons->sum('subtotal'),
            ];
        })->filter()->values();
    }
}
