@extends('layouts.admin')

@section('title', 'Pelanggan')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama / email..." value="{{ request('search') }}">
        <button class="btn btn-sm btn-outline-secondary">Cari</button>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.customers.export') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
    </div>
</div>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>No HP</th>
                <th>Status</th>
                <th>Bergabung</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
            <tr class="{{ $customer->trashed() ? 'table-secondary' : '' }}">
                <td class="fw-semibold">{{ $customer->name }} @if($customer->trashed()) <span class="badge bg-secondary">Deleted</span> @endif</td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone ?? '-' }}</td>
                <td>
                    <span class="badge {{ $customer->is_active ? 'bg-success' : 'bg-warning text-dark' }}">
                        {{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="small">{{ $customer->created_at->format('d M Y') }}</td>
                <td class="text-end d-flex gap-1 justify-content-end">
                    @if(!$customer->trashed())
                        <form method="POST" action="{{ route('admin.customers.toggle-active', $customer->id) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-warning">
                                {{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.customers.destroy', $customer->id) }}"
                              onsubmit="return confirm('Hapus customer ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.customers.restore', $customer->id) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-success">Pulihkan</button>
                        </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada customer.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">{{ $customers->links('pagination::bootstrap-5') }}</div>

@endsection
