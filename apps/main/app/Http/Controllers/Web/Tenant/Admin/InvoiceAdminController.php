<?php

namespace App\Http\Controllers\Web\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class InvoiceAdminController extends Controller
{
    public function index(string $tenantSlug, Request $request)
    {
        $tenant = App::make('current_tenant');

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->with('booking.slot.product')
            ->when($request->search, fn($q) =>
                $q->where('invoice_code', 'like', "%{$request->search}%")
                  ->orWhere('guest_name',  'like', "%{$request->search}%")
                  ->orWhere('guest_email', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total'   => Invoice::where('tenant_id', $tenant->id)->count(),
            'paid'    => Invoice::where('tenant_id', $tenant->id)->where('status', 'paid')->count(),
            'pending' => Invoice::where('tenant_id', $tenant->id)->whereIn('status', ['pending', 'awaiting_payment'])->count(),
            'revenue' => Invoice::where('tenant_id', $tenant->id)->where('status', 'paid')->sum('total_amount'),
        ];

        return view('tenant.admin.invoices.index', compact('invoices', 'summary', 'tenant'));
    }

    public function show(string $tenantSlug, string $code)
    {
        $tenant  = App::make('current_tenant');
        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->where('invoice_code', $code)
            ->with('booking.slot.product')
            ->firstOrFail();

        return view('tenant.admin.invoices.show', compact('invoice', 'tenant'));
    }

    public function export(string $tenantSlug, Request $request)
    {
        $tenant   = App::make('current_tenant');
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $csv = "Invoice Code,Pemesan,Email,Produk,Total,Status,Tanggal\n";
        foreach ($invoices as $inv) {
            $productName = $inv->booking?->slot?->product?->name ?? '-';
            $csv .= implode(',', [
                $inv->invoice_code,
                '"' . $inv->guest_name . '"',
                $inv->guest_email,
                '"' . $productName . '"',
                $inv->total_amount,
                $inv->status,
                $inv->created_at->format('Y-m-d'),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invoices-' . $tenant->slug . '.csv"',
        ]);
    }
}
