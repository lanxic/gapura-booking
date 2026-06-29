@extends('layouts.admin')

@section('title', 'Produk')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari produk..." value="{{ request('search') }}">
        <button class="btn btn-sm btn-outline-secondary">Cari</button>
    </form>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Produk
    </a>
</div>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nama</th>
                <th>Tenant</th>
                <th>Tipe</th>
                <th>Harga Dasar</th>
                <th>Maks Pax</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td>
                    <div class="fw-semibold">{{ $product->name }}</div>
                    <div class="text-muted small">{{ $product->slug }}</div>
                </td>
                <td>
                    <span class="badge bg-secondary-subtle text-secondary">{{ $product->tenant?->name ?? '-' }}</span>
                </td>
                <td><span class="badge bg-info-subtle text-info">{{ $product->type }}</span></td>
                <td>Rp {{ number_format($product->price_adult, 0, ',', '.') }}</td>
                <td>{{ $product->max_pax }}</td>
                <td>
                    <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.products.slots', $product->id) }}" class="btn btn-xs btn-outline-info btn-sm">Slots</a>
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline"
                          onsubmit="return confirm('Hapus produk ini?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada produk.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">{{ $products->links('pagination::bootstrap-5') }}</div>

@endsection
