<?php

namespace App\Http\Controllers\Web\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSlot;
use Illuminate\Http\Request;
use Illuminate\Support\App;
use Illuminate\Support\Str;

class ProductAdminController extends Controller
{
    public function index(string $tenantSlug, Request $request)
    {
        $tenant   = \Illuminate\Support\Facades\App::make('current_tenant');
        $products = Product::where('tenant_id', $tenant->id)
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('tenant.admin.products.index', compact('products', 'tenant'));
    }

    public function create(string $tenantSlug)
    {
        $tenant = \Illuminate\Support\Facades\App::make('current_tenant');
        return view('tenant.admin.products.form', ['product' => null, 'tenant' => $tenant]);
    }

    public function store(string $tenantSlug, Request $request)
    {
        $tenant = \Illuminate\Support\Facades\App::make('current_tenant');
        $data   = $this->validated($request);
        $data['slug']      = Str::slug($data['name']);
        $data['tenant_id'] = $tenant->id;
        Product::create($data);

        return redirect()->route('tenant.admin.products.index')->with('success', 'Produk berhasil dibuat.');
    }

    public function edit(string $tenantSlug, Product $product)
    {
        $tenant = \Illuminate\Support\Facades\App::make('current_tenant');
        abort_if($product->tenant_id !== $tenant->id, 403);

        return view('tenant.admin.products.form', compact('product', 'tenant'));
    }

    public function update(string $tenantSlug, Request $request, Product $product)
    {
        $tenant = \Illuminate\Support\Facades\App::make('current_tenant');
        abort_if($product->tenant_id !== $tenant->id, 403);

        $product->update($this->validated($request, $product->id));

        return redirect()->route('tenant.admin.products.index')->with('success', 'Produk berhasil diupdate.');
    }

    public function destroy(string $tenantSlug, Product $product)
    {
        $tenant = \Illuminate\Support\Facades\App::make('current_tenant');
        abort_if($product->tenant_id !== $tenant->id, 403);

        $product->delete();
        return redirect()->route('tenant.admin.products.index')->with('success', 'Produk dihapus.');
    }

    public function bulkDestroy(string $tenantSlug, Request $request)
    {
        $tenant = \Illuminate\Support\Facades\App::make('current_tenant');
        $ids    = array_filter(explode(',', $request->input('ids', '')));

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada produk yang dipilih.');
        }

        $deleted = Product::where('tenant_id', $tenant->id)
            ->whereIn('id', $ids)
            ->delete();

        return redirect()->route('tenant.admin.products.index')
            ->with('success', "{$deleted} produk berhasil dihapus.");
    }

    public function slots(string $tenantSlug, Request $request, int $id)
    {
        $tenant     = \Illuminate\Support\Facades\App::make('current_tenant');
        $product    = Product::where('tenant_id', $tenant->id)->findOrFail($id);
        $filterStatus = $request->get('status', 'available');

        $query = ProductSlot::where('product_id', $id);
        if ($filterStatus === 'tidak_tersedia') {
            $query->whereIn('status', ['blocked', 'full', 'cancelled']);
        } elseif ($filterStatus !== 'all') {
            $query->where('status', $filterStatus);
        }
        $slots = $query->orderBy('date')->orderBy('start_time')->paginate(20)->withQueryString();

        return view('tenant.admin.products.slots', compact('product', 'slots', 'tenant', 'filterStatus'));
    }

    public function generateSlots(string $tenantSlug, Request $request, int $id)
    {
        $tenant  = \Illuminate\Support\Facades\App::make('current_tenant');
        $product = Product::where('tenant_id', $tenant->id)->findOrFail($id);

        $data = $request->validate([
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after_or_equal:start_date'],
            'days_of_week'   => ['required', 'array'],
            'start_time'     => ['required'],
            'end_time'       => ['required'],
            'capacity'       => ['required', 'integer', 'min:1'],
            'spare_capacity' => ['nullable', 'integer', 'min:0', 'max:10'],
            'price_adult'    => ['required', 'integer', 'min:0'],
            'price_child'    => ['nullable', 'integer', 'min:0'],
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
                'tenant_id'      => $tenant->id,
                'end_time'       => $data['end_time'],
                'capacity'       => $data['capacity'],
                'spare_capacity' => $data['spare_capacity'] ?? 0,
                'booked_count'   => 0,
                'price_adult'    => $data['price_adult'],
                'price_child'    => $data['price_child'] ?? null,
                'status'         => 'available',
            ]);
            $created++;
        }

        return redirect()->route('tenant.admin.products.slots', $id)
            ->with('success', "{$created} slot berhasil dibuat.");
    }

    public function bulkUpdateSlots(string $tenantSlug, Request $request)
    {
        $tenant = \Illuminate\Support\Facades\App::make('current_tenant');

        $data = $request->validate([
            'slot_ids_json' => ['required', 'string'],
            'status'        => ['required', 'in:available,blocked'],
            'product_id'    => ['required', 'integer'],
        ]);

        $ids = json_decode($data['slot_ids_json'], true) ?? [];
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada slot yang dipilih.');
        }

        $statusLabels = ['available' => 'Tersedia', 'blocked' => 'Tidak Tersedia'];

        $updated = ProductSlot::where('tenant_id', $tenant->id)
            ->whereIn('id', $ids)
            ->update(['status' => $data['status']]);

        $label = $statusLabels[$data['status']] ?? $data['status'];
        return redirect()->route('tenant.admin.products.slots', $data['product_id'])
            ->with('success', "{$updated} slot berhasil diubah ke \"{$label}\".");
    }

    public function storeSlot(string $tenantSlug, Request $request, int $id)
    {
        $tenant  = \Illuminate\Support\Facades\App::make('current_tenant');
        $product = Product::where('tenant_id', $tenant->id)->findOrFail($id);

        $data = $request->validate([
            'date'        => ['required', 'date'],
            'start_time'  => ['required'],
            'end_time'    => ['required', 'after:start_time'],
            'capacity'    => ['required', 'integer', 'min:1'],
            'price_adult' => ['required', 'integer', 'min:0'],
            'price_child' => ['nullable', 'integer', 'min:0'],
        ]);

        $existing = ProductSlot::where('product_id', $product->id)
            ->where('date', $data['date'])
            ->where('start_time', $data['start_time'])
            ->exists();

        if ($existing) {
            return redirect()->route('tenant.admin.products.slots', $id)
                ->with('error', 'Slot dengan tanggal dan jam tersebut sudah ada.');
        }

        ProductSlot::create([
            'tenant_id'    => $tenant->id,
            'product_id'   => $product->id,
            'date'         => $data['date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'capacity'     => $data['capacity'],
            'booked_count' => 0,
            'price_adult'  => $data['price_adult'],
            'price_child'  => $data['price_child'] ?? null,
            'status'       => 'available',
        ]);

        return redirect()->route('tenant.admin.products.slots', $id)
            ->with('success', 'Slot berhasil ditambahkan.');
    }

    public function updateSlot(string $tenantSlug, Request $request, int $slotId)
    {
        $tenant = \Illuminate\Support\Facades\App::make('current_tenant');
        $slot   = ProductSlot::where('tenant_id', $tenant->id)->findOrFail($slotId);

        $validated = $request->validate([
            'start_time'     => ['sometimes', 'date_format:H:i'],
            'end_time'       => ['sometimes', 'date_format:H:i'],
            'capacity'       => ['sometimes', 'integer', 'min:0'],
            'spare_capacity' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'price_adult'    => ['sometimes', 'numeric', 'min:0'],
            'price_child'    => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status'         => ['sometimes', 'in:available,blocked'],
        ]);

        $slot->update($validated);

        // Jika admin tidak mengubah status secara eksplisit, sync otomatis berdasarkan kapasitas
        if (! isset($validated['status'])) {
            $slot->syncStatus();
        }

        return back()->with('success', 'Slot diupdate.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'type'             => ['nullable', 'in:aktivitas'],
            'category'         => ['required', 'in:indoor,outdoor'],
            'price_adult'      => ['required', 'numeric', 'min:0'],
            'price_child'      => ['nullable', 'numeric', 'min:0'],
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
