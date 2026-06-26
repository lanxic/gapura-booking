<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerAdminController extends Controller
{
    public function index(Request $request)
    {
        $customers = User::where('role', UserRole::customer)
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%"))
            ->withTrashed()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(int $id)
    {
        $customer = User::withTrashed()->findOrFail($id);
        return view('admin.customers.show', compact('customer'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string'],
        ]);

        User::create([...$data, 'role' => UserRole::customer, 'password' => Hash::make('password'), 'is_active' => true]);

        return redirect()->route('admin.customers.index')->with('success', 'Customer berhasil dibuat.');
    }

    public function update(Request $request, int $id)
    {
        $customer = User::findOrFail($id);
        $customer->update($request->validate([
            'name'  => ['required', 'string'],
            'phone' => ['nullable', 'string'],
        ]));

        return back()->with('success', 'Customer diupdate.');
    }

    public function destroy(int $id)
    {
        User::findOrFail($id)->delete();
        return back()->with('success', 'Customer dihapus (soft delete).');
    }

    public function restore(int $id)
    {
        User::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'Customer dipulihkan.');
    }

    public function toggleActive(int $id)
    {
        $customer = User::findOrFail($id);
        $customer->update(['is_active' => !$customer->is_active]);
        return back()->with('success', 'Status customer diupdate.');
    }

    public function export()
    {
        $customers = User::where('role', UserRole::customer)->get();

        $csv = "Name,Email,Phone,Active,Joined\n";
        foreach ($customers as $c) {
            $csv .= implode(',', [$c->name, $c->email, $c->phone, $c->is_active ? 'Yes' : 'No', $c->created_at->format('Y-m-d')]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers.csv"',
        ]);
    }
}
