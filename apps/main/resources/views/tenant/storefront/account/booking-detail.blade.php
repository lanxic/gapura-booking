@extends('layouts.tenant-storefront')

@section('title', 'Detail Booking')

@section('content')
<div class="container py-5" style="max-width:640px">
    <a href="{{ route('tenant.account.bookings') }}" class="btn btn-sm btn-outline-secondary mb-4">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h5 class="fw-bold mb-0">{{ $booking->slot?->product?->name ?? '—' }}</h5>
                @php
                    $statusColor = match($booking->status->value ?? $booking->status) {
                        'confirmed'  => 'success',
                        'pending'    => 'warning',
                        'attended'   => 'info',
                        'cancelled'  => 'danger',
                        'no_show'    => 'secondary',
                        default      => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $statusColor }}">{{ ucfirst($booking->status->value ?? $booking->status) }}</span>
            </div>

            <table class="table table-sm table-borderless mb-0 small">
                <tr>
                    <td class="text-muted" style="width:40%">Kode Booking</td>
                    <td class="fw-semibold">{{ $booking->booking_code }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Tanggal</td>
                    <td>{{ $booking->slot?->date ? \Carbon\Carbon::parse($booking->slot->date)->translatedFormat('d F Y') : '—' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Waktu</td>
                    <td>
                        @if($booking->slot?->start_time)
                            {{ \Carbon\Carbon::parse($booking->slot->start_time)->format('H:i') }}
                            @if($booking->slot?->end_time)
                                – {{ \Carbon\Carbon::parse($booking->slot->end_time)->format('H:i') }}
                            @endif
                        @else
                            —
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Nama Pemesan</td>
                    <td>{{ $booking->guest_name }}</td>
                </tr>
                <tr>
                    <td class="text-muted">No HP</td>
                    <td>{{ $booking->guest_phone ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Peserta</td>
                    <td>{{ $booking->pax }} orang</td>
                </tr>
                @if($booking->invoice)
                <tr>
                    <td class="text-muted">Total</td>
                    <td class="fw-semibold">Rp {{ number_format($booking->invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Status Bayar</td>
                    <td>
                        <span class="badge {{ $booking->invoice->status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $booking->invoice->status === 'paid' ? 'Lunas' : 'Belum Bayar' }}
                        </span>
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- QR Code --}}
    @if($booking->status->value === 'confirmed' || $booking->status === 'confirmed')
    <div class="card shadow-sm mb-3 text-center">
        <div class="card-body py-4">
            <p class="small text-muted mb-3">Tunjukkan QR code ini kepada petugas saat check-in</p>
            <div class="d-flex justify-content-center">{!! $qrSvg !!}</div>
            <div class="mt-2 text-muted small">{{ $booking->booking_code }}</div>
        </div>
    </div>
    @endif

    {{-- Invoice link --}}
    @if($booking->invoice)
    <a href="{{ route('tenant.invoice.show', $booking->invoice->invoice_code) }}"
       class="btn btn-outline-primary w-100">
        <i class="bi bi-receipt me-1"></i>Lihat Invoice
    </a>
    @endif
</div>
@endsection
