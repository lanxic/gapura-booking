@extends('layouts.admin')

@section('title', 'Detail Booking')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Booking</a></li>
    <li class="breadcrumb-item active">{{ $booking->booking_code }}</li>
@endsection

@section('content')
<div class="row g-4" style="max-width: 800px;">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between mb-4">
                    <div>
                        <h5 class="fw-bold font-monospace mb-1">{{ $booking->booking_code }}</h5>
                        <span class="badge {{ match($booking->status) {
                            'confirmed' => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default     => 'bg-secondary'
                        } }} px-3 py-2">{{ $booking->status }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.bookings.update', $booking->id) }}" class="d-flex gap-2">
                        @csrf @method('PUT')
                        <select name="status" class="form-select form-select-sm">
                            <option value="confirmed" {{ $booking->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="cancelled" {{ $booking->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="no_show" {{ $booking->status === 'no_show' ? 'selected' : '' }}>No Show</option>
                        </select>
                        <button class="btn btn-sm btn-primary">Update</button>
                    </form>
                </div>

                <div class="row g-3 small">
                    <div class="col-md-6">
                        <div class="text-muted mb-1">Aktivitas</div>
                        <div class="fw-semibold">{{ $booking->slot?->activity?->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted mb-1">Jadwal</div>
                        <div class="fw-semibold">{{ $booking->slot?->date?->format('d M Y') }} {{ $booking->slot?->start_time?->format('H:i') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted mb-1">Pemesan</div>
                        <div class="fw-semibold">{{ $booking->guest_name }}</div>
                        <div class="text-muted">{{ $booking->guest_email }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted mb-1">Peserta</div>
                        <div class="fw-semibold">{{ $booking->pax_count }} orang</div>
                    </div>
                    @if($booking->checked_in_at)
                    <div class="col-md-6">
                        <div class="text-muted mb-1">Check-in</div>
                        <div class="fw-semibold">{{ $booking->checked_in_at->format('d M Y H:i') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
