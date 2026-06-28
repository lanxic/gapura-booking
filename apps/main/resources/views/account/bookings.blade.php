@extends('layouts.app')

@section('title', 'Booking Saya')

@section('content')
<div class="container py-5">
    <h2 class="fw-bold mb-4">Booking Saya</h2>

    @forelse($bookings as $booking)
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="fw-bold font-monospace mb-1">{{ $booking->booking_code }}</div>
                <div class="fw-semibold">{{ $booking->slot->activity->name ?? '-' }}</div>
                <div class="text-muted small">
                    <i class="bi bi-calendar me-1"></i>{{ $booking->slot->date->format('d M Y') }}
                    &nbsp;{{ $booking->slot->start_time->format('H:i') }}
                    &nbsp;·&nbsp;{{ $booking->pax_count }} peserta
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge {{ $booking->status === 'confirmed' ? 'bg-success' : 'bg-secondary' }} px-3 py-2">
                    {{ ucfirst($booking->status) }}
                </span>
                <a href="{{ route('account.booking.detail', $booking->booking_code) }}" class="btn btn-outline-primary btn-sm">
                    Lihat Tiket
                </a>
            </div>
        </div>
    </div>
    @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-ticket-perforated fs-1 d-block mb-3"></i>
            Belum ada booking.
            <br>
            <a href="{{ route('activities.index') }}" class="btn btn-primary mt-3">Jelajahi Aktivitas</a>
        </div>
    @endforelse

    @if($bookings->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $bookings->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
