@extends('layouts.admin')

@section('title', 'Booking')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari kode / nama..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" style="width: auto;">
            <option value="">Semua Status</option>
            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            <option value="no_show" {{ request('status') === 'no_show' ? 'selected' : '' }}>No Show</option>
        </select>
        <button class="btn btn-sm btn-outline-secondary">Filter</button>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.bookings.export') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#walkinModal">
            <i class="bi bi-plus-lg me-1"></i>Walk-in Booking
        </button>
    </div>
</div>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Aktivitas</th>
                <th>Tanggal</th>
                <th>Pax</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
            <tr>
                <td class="font-monospace small">{{ $booking->booking_code }}</td>
                <td>
                    <div class="fw-semibold">{{ $booking->guest_name }}</div>
                    <div class="text-muted small">{{ $booking->guest_email }}</div>
                </td>
                <td>{{ $booking->slot?->activity?->name ?? '-' }}</td>
                <td class="small">{{ $booking->slot?->date?->format('d M Y') }}</td>
                <td>{{ $booking->pax_count }}</td>
                <td>
                    <span class="badge {{ match($booking->status) {
                        'confirmed' => 'bg-success',
                        'cancelled' => 'bg-danger',
                        'no_show'   => 'bg-warning text-dark',
                        default     => 'bg-secondary'
                    } }}">{{ $booking->status }}</span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada booking.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">{{ $bookings->links('pagination::bootstrap-5') }}</div>

{{-- Walk-in Modal --}}
<div class="modal fade" id="walkinModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Walk-in Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.bookings.store-manual') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Slot Aktivitas</label>
                        <select name="slot_id" class="form-select" required>
                            <option value="">Pilih slot...</option>
                            @foreach($slots as $slot)
                                <option value="{{ $slot->id }}">
                                    {{ $slot->activity?->name }} — {{ $slot->date->format('d M Y') }} {{ $slot->start_time->format('H:i') }}
                                    ({{ $slot->available_pax }} tersedia)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama</label>
                        <input type="text" name="guest_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="email" name="guest_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">No HP</label>
                        <input type="text" name="guest_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Jumlah Pax</label>
                        <input type="number" name="pax_count" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Metode Pembayaran</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash / Tunai</option>
                            <option value="bank_transfer">Transfer Bank</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
