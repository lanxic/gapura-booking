@extends('layouts.admin')

@section('title', $activity ? 'Edit Aktivitas' : 'Tambah Aktivitas')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.activities.index') }}">Aktivitas</a></li>
    <li class="breadcrumb-item active">{{ $activity ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')

<div class="card" style="max-width: 760px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ $activity ? route('admin.activities.update', $activity) : route('admin.activities.store') }}">
            @csrf
            @if($activity) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold small">Nama Aktivitas</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $activity?->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Kategori</label>
                    <input type="text" name="category" class="form-control" value="{{ old('category', $activity?->category) }}" placeholder="Contoh: Outdoor, Kuliner">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Harga Dasar (Rp)</label>
                    <input type="number" name="base_price" class="form-control @error('base_price') is-invalid @enderror"
                           value="{{ old('base_price', $activity?->base_price) }}" min="0" required>
                    @error('base_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Maks Pax</label>
                    <input type="number" name="max_pax" class="form-control @error('max_pax') is-invalid @enderror"
                           value="{{ old('max_pax', $activity?->max_pax ?? 10) }}" min="1" required>
                    @error('max_pax')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Durasi (menit)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $activity?->duration_minutes) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Lokasi</label>
                    <input type="text" name="location" class="form-control" value="{{ old('location', $activity?->location) }}">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small">Deskripsi Singkat</label>
                    <input type="text" name="short_description" class="form-control" value="{{ old('short_description', $activity?->short_description) }}">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small">Deskripsi Lengkap</label>
                    <textarea name="description" class="form-control" rows="5">{{ old('description', $activity?->description) }}</textarea>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $activity?->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">Aktif</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                               {{ old('is_featured', $activity?->is_featured) ? 'checked' : '' }}>
                        <label class="form-check-label">Unggulan (Featured)</label>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">
                        {{ $activity ? 'Update Aktivitas' : 'Buat Aktivitas' }}
                    </button>
                    <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
