<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductAdminController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('tenant')
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->tenant_id, fn($q) =>
                $q->where('tenant_id', $request->tenant_id))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $tenants = \App\Models\Tenant::active()->orderBy('name')->get();
        return view('admin.products.form', ['product' => null, 'tenants' => $tenants]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);
        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dibuat.');
    }

    public function edit(Product $product)
    {
        $tenants = \App\Models\Tenant::active()->orderBy('name')->get();
        return view('admin.products.form', compact('product', 'tenants'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product->id);
        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diupdate.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produk dihapus.');
    }

    public function slots(int $id)
    {
        $product = Product::findOrFail($id);
        $slots   = ProductSlot::where('product_id', $id)->orderBy('date')->orderBy('start_time')->paginate(20);

        return view('admin.products.slots', compact('product', 'slots'));
    }

    public function generateSlots(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date', 'after_or_equal:start_date'],
            'days_of_week' => ['required', 'array'],
            'start_time'   => ['required'],
            'end_time'     => ['required'],
            'capacity'     => ['required', 'integer', 'min:1'],
            'price_adult'  => ['required', 'integer', 'min:0'],
            'price_child'  => ['nullable', 'integer', 'min:0'],
        ]);

        $start   = \Carbon\Carbon::parse($data['start_date']);
        $end     = \Carbon\Carbon::parse($data['end_date']);
        $created = 0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (! in_array($d->dayOfWeek, $data['days_of_week'])) continue;

            ProductSlot::firstOrCreate([
                'product_id' => $product->id,
                'date'       => $d->toDateString(),
                'start_time' => $data['start_time'],
            ], [
                'tenant_id'    => $product->tenant_id,
                'end_time'     => $data['end_time'],
                'capacity'     => $data['capacity'],
                'booked_count' => 0,
                'price_adult'  => $data['price_adult'],
                'price_child'  => $data['price_child'] ?? null,
                'status'       => 'available',
            ]);
            $created++;
        }

        return redirect()->route('admin.products.slots', $id)
            ->with('success', "{$created} slot berhasil dibuat.");
    }

    public function updateSlot(Request $request, int $slotId)
    {
        $slot = ProductSlot::findOrFail($slotId);
        $slot->update($request->validate([
            'capacity'    => ['sometimes', 'integer', 'min:0'],
            'price_adult' => ['sometimes', 'numeric', 'min:0'],
            'price_child' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status'      => ['sometimes', 'in:available,full,blocked,cancelled'],
        ]));

        return back()->with('success', 'Slot diupdate.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'tenant_id'        => ['required', 'exists:tenants,id'],
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'type'             => ['nullable', 'in:aktivitas'],
            'category'         => ['required', 'in:indoor,outdoor'],
            'price_adult'       => ['required', 'numeric', 'min:0'],
            'max_pax'          => ['required', 'integer', 'min:1'],
            'min_pax'          => ['nullable', 'integer', 'min:1'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'level'            => ['nullable', 'in:beginner,intermediate,advanced'],
            'min_age'          => ['nullable', 'integer', 'min:0'],
            'status'           => ['nullable', 'in:active,inactive,archived'],
            'is_featured'      => ['boolean'],
        ]);
    }
}
