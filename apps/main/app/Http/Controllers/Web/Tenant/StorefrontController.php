<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class StorefrontController extends Controller
{
    public function home(Request $request)
    {
        $tenant = App::make('current_tenant');

        $products = Product::where('tenant_id', $tenant->id)
            ->active()
            ->with('media')
            ->when($request->search, fn($q) =>
                $q->where(function ($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->search}%")
                       ->orWhere('description', 'like', "%{$request->search}%");
                })
            )
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->latest()
            ->get();

        return view('tenant.storefront.home', compact('tenant', 'products'));
    }
}
