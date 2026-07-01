@extends('layouts.admin')

@section('title', 'Manajemen Tenant')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari tenant..." value="{{ request('search') }}">
        <button class="btn btn-sm btn-outline-secondary">Cari</button>
    </form>
    <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Tenant
    </a>
</div>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nama</th>
                <th>Subdomain</th>
                <th>Prefix Invoice</th>
                <th>Produk</th>
                <th>Booking</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $tenant)
            <tr>
                <td>
                    <div class="fw-semibold">{{ $tenant->name }}</div>
                    @if($tenant->domain)
                    <div class="text-muted small">{{ $tenant->domain }}</div>
                    @endif
                </td>
                <td>
                    <code class="small">{{ $tenant->slug }}.{{ parse_url(config('app.url'), PHP_URL_HOST) }}</code>
                </td>
                <td><span class="badge bg-primary">{{ $tenant->invoice_prefix }}</span></td>
                <td>{{ $tenant->products_count }}</td>
                <td>{{ $tenant->bookings_count }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.tenants.toggle', $tenant) }}">
                        @csrf @method('PATCH')
                        <button type="button"
                                class="badge border-0 {{ $tenant->is_active ? 'bg-success' : 'bg-secondary' }}"
                                onclick="confirmModal('{{ $tenant->is_active ? 'Nonaktifkan' : 'Aktifkan' }} tenant ini?', () => this.closest('form').submit())">
                            {{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </form>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmModal('Hapus tenant ini? Semua data terkait akan ikut terhapus.', () => this.closest('form').submit(), 'Ya, Hapus')">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada tenant.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">{{ $tenants->links('pagination::bootstrap-5') }}</div>

@endsection
