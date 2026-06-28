<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::withCount(['products', 'bookings'])
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('admin.tenants.form', ['tenant' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        Tenant::create($data);

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant berhasil dibuat.');
    }

    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.form', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $this->validated($request, $tenant->id);
        $tenant->update($data);

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant berhasil diupdate.');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('admin.tenants.index')->with('success', 'Tenant dihapus.');
    }

    public function toggleActive(Tenant $tenant)
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);
        $label = $tenant->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Tenant berhasil {$label}.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'slug'           => ['nullable', 'string', 'max:100', 'alpha_dash',
                                 "unique:tenants,slug,{$ignoreId}"],
            'domain'         => ['nullable', 'string', 'max:255',
                                 "unique:tenants,domain,{$ignoreId}"],
            'invoice_prefix' => ['required', 'string', 'max:10', 'alpha',
                                 "unique:tenants,invoice_prefix,{$ignoreId}"],
            'logo_url'       => ['nullable', 'url'],
            'is_active'      => ['boolean'],
            'settings'       => ['nullable', 'array'],
        ]);
    }
}
