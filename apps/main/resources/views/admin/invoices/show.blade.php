@extends('layouts.admin')

@section('title', 'Detail Invoice')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoice</a></li>
    <li class="breadcrumb-item active">{{ $invoice->invoice_code }}</li>
@endsection

@section('content')
<div style="max-width: 640px;">
    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between mb-4">
                <div>
                    <h5 class="fw-bold font-monospace mb-1">{{ $invoice->invoice_code }}</h5>
                    <span class="badge {{ match($invoice->status) {
                        'paid'     => 'bg-success',
                        'pending','awaiting_payment' => 'bg-warning text-dark',
                        default    => 'bg-danger'
                    } }} px-3 py-2">{{ $invoice->status }}</span>
                </div>
            </div>

            <div class="row g-3 small mb-4">
                <div class="col-md-6">
                    <div class="text-muted mb-1">Pemesan</div>
                    <div class="fw-semibold">{{ $invoice->guest_name }}</div>
                    <div class="text-muted">{{ $invoice->guest_email }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted mb-1">Jatuh Tempo</div>
                    <div class="fw-semibold">{{ $invoice->due_at?->format('d M Y H:i') ?? '-' }}</div>
                </div>
            </div>

            <hr>

            @foreach($invoice->items ?? [] as $item)
            <div class="d-flex justify-content-between mb-2 small">
                <span>{{ $item['name'] ?? '' }} × {{ $item['qty'] ?? 1 }}</span>
                <span>Rp {{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}</span>
            </div>
            @endforeach

            @if($invoice->discount_amount > 0)
            <div class="d-flex justify-content-between mb-2 small text-success">
                <span>Diskon</span>
                <span>− Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif

            <hr>
            <div class="d-flex justify-content-between fw-bold">
                <span>Total</span>
                <span class="text-primary">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
            </div>

            @if($invoice->booking)
            <div class="alert alert-success py-2 small mt-4">
                <i class="bi bi-check-circle me-1"></i>
                Booking: <strong>{{ $invoice->booking->booking_code }}</strong>
                <a href="{{ route('admin.bookings.show', $invoice->booking->id) }}" class="ms-2 alert-link">Lihat Booking</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
