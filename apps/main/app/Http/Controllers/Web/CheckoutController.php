<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityAddon;
use App\Models\ActivitySlot;
use App\Services\CartService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly CartService    $cart,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::guard('web')->user();

        // ── Cart checkout mode ─────────────────────────────────────────────
        if (!$this->cart->isEmpty()) {
            $items      = $this->cart->items();
            $grandTotal = $this->cart->grandTotal();
            return view('checkout.index', compact('items', 'grandTotal', 'user'));
        }

        // ── Single-item mode (direct from activity page) ───────────────────
        $slotId     = $request->get('slot_id');
        $pax        = (int) $request->get('pax', 1);
        $addonsJson = $request->get('addons', '[]');

        if (!$slotId) {
            return redirect()->route('activities.index')->with('error', 'Pilih slot aktivitas terlebih dahulu.');
        }

        $slot = ActivitySlot::with('activity')->findOrFail($slotId);

        if (!$slot->isAvailableFor($pax)) {
            return back()->with('error', 'Slot tidak tersedia.');
        }

        $addonItems = $this->resolveAddonItems($addonsJson);
        $grandTotal = ($slot->price * $pax) + $addonItems->sum('subtotal');

        return view('checkout.index', compact('slot', 'pax', 'addonItems', 'addonsJson', 'grandTotal', 'user'));
    }

    public function store(Request $request)
    {
        $user = Auth::guard('web')->user();

        // ── Cart checkout ──────────────────────────────────────────────────
        if ($request->boolean('cart_checkout') && !$this->cart->isEmpty()) {
            return $this->storeFromCart($request, $user);
        }

        // ── Single-item checkout ───────────────────────────────────────────
        $data = $request->validate([
            'slot_id'        => ['required', 'exists:activity_slots,id'],
            'pax_count'      => ['required', 'integer', 'min:1'],
            'guest_name'     => ['required', 'string', 'max:255'],
            'guest_email'    => ['required', 'email'],
            'guest_phone'    => ['nullable', 'string', 'max:30'],
            'country'        => ['nullable', 'string', 'max:10'],
            'promo_code'     => ['nullable', 'string'],
            'payment_plan'   => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string'],
            'addons_json'    => ['nullable', 'string'],
            'agree_terms'    => ['accepted'],
        ]);

        $data['addons']      = $this->decodeAddonsJson($data['addons_json'] ?? '[]');
        $data['customer_id'] = $user?->id;
        unset($data['addons_json']);

        try {
            $invoice = $this->invoiceService->createFromCheckout($data);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('invoice.show', $invoice->invoice_code);
    }

    private function storeFromCart(Request $request, $user)
    {
        $data = $request->validate([
            'guest_name'  => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'country'     => ['nullable', 'string', 'max:10'],
            'promo_code'  => ['nullable', 'string'],
            'agree_terms' => ['accepted'],
        ]);

        $items    = $this->cart->items();
        $invoices = [];

        try {
            DB::transaction(function () use ($items, $data, $user, &$invoices) {
                foreach ($items as $item) {
                    $invoiceData = array_merge($data, [
                        'slot_id'        => $item['slot_id'],
                        'pax_count'      => $item['pax'],
                        'addons'         => $this->decodeAddonsJson($item['addons_json']),
                        'customer_id'    => $user?->id,
                    ]);
                    $invoices[] = $this->invoiceService->createFromCheckout($invoiceData);
                }
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $this->cart->clear();

        if (count($invoices) === 1) {
            return redirect()->route('invoice.show', $invoices[0]->invoice_code);
        }

        return redirect()->route('account.bookings')
            ->with('success', count($invoices) . ' tiket berhasil dipesan!');
    }

    private function resolveAddonItems(string $json): \Illuminate\Support\Collection
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return collect();

        return collect($decoded)
            ->filter(fn($a) => !empty($a['addon_id']) && ($a['quantity'] ?? 0) > 0)
            ->map(function ($a) {
                $model = ActivityAddon::find($a['addon_id']);
                if (!$model) return null;
                return [
                    'id'       => $model->id,
                    'name'     => $model->name,
                    'price'    => $model->price,
                    'unit'     => $model->unit,
                    'qty'      => (int) $a['quantity'],
                    'subtotal' => $model->price * (int) $a['quantity'],
                ];
            })
            ->filter()
            ->values();
    }

    private function decodeAddonsJson(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return [];

        return collect($decoded)
            ->filter(fn($a) => !empty($a['addon_id']) && ($a['quantity'] ?? 0) > 0)
            ->map(fn($a) => ['addon_id' => (int) $a['addon_id'], 'quantity' => (int) $a['quantity']])
            ->values()
            ->toArray();
    }
}
