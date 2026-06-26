@extends('layouts.admin')

@section('title', 'Aktivitas')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari aktivitas..." value="{{ request('search') }}">
        <button class="btn btn-sm btn-outline-secondary">Cari</button>
    </form>
    <a href="{{ route('admin.activities.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Aktivitas
    </a>
</div>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Harga Dasar</th>
                <th>Maks Pax</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $activity)
            <tr>
                <td>
                    <div class="fw-semibold">{{ $activity->name }}</div>
                    <div class="text-muted small">{{ $activity->slug }}</div>
                </td>
                <td>{{ $activity->category ?? '-' }}</td>
                <td>Rp {{ number_format($activity->base_price, 0, ',', '.') }}</td>
                <td>{{ $activity->max_pax }}</td>
                <td>
                    <span class="badge {{ $activity->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $activity->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.activities.slots', $activity->id) }}" class="btn btn-xs btn-outline-info btn-sm">Slots</a>
                    <a href="{{ route('admin.activities.edit', $activity) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" class="d-inline"
                          onsubmit="return confirm('Hapus aktivitas ini?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">{{ $activities->links('pagination::bootstrap-5') }}</div>

@endsection
