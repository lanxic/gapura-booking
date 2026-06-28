@extends('layouts.app')

@section('title', 'Tiket ' . $booking->booking_code)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card text-center">
                <div class="card-body p-4">
                    <div class="mb-1">
                        <span class="badge bg-success px-3 py-2">Tiket Confirmed</span>
                    </div>
                    <h4 class="fw-bold mt-3 mb-1">{{ $booking->slot->activity->name }}</h4>
                    <p class="text-muted small">
                        <i class="bi bi-calendar me-1"></i>{{ $booking->slot->date->format('d M Y') }}
                        &nbsp;{{ $booking->slot->start_time->format('H:i') }}
                    </p>
                    <p class="text-muted small mb-4">{{ $booking->pax_count }} peserta</p>

                    {{-- QR Code --}}
                    <div class="qr-container mb-4">
                        {!! $qrSvg !!}
                    </div>

                    <p class="font-monospace fw-bold fs-5 mb-1">{{ $booking->booking_code }}</p>
                    <p class="text-muted small">Tunjukkan QR code ini kepada petugas saat check-in.</p>

                    <hr>
                    <div class="text-start">
                        <div class="row small">
                            <div class="col-5 text-muted">Pemesan</div>
                            <div class="col-7 fw-semibold">{{ $booking->guest_name }}</div>
                        </div>
                        <div class="row small mt-2">
                            <div class="col-5 text-muted">Email</div>
                            <div class="col-7">{{ $booking->guest_email }}</div>
                        </div>
                        <div class="row small mt-2">
                            <div class="col-5 text-muted">Status</div>
                            <div class="col-7">
                                <span class="badge bg-{{ $booking->checked_in_at ? 'info' : 'success' }}">
                                    {{ $booking->checked_in_at ? 'Sudah Check-in' : 'Belum Check-in' }}
                                </span>
                            </div>
                        </div>
                        @if($booking->checked_in_at)
                        <div class="row small mt-2">
                            <div class="col-5 text-muted">Check-in</div>
                            <div class="col-7">{{ $booking->checked_in_at->format('d M Y H:i') }}</div>
                        </div>
                        @endif
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('account.bookings') }}" class="btn btn-outline-secondary btn-sm">
                            ← Kembali ke Daftar Booking
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
