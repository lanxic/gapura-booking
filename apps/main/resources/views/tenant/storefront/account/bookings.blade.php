@extends('layouts.tenant-storefront')

@section('title', 'Booking Saya')

@section('content')
<div class="container py-5">
    <h4 class="fw-bold mb-4">Booking Saya</h4>

    @forelse($bookings as $booking)
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="fw-semibold">{{ $booking->slot?->product?->name ?? '—' }}</div>
                    <div class="small text-muted">
                        {{ $booking->slot?->date ? \Carbon\Carbon::parse($booking->slot->date)->translatedFormat('d F Y') : '—' }}
                        @if($booking->slot?->start_time)
                            · {{ \Carbon\Carbon::parse($booking->slot->start_time)->format('H:i') }}
                        @endif
                    </div>
                    <div class="small text-muted mt-1">Kode: <span class="fw-mono">{{ $booking->booking_code }}</span></div>
                </div>
                <div class="text-end">
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
                    <span class="badge bg-{{ $statusColor }} mb-2">{{ ucfirst($booking->status->value ?? $booking->status) }}</span>
                    <br>
                    <a href="{{ route('tenant.account.booking.detail', $booking->booking_code) }}"
                       class="btn btn-sm btn-outline-primary">Detail</a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-ticket-perforated fs-1 d-block mb-3 opacity-50"></i>
        <p>Belum ada booking.</p>
        <a href="{{ route('tenant.products.index') }}" class="btn btn-primary">Lihat Produk</a>
    </div>
    @endforelse

    <div class="d-flex justify-content-center mt-3">
        {{ $bookings->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
