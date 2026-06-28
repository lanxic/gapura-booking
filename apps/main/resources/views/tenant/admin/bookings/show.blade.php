@extends('layouts.tenant-admin')

@section('title', 'Detail Booking — ' . $booking->booking_code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tenant.admin.bookings.index') }}">Booking</a></li>
    <li class="breadcrumb-item active">{{ $booking->booking_code }}</li>
@endsection

@section('content')

<div class="row g-4" style="max-width: 800px">

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Info Booking</h6>
                @php $colors = ['confirmed'=>'success','attended'=>'primary','cancelled'=>'danger','no_show'=>'warning','pending'=>'secondary']; @endphp
                <span class="badge bg-{{ $colors[$booking->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$booking->status)) }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="text-muted small">Kode Booking</div>
                        <code>{{ $booking->booking_code }}</code>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Produk</div>
                        <div class="fw-semibold">{{ $booking->slot?->product?->name }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Tanggal Slot</div>
                        <div>{{ $booking->slot?->date?->format('d M Y') }} {{ $booking->slot?->start_time?->format('H:i') }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Jumlah Pax</div>
                        <div>{{ $booking->pax_count }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Nama Pemesan</div>
                        <div>{{ $booking->guest_name }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Email</div>
                        <div>{{ $booking->guest_email }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Total Pembayaran</div>
                        <div class="fw-bold">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Status Bayar</div>
                        <span class="badge bg-{{ $booking->payment_status === 'paid' ? 'success' : 'warning' }}">
                            {{ ucfirst($booking->payment_status) }}
                        </span>
                    </div>
                </div>

                <hr>
                <h6 class="fw-semibold mb-3">Update Status</h6>
                <form method="POST" action="{{ route('tenant.admin.bookings.update', $booking->id) }}" class="d-flex gap-2 align-items-end">
                    @csrf @method('PUT')
                    <div>
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            @foreach(['pending','confirmed','attended','cancelled','no_show'] as $s)
                            <option value="{{ $s }}" {{ $booking->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection
