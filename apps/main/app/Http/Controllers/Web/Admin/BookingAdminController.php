<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivitySlot;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingAdminController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function index(Request $request)
    {
        $bookings = Booking::with(['slot.activity', 'customer'])
            ->when($request->search, fn($q) =>
                $q->where('booking_code', 'like', "%{$request->search}%")
                  ->orWhere('guest_name', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $slots = ActivitySlot::with('activity')
            ->where('date', '>=', now()->toDateString())
            ->where('is_active', true)
            ->orderBy('date')
            ->get();

        return view('admin.bookings.index', compact('bookings', 'slots'));
    }

    public function show(int $id)
    {
        $booking = Booking::with(['slot.activity', 'customer'])->findOrFail($id);
        return view('admin.bookings.show', compact('booking'));
    }

    public function update(Request $request, int $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update($request->validate([
            'status' => ['required', 'in:confirmed,cancelled,no_show'],
        ]));

        return back()->with('success', 'Booking diupdate.');
    }

    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'slot_id'        => ['required', 'exists:activity_slots,id'],
            'guest_name'     => ['required', 'string'],
            'guest_email'    => ['required', 'email'],
            'guest_phone'    => ['nullable', 'string'],
            'pax_count'      => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,bank_transfer,manual'],
        ]);

        try {
            $booking = $this->bookingService->createManual($data);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookings.show', $booking->id)
            ->with('success', 'Walk-in booking berhasil dibuat: ' . $booking->booking_code);
    }

    public function export(Request $request)
    {
        $bookings = Booking::with(['slot.activity'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $csv = "Booking Code,Name,Email,Activity,Date,Pax,Status\n";
        foreach ($bookings as $b) {
            $csv .= implode(',', [
                $b->booking_code,
                $b->guest_name,
                $b->guest_email,
                $b->slot?->activity?->name,
                $b->slot?->date?->format('Y-m-d'),
                $b->pax_count,
                $b->status,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bookings.csv"',
        ]);
    }
}
