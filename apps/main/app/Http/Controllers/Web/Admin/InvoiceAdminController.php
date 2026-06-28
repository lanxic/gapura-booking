<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceAdminController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with('booking')
            ->when($request->search, fn($q) =>
                $q->where('invoice_code', 'like', "%{$request->search}%")
                  ->orWhere('guest_email', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(string $code)
    {
        $invoice = Invoice::where('invoice_code', $code)->with('booking')->firstOrFail();
        return view('admin.invoices.show', compact('invoice'));
    }

    public function export(Request $request)
    {
        $invoices = Invoice::when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $csv = "Invoice Code,Name,Email,Total,Status,Created\n";
        foreach ($invoices as $inv) {
            $csv .= implode(',', [
                $inv->invoice_code,
                $inv->guest_name,
                $inv->guest_email,
                $inv->total_amount,
                $inv->status,
                $inv->created_at->format('Y-m-d'),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invoices.csv"',
        ]);
    }
}
