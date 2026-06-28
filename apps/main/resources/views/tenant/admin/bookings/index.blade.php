@extends('layouts.tenant-admin')

@section('title', 'Booking')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari kode / nama..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <option value="">Semua Status</option>
            @foreach(['pending','confirmed','attended','cancelled','no_show'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-outline-secondary">Filter</button>
    </form>
</div>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Kode</th>
                <th>Pelanggan</th>
                <th>Produk / Slot</th>
                <th>Pax</th>
                <th>Total</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
            <tr>
                <td><code class="small">{{ $booking->booking_code }}</code></td>
                <td>
                    <div class="fw-semibold small">{{ $booking->guest_name }}</div>
                    <div class="text-muted" style="font-size:.75rem">{{ $booking->guest_email }}</div>
                </td>
                <td>
                    <div class="small">{{ $booking->slot?->product?->name }}</div>
                    <div class="text-muted" style="font-size:.75rem">{{ $booking->slot?->date?->format('d M Y') }}</div>
                </td>
                <td>{{ $booking->pax_count }}</td>
                <td>Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</td>
                <td>
                    @php $colors = ['confirmed'=>'success','attended'=>'primary','cancelled'=>'danger','no_show'=>'warning','pending'=>'secondary']; @endphp
                    <span class="badge bg-{{ $colors[$booking->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$booking->status)) }}</span>
                </td>
                <td class="text-end">
                    <a href="{{ route('tenant.admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada booking.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">{{ $bookings->links('pagination::bootstrap-5') }}</div>

@endsection
