@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card p-3 d-flex flex-row align-items-center gap-3">
            <div class="stat-icon bg-primary-subtle text-primary">🎫</div>
            <div>
                <div class="text-muted small">Total Booking</div>
                <div class="fw-bold fs-4">{{ number_format($stats['total_bookings']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card p-3 d-flex flex-row align-items-center gap-3">
            <div class="stat-icon bg-success-subtle text-success">💰</div>
            <div>
                <div class="text-muted small">Total Revenue</div>
                <div class="fw-bold fs-4">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card p-3 d-flex flex-row align-items-center gap-3">
            <div class="stat-icon bg-info-subtle text-info">👥</div>
            <div>
                <div class="text-muted small">Total Pelanggan</div>
                <div class="fw-bold fs-4">{{ number_format($stats['total_customers']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card p-3 d-flex flex-row align-items-center gap-3">
            <div class="stat-icon bg-warning-subtle text-warning">📅</div>
            <div>
                <div class="text-muted small">Aktivitas Aktif</div>
                <div class="fw-bold fs-4">{{ number_format($stats['total_activities']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- Revenue Chart --}}
    <div class="col-lg-8">
        <div class="table-card p-3">
            <h6 class="fw-bold mb-3">Revenue 7 Hari Terakhir</h6>
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>

    {{-- Recent Bookings --}}
    <div class="col-lg-4">
        <div class="table-card p-3">
            <h6 class="fw-bold mb-3">Booking Terbaru</h6>
            @forelse($recentBookings as $booking)
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <div class="fw-semibold small font-monospace">{{ $booking->booking_code }}</div>
                    <div class="text-muted" style="font-size:.75rem">{{ $booking->guest_name }}</div>
                </div>
                <span class="badge {{ $booking->status === 'confirmed' ? 'bg-success' : 'bg-secondary' }}">
                    {{ $booking->status }}
                </span>
            </div>
            @empty
                <p class="text-muted small">Belum ada booking.</p>
            @endforelse
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-primary btn-sm w-100 mt-3">Lihat Semua</a>
        </div>
    </div>

</div>

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@push('scripts')
<script>
const ctx = document.getElementById('revenueChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! $revenueChart->pluck('date')->toJson() !!},
        datasets: [{
            label: 'Revenue (Rp)',
            data: {!! $revenueChart->pluck('amount')->toJson() !!},
            backgroundColor: '#3b82f6',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                ticks: {
                    callback: v => 'Rp ' + v.toLocaleString('id-ID')
                }
            }
        }
    }
});
</script>
@endpush

@endsection
