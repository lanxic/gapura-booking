@extends('layouts.admin')

@section('title', 'Invoice')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari kode / email..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" style="width: auto;">
            <option value="">Semua Status</option>
            @foreach(['pending','awaiting_payment','paid','expired','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-outline-secondary">Filter</button>
    </form>
    <a href="{{ route('admin.invoices.export') }}" class="btn btn-sm btn-outline-success">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Kode</th>
                <th>Pemesan</th>
                <th>Total</th>
                <th>Status</th>
                <th>Jatuh Tempo</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td class="font-monospace small">{{ $invoice->invoice_code }}</td>
                <td>
                    <div class="fw-semibold">{{ $invoice->guest_name }}</div>
                    <div class="text-muted small">{{ $invoice->guest_email }}</div>
                </td>
                <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                <td>
                    <span class="badge {{ match($invoice->status) {
                        'paid'             => 'bg-success',
                        'pending','awaiting_payment' => 'bg-warning text-dark',
                        'expired','cancelled' => 'bg-danger',
                        default            => 'bg-secondary'
                    } }}">{{ $invoice->status }}</span>
                </td>
                <td class="small">{{ $invoice->due_at?->format('d M Y H:i') ?? '-' }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.invoices.show', $invoice->invoice_code) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada invoice.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">{{ $invoices->links('pagination::bootstrap-5') }}</div>

@endsection
