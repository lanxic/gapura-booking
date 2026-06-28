@extends('layouts.admin')

@section('title', $offer ? 'Edit Penawaran' : 'Tambah Penawaran')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.offers.index') }}">Penawaran</a></li>
    <li class="breadcrumb-item active">{{ $offer ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')
<div class="card" style="max-width: 600px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ $offer ? route('admin.offers.update', $offer) : route('admin.offers.store') }}">
            @csrf
            @if($offer) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold small">Judul</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $offer?->title) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $offer?->description) }}</textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Tipe Diskon</label>
                    <select name="discount_type" class="form-select" required>
                        <option value="percent" {{ old('discount_type', $offer?->discount_type) === 'percent' ? 'selected' : '' }}>Persen (%)</option>
                        <option value="fixed" {{ old('discount_type', $offer?->discount_type) === 'fixed' ? 'selected' : '' }}>Fixed (Rp)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Nilai Diskon</label>
                    <input type="number" name="discount_value" class="form-control" value="{{ old('discount_value', $offer?->discount_value) }}" min="0" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Berlaku Hingga</label>
                <input type="date" name="valid_until" class="form-control"
                       value="{{ old('valid_until', $offer?->valid_until?->format('Y-m-d')) }}">
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $offer?->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label">Aktif</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ $offer ? 'Update' : 'Simpan' }}</button>
                <a href="{{ route('admin.offers.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
