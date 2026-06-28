@extends('layouts.tenant-admin')

@section('title', 'Detail Invoice')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tenant.admin.invoices.index') }}">Invoice</a></li>
    <li class="breadcrumb-item active">{{ $invoice->invoice_code }}</li>
@endsection

@section('content')
<div class="row g-4" style="max-width:900px">

    {{-- Left: Invoice detail --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="font-monospace fw-bold">{{ $invoice->invoice_code }}</span>
                    @php
                        $statusMap = [
                            'paid'             => ['success', 'Lunas'],
                            'pending'          => ['warning',  'Pending'],
                            'awaiting_payment' => ['warning',  'Menunggu Bayar'],
                            'expired'          => ['danger',   'Kadaluarsa'],
                            'cancelled'        => ['secondary','Dibatalkan'],
                        ];
                        [$sc, $sl] = $statusMap[$invoice->status] ?? ['secondary', $invoice->status];
                    @endphp
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} border border-{{ $sc }}-subtle ms-2">{{ $sl }}</span>
                </div>
                <div class="small text-muted">{{ $invoice->created_at->format('d M Y, H:i') }}</div>
            </div>
            <div class="card-body p-4">

                {{-- Pemesan --}}
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1">Pemesan</div>
                        <div class="fw-semibold">{{ $invoice->guest_name }}</div>
                        <div class="small text-muted">{{ $invoice->guest_email }}</div>
                        @if($invoice->guest_phone)
                            <div class="small text-muted">{{ $invoice->guest_phone }}</div>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1">Jatuh Tempo</div>
                        <div class="fw-semibold">{{ $invoice->due_at?->format('d M Y, H:i') ?? '—' }}</div>
                        @if($invoice->paid_at)
                            <div class="small text-success">
                                <i class="bi bi-check-circle me-1"></i>Dibayar {{ $invoice->paid_at->format('d M Y, H:i') }}
                            </div>
                        @endif
                    </div>
                </div>

                <hr>

                {{-- Items --}}
                <div class="mb-3">
                    @foreach($invoice->items ?? [] as $item)
                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                        <div>
                            <div class="fw-semibold small">{{ $item['name'] ?? '' }}</div>
                            @if(!empty($item['qty']) && $item['qty'] > 1)
                                <div class="text-muted" style="font-size:.75rem">× {{ $item['qty'] }}</div>
                            @endif
                        </div>
                        <div class="fw-semibold small">Rp {{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                    @endforeach
                </div>

                @if($invoice->discount_amount > 0)
                <div class="d-flex justify-content-between small text-success mb-2">
                    <span>Diskon</span>
                    <span>− Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                </div>
                @endif

                <div class="d-flex justify-content-between fw-bold pt-2 border-top">
                    <span>Total Pembayaran</span>
                    <span class="text-primary fs-5">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>

            </div>
        </div>
    </div>

    {{-- Right: Booking info --}}
    <div class="col-lg-4">

        @if($invoice->booking)
        @php $booking = $invoice->booking; @endphp
        <div class="card mb-3">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-ticket-perforated me-2 text-primary"></i>Booking</h6>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless mb-0 small">
                    <tr>
                        <td class="text-muted ps-0">Kode</td>
                        <td class="fw-semibold font-monospace pe-0">{{ $booking->booking_code }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Produk</td>
                        <td class="pe-0">{{ $booking->slot?->product?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Tanggal</td>
                        <td class="pe-0">{{ $booking->slot?->date?->format('d M Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Peserta</td>
                        <td class="pe-0">{{ $booking->pax }} orang</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Status</td>
                        <td class="pe-0">
                            @php
                                $bColor = match($booking->status->value ?? $booking->status) {
                                    'confirmed' => 'success',
                                    'pending'   => 'warning',
                                    'attended'  => 'info',
                                    'cancelled','no_show' => 'danger',
                                    default     => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $bColor }}-subtle text-{{ $bColor }} border border-{{ $bColor }}-subtle">
                                {{ ucfirst($booking->status->value ?? $booking->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
                <a href="{{ route('tenant.admin.bookings.show', $booking->id) }}"
                   class="btn btn-sm btn-outline-primary w-100 mt-3">
                    <i class="bi bi-arrow-right me-1"></i>Lihat Detail Booking
                </a>
            </div>
        </div>
        @endif

        {{-- Payment info --}}
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-credit-card me-2 text-success"></i>Pembayaran</h6>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless mb-0 small">
                    <tr>
                        <td class="text-muted ps-0">Gateway</td>
                        <td class="fw-semibold pe-0">{{ $invoice->gateway ?? '—' }}</td>
                    </tr>
                    @if($invoice->gateway_order_id)
                    <tr>
                        <td class="text-muted ps-0">Order ID</td>
                        <td class="font-monospace pe-0" style="font-size:.75rem">{{ $invoice->gateway_order_id }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted ps-0">Subtotal</td>
                        <td class="pe-0">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td class="text-muted ps-0">Diskon</td>
                        <td class="text-success pe-0">− Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted ps-0">Total</td>
                        <td class="fw-bold text-primary pe-0">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
