<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function show(string $code)
    {
        $user    = Auth::guard('web')->user();
        $invoice = Invoice::where('invoice_code', $code)
            ->when($user, fn($q) => $q->where('customer_id', $user->id))
            ->with('booking')
            ->firstOrFail();

        return view('invoice.show', compact('invoice'));
    }

    public function retry(Request $request, string $code)
    {
        $user    = Auth::guard('web')->user();
        $invoice = Invoice::where('invoice_code', $code)
            ->where('customer_id', $user->id)
            ->firstOrFail();

        try {
            $snapToken = $this->invoiceService->retryPayment($invoice);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('snap_token', $snapToken);
    }

    public function finish(Request $request)
    {
        $code = $request->get('order_id');

        if (!$code) {
            return redirect()->route('home');
        }

        return redirect()->route('invoice.show', $code);
    }
}
