@extends('layouts.tenant-admin')

@section('title', 'Invoice & Transaksi')

@section('content')

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-semibold">Total Invoice</span>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $summary['total'] }}</span>
                </div>
                <div class="fw-bold fs-4">{{ number_format($summary['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-semibold">Lunas</span>
                    <span class="badge bg-success-subtle text-success rounded-pill">{{ $summary['paid'] }}</span>
                </div>
                <div class="fw-bold fs-4 text-success">{{ number_format($summary['paid']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-semibold">Menunggu Bayar</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill">{{ $summary['pending'] }}</span>
                </div>
                <div class="fw-bold fs-4 text-warning">{{ number_format($summary['pending']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100" style="border-left: 4px solid var(--bs-success);">
            <div class="card-body">
                <div class="small text-muted fw-semibold mb-2">Total Pendapatan</div>
                <div class="fw-bold fs-5 text-success">Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter & toolbar --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <div class="input-group input-group-sm" style="width:240px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Kode / nama / email..." value="{{ request('search') }}">
        </div>
        <select name="status" class="form-select form-select-sm" style="width:160px">
            <option value="">Semua Status</option>
            @foreach(['pending' => 'Pending', 'awaiting_payment' => 'Menunggu Bayar', 'paid' => 'Lunas', 'expired' => 'Kadaluarsa', 'cancelled' => 'Dibatalkan'] as $val => $label)
                <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
        @if(request('search') || request('status'))
            <a href="{{ route('tenant.admin.invoices.index') }}" class="btn btn-sm btn-outline-danger">Reset</a>
        @endif
    </form>
    <a href="{{ route('tenant.admin.invoices.export', request()->only('status')) }}"
       class="btn btn-sm btn-outline-success">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

{{-- Table --}}
<div class="table-card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Kode Invoice</th>
                <th>Pemesan</th>
                <th>Produk</th>
                <th>Total</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th class="text-end pe-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            @php
                $statusMap = [
                    'paid'             => ['success', 'Lunas'],
                    'pending'          => ['warning',  'Pending'],
                    'awaiting_payment' => ['warning',  'Menunggu Bayar'],
                    'expired'          => ['danger',   'Kadaluarsa'],
                    'cancelled'        => ['secondary','Dibatalkan'],
                ];
                [$sc, $sl] = $statusMap[$invoice->status] ?? ['secondary', $invoice->status];
            @endphp
            <tr>
                <td class="ps-3">
                    <span class="font-monospace small fw-semibold">{{ $invoice->invoice_code }}</span>
                </td>
                <td>
                    <div class="fw-semibold small">{{ $invoice->guest_name }}</div>
                    <div class="text-muted" style="font-size:.75rem">{{ $invoice->guest_email }}</div>
                </td>
                <td class="small text-muted">
                    {{ $invoice->booking?->slot?->product?->name ?? '—' }}
                </td>
                <td class="fw-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                <td>
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} border border-{{ $sc }}-subtle">
                        {{ $sl }}
                    </span>
                </td>
                <td class="small text-muted">{{ $invoice->created_at->format('d M Y') }}</td>
                <td class="text-end pe-3">
                    <a href="{{ route('tenant.admin.invoices.show', $invoice->invoice_code) }}"
                       class="btn btn-sm btn-outline-primary">Detail</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-receipt fs-2 d-block mb-2 opacity-50"></i>
                    Belum ada invoice.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">
    {{ $invoices->links('pagination::bootstrap-5') }}
</div>

@endsection
