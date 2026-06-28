@extends('layouts.tenant-admin')

@section('title', 'Dashboard')

@section('content')

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-ticket-perforated"></i></div>
                <div>
                    <div class="text-muted small">Total Booking</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['total_bookings']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-currency-dollar"></i></div>
                <div>
                    <div class="text-muted small">Total Pendapatan</div>
                    <div class="fw-bold fs-5">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="text-muted small">Produk Aktif</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['total_products']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="mb-0 fw-semibold">Pendapatan 7 Hari Terakhir</h6>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Booking Terbaru</h6>
                <a href="{{ route('tenant.admin.bookings.index') }}" class="small text-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentBookings as $booking)
                <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold small">{{ $booking->guest_name }}</div>
                        <div class="text-muted" style="font-size:.75rem">{{ $booking->slot?->product?->name }}</div>
                    </div>
                    <span class="badge {{ $booking->status === 'confirmed' ? 'bg-success' : 'bg-secondary' }} small">
                        {{ ucfirst($booking->status) }}
                    </span>
                </div>
                @empty
                <div class="text-center text-muted py-4 small">Belum ada booking.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json($revenueChart->pluck('date')),
        datasets: [{
            label: 'Pendapatan',
            data: @json($revenueChart->pluck('amount')),
            backgroundColor: 'rgba(59,130,246,.5)',
            borderColor: 'rgba(59,130,246,1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
});
</script>
@endpush
