<?php

namespace App\Http\Controllers\Web\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class BookingAdminController extends Controller
{
    public function index(string $tenantSlug, Request $request)
    {
        $tenant   = App::make('current_tenant');
        $bookings = Booking::with('slot.product', 'customer')
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn($q) =>
                $q->where('booking_code', 'like', "%{$request->search}%")
                  ->orWhere('guest_name', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) =>
                $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tenant.admin.bookings.index', compact('bookings', 'tenant'));
    }

    public function show(string $tenantSlug, int $id)
    {
        $tenant  = App::make('current_tenant');
        $booking = Booking::with('slot.product', 'customer', 'addons', 'participants', 'invoice')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        return view('tenant.admin.bookings.show', compact('booking', 'tenant'));
    }

    public function update(string $tenantSlug, Request $request, int $id)
    {
        $tenant  = App::make('current_tenant');
        $booking = Booking::where('tenant_id', $tenant->id)->findOrFail($id);

        $booking->update($request->validate([
            'status' => ['required', 'in:pending,confirmed,attended,cancelled,no_show'],
            'notes'  => ['nullable', 'string'],
        ]));

        return redirect()->route('tenant.admin.bookings.show', $id)
            ->with('success', 'Status booking berhasil diupdate.');
    }
}
