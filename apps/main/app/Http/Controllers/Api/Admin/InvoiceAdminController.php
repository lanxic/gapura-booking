<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceAdminController extends Controller
{
    /**
     * GET /admin/invoices
     * Daftar semua invoice dengan filter (PRD Section 4.4.1a).
     */
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with(['slot.activity', 'customer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('gateway'), fn ($q) => $q->where('gateway', $request->gateway))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))
            ->when($request->filled('search'), fn ($q) =>
                $q->where('invoice_code', 'like', '%' . $request->search . '%')
                  ->orWhere('guest_name', 'like', '%' . $request->search . '%')
                  ->orWhere('guest_email', 'like', '%' . $request->search . '%'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($invoices);
    }

    /**
     * GET /admin/invoices/{code}
     * Detail invoice lengkap beserta booking, items, dan payment attempts.
     */
    public function show(string $code): JsonResponse
    {
        $invoice = Invoice::with([
            'slot.activity',
            'customer',
            'booking',
            'paymentAttempts',
            'promoCode',
        ])->where('invoice_code', $code)->firstOrFail();

        return response()->json(['data' => $invoice]);
    }

    /**
     * GET /admin/invoices/export
     * Export daftar invoice ke CSV (PRD Section 4.4.1a).
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $invoices = Invoice::with(['slot.activity'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('gateway'), fn ($q) => $q->where('gateway', $request->gateway))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invoices-' . now()->format('Ymd') . '.csv"',
        ];

        return response()->stream(function () use ($invoices) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, [
                'Kode Invoice', 'Nama Tamu', 'Email', 'Aktivitas', 'Tanggal Slot',
                'Pax', 'Subtotal', 'Diskon', 'Total', 'Status',
                'Gateway', 'Gateway Order ID', 'Paid At', 'Dibuat',
            ]);
            foreach ($invoices as $inv) {
                fputcsv($fp, [
                    $inv->invoice_code,
                    $inv->guest_name,
                    $inv->guest_email,
                    $inv->slot?->activity?->name ?? '—',
                    $inv->slot?->date ?? '—',
                    $inv->pax_count,
                    $inv->subtotal,
                    $inv->discount_amount,
                    $inv->total_amount,
                    $inv->status,
                    $inv->gateway ?? '—',
                    $inv->gateway_order_id ?? '—',
                    $inv->paid_at?->format('Y-m-d H:i') ?? '—',
                    $inv->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($fp);
        }, 200, $headers);
    }
}
