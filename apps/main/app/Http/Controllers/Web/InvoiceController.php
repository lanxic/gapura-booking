<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function show(string $tenantSlug, string $code)
    {
        $user    = Auth::guard('web')->user();
        $invoice = Invoice::where('invoice_code', $code)
            ->when($user, fn($q) => $q->where('customer_id', $user->id))
            ->with('booking')
            ->firstOrFail();

        $gateway = $invoice->gateway
            ? PaymentGateway::where('name', $invoice->gateway)->first()
            : null;

        return view('tenant.storefront.invoice.show', compact('invoice', 'gateway'));
    }

    public function retry(string $tenantSlug, Request $request, string $code)
    {
        $user    = Auth::guard('web')->user();
        $invoice = Invoice::where('invoice_code', $code)
            ->where('customer_id', $user->id)
            ->firstOrFail();

        try {
            $snapToken = $this->invoiceService->initiateSnapToken($invoice);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('snap_token', $snapToken);
    }

    public function finish(Request $request)
    {
        $code = $request->get('order_id');

        if (!$code) {
            return redirect()->route('tenant.home');
        }

        return redirect()->route('tenant.invoice.show', $code);
    }
}
