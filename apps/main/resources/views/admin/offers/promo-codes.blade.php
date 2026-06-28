@extends('layouts.admin')

@section('title', 'Promo Codes — ' . $offer->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.offers.index') }}">Penawaran</a></li>
    <li class="breadcrumb-item active">Promo Codes</li>
@endsection

@section('content')

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Tambah Promo Code</h6>
                <form method="POST" action="{{ route('admin.offers.promo-codes.store', $offer) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kode</label>
                        <input type="text" name="code" class="form-control text-uppercase" placeholder="PROMO2024" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Maks Penggunaan</label>
                        <input type="number" name="max_uses" class="form-control" placeholder="Kosongkan = unlimited" min="1">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Tambah</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="table-card">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Digunakan</th>
                        <th>Maks</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promoCodes as $promo)
                    <tr>
                        <td class="font-monospace fw-bold">{{ $promo->code }}</td>
                        <td>{{ $promo->used_count }}</td>
                        <td>{{ $promo->max_uses ?? '∞' }}</td>
                        <td>
                            <span class="badge {{ $promo->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $promo->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.promo-codes.toggle', $promo) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-{{ $promo->is_active ? 'warning' : 'success' }}">
                                    {{ $promo->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada promo code.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $promoCodes->links('pagination::bootstrap-5') }}</div>
    </div>
</div>

@endsection
