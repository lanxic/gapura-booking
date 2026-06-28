<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingAdminController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    /**
     * GET /admin/bookings
     * Daftar semua booking dengan filter (PRD Section 4.6.5).
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['slot.product', 'customer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('product_id'), fn ($q) => $q->whereHas('slot', fn ($s) => $s->where('product_id', $request->product_id)))
            ->when($request->filled('date'), fn ($q) => $q->whereHas('slot', fn ($s) => $s->whereDate('date', $request->date)))
            ->when($request->filled('booking_date'), fn ($q) => $q->whereDate('created_at', $request->booking_date))
            ->when($request->filled('guest'), fn ($q) => $q->where('guest_name', 'like', '%' . $request->guest . '%')
                ->orWhere('guest_email', 'like', '%' . $request->guest . '%'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($bookings);
    }

    /**
     * GET /admin/bookings/{id}
     * Detail booking lengkap.
     */
    public function show(int $id): JsonResponse
    {
        $booking = Booking::with(['slot.product', 'customer', 'addons.addon', 'participants', 'invoice.paymentAttempts'])
            ->findOrFail($id);

        return response()->json(['data' => $booking]);
    }

    /**
     * PUT /admin/bookings/{id}
     * Update status, reschedule, atau cancel (PRD Section 4.6.5).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $data    = $request->validate([
            'status' => 'sometimes|in:confirmed,attended,cancelled,no_show',
            'notes'  => 'nullable|string',
        ]);

        $booking->update($data);

        return response()->json(['data' => $booking->fresh()]);
    }

    /**
     * POST /admin/bookings
     * Manual booking tanpa flow webstore — untuk walk-in / telepon (PRD Section 4.6.5).
     */
    public function storeManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slot_id'        => 'required|integer|exists:product_slots,id',
            'guest_name'     => 'required|string',
            'guest_email'    => 'required|email',
            'guest_phone'    => 'nullable|string',
            'pax_count'      => 'required|integer|min:1',
            'notes'          => 'nullable|string',
            'payment_method' => 'nullable|string|in:cash,bank_transfer,manual',
        ]);

        $slot    = \App\Models\ProductSlot::with('product')->findOrFail($data['slot_id']);
        $gateway = $data['payment_method'] ?? 'cash';
        $total   = $slot->price * $data['pax_count'];

        $invoice = Invoice::create([
            'invoice_code'     => 'INV-' . now()->format('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'guest_name'       => $data['guest_name'],
            'guest_email'      => $data['guest_email'],
            'guest_phone'      => $data['guest_phone'] ?? null,
            'checkout_slot_id' => $slot->id,
            'pax_count'        => $data['pax_count'],
            'items'            => [[
                'type'       => 'activity',
                'name'       => $slot->product->name,
                'unit_price' => $slot->price,
                'quantity'   => $data['pax_count'],
                'subtotal'   => $total,
            ]],
            'subtotal'         => $total,
            'discount_amount'  => 0,
            'total_amount'     => $total,
            'payment_plan'     => 'FULL',
            'due_now'          => $total,
            'due_later'        => 0,
            'status'           => 'paid',
            'paid_at'          => now(),
            'due_at'           => now()->addHours(2),
            'gateway'          => $gateway,
            'notes'            => $data['notes'] ?? null,
        ]);

        $booking = $this->bookingService->createFromInvoice($invoice);

        return response()->json(['data' => $booking->load('slot.product')], 201);
    }

    /**
     * POST /admin/bookings/{id}/export-csv — dipanggil via GET /admin/bookings dengan ?export=csv
     * Export booking list ke CSV (PRD Section 4.6.5).
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $bookings = Booking::with(['slot.product'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereHas('slot', fn ($s) => $s->whereDate('date', $request->date)))
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bookings-' . now()->format('Ymd') . '.csv"',
        ];

        return response()->stream(function () use ($bookings) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['Booking Code', 'Product', 'Date', 'Time', 'Guest', 'Email', 'Pax', 'Status', 'Total']);
            foreach ($bookings as $b) {
                fputcsv($fp, [
                    $b->booking_code,
                    $b->slot->activity->name ?? '',
                    $b->slot->date ?? '',
                    $b->slot->start_time ?? '',
                    $b->guest_name,
                    $b->guest_email,
                    $b->pax_count,
                    $b->status,
                    $b->total_amount,
                ]);
            }
            fclose($fp);
        }, 200, $headers);
    }
}
