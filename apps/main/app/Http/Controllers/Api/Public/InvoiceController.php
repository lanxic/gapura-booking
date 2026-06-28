<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    /**
     * POST /api/v1/invoices
     * Buat invoice saat checkout submit Step 4 (PRD Section 13.3 Step 1-3).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slot_id'      => 'required|integer|exists:activity_slots,id',
            'pax_count'    => 'required|integer|min:1|max:50',
            'guest_name'   => 'required|string|max:150',
            'guest_email'  => 'required|email',
            'guest_phone'  => 'nullable|string|max:20',
            'notes'        => 'nullable|string|max:500',
            'addons'       => 'nullable|array',
            'addons.*.addon_id' => 'required|integer|exists:activity_addons,id',
            'addons.*.quantity' => 'required|integer|min:1',
            'promo_code'   => 'nullable|string|max:50',
            'payment_plan' => 'nullable|string|in:FULL,DP30,DP50,DP70',
            'customer_id'  => 'nullable|integer|exists:customers,id',
        ]);

        try {
            $invoice = $this->invoiceService->createFromCheckout($data);
            $payment = $this->invoiceService->initiatePayment($invoice);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'invoice_code' => $invoice->invoice_code,
                'total_amount' => $invoice->total_amount,
                'due_now'      => $invoice->due_now,
                'due_later'    => $invoice->due_later,
                'payment_plan' => $invoice->payment_plan,
                'status'       => $invoice->status,
                'due_at'       => $invoice->due_at,
                'payment_url'  => $payment['payment_url'],
            ],
        ], 201);
    }

    /**
     * GET /api/v1/invoices/{invoice_code}
     * Cek status invoice (PRD Section 13.6).
     */
    public function show(string $invoiceCode): JsonResponse
    {
        $invoice = Invoice::where('invoice_code', $invoiceCode)->firstOrFail();

        return response()->json(['data' => [
            'invoice_code' => $invoice->invoice_code,
            'status'       => $invoice->status,
            'total_amount' => $invoice->total_amount,
            'due_now'      => $invoice->due_now,
            'due_at'       => $invoice->due_at,
            'paid_at'      => $invoice->paid_at,
            'booking_code' => $invoice->booking?->booking_code,
            'pdf_url'      => $invoice->pdf_path,
        ]]);
    }

    /**
     * POST /api/v1/invoices/{invoice_code}/retry-payment
     * Inisiasi ulang payment untuk invoice PENDING/FAILED (PRD Section 13.3.1).
     */
    public function retryPayment(Request $request, string $invoiceCode): JsonResponse
    {
        $invoice = Invoice::where('invoice_code', $invoiceCode)->firstOrFail();

        if (! $invoice->canRetry()) {
            return response()->json([
                'message' => 'Invoice tidak dapat di-retry. Sudah expired atau maksimal 3x retry.',
            ], 422);
        }

        try {
            $payment = $this->invoiceService->initiatePayment($invoice);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['payment_url' => $payment['payment_url']]]);
    }

    /**
     * GET /api/v1/payments/finish
     * Redirect URL setelah user selesai di halaman Midtrans Snap (PRD Section 13.6).
     */
    public function finish(Request $request): JsonResponse
    {
        $invoice = Invoice::where('invoice_code', $request->query('invoice_code'))->first();

        return response()->json([
            'data' => [
                'invoice_code' => $invoice?->invoice_code,
                'status'       => $invoice?->status,
                'booking_code' => $invoice?->booking?->booking_code,
            ],
        ]);
    }
}
