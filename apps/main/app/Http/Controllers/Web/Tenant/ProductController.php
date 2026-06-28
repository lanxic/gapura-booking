<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $tenant   = App::make('current_tenant');
        $products = Product::where('tenant_id', $tenant->id)
            ->active()
            ->with('media')
            ->when($request->category, fn($q) =>
                $q->where('category', $request->category))
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%"))
            ->paginate(12)
            ->withQueryString();

        return view('tenant.storefront.products.index', compact('tenant', 'products'));
    }

    public function show(string $tenantSlug, string $slug)
    {
        $tenant  = App::make('current_tenant');
        $product = Product::where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->active()
            ->with([
                'media',
                'addons'     => fn($q) => $q->where('is_active', true),
                'schedules'  => fn($q) => $q->where('is_active', true)->orderBy('day_of_week'),
                'slots'      => fn($q) => $q->where('date', '>=', now()->toDateString())
                                           ->where('status', 'available')
                                           ->orderBy('date')->orderBy('start_time'),
            ])
            ->firstOrFail();

        return view('tenant.storefront.products.show', compact('tenant', 'product'));
    }
}
