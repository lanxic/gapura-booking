@extends('layouts.admin')

@section('title', 'Penawaran')

@section('content')

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.offers.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Penawaran
    </a>
</div>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Judul</th>
                <th>Diskon</th>
                <th>Berlaku Hingga</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($offers as $offer)
            <tr>
                <td class="fw-semibold">{{ $offer->title }}</td>
                <td>
                    {{ $offer->discount_type === 'percent'
                        ? $offer->discount_value . '%'
                        : 'Rp ' . number_format($offer->discount_value, 0, ',', '.') }}
                </td>
                <td>{{ $offer->valid_until?->format('d M Y') ?? '—' }}</td>
                <td>
                    <span class="badge {{ $offer->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $offer->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.offers.promo-codes', $offer) }}" class="btn btn-sm btn-outline-info">Promo Codes</a>
                    <a href="{{ route('admin.offers.edit', $offer) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <form method="POST" action="{{ route('admin.offers.destroy', $offer) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmModal('Hapus penawaran ini?', () => this.closest('form').submit())">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada penawaran.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">{{ $offers->links('pagination::bootstrap-5') }}</div>

@endsection
