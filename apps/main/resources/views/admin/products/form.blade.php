@extends('layouts.admin')

@section('title', $product ? 'Edit Produk' : 'Tambah Produk')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produk</a></li>
    <li class="breadcrumb-item active">{{ $product ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')

<div class="card" style="max-width: 760px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ $product ? route('admin.products.update', $product) : route('admin.products.store') }}">
            @csrf
            @if($product) @method('PUT') @endif

            <div class="row g-3">

                <div class="col-12">
                    <label class="form-label fw-semibold small">Tenant <span class="text-danger">*</span></label>
                    <select name="tenant_id" class="form-select @error('tenant_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Tenant --</option>
                        @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ old('tenant_id', $product?->tenant_id) == $tenant->id ? 'selected' : '' }}>
                            {{ $tenant->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold small">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $product?->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Tipe</label>
                    <select name="type" class="form-select">
                        <option value="aktivitas" {{ old('type', $product?->type ?? 'aktivitas') === 'aktivitas' ? 'selected' : '' }}>Aktivitas</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                    <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                        <option value="">-- Pilih --</option>
                        <option value="indoor"  {{ old('category', $product?->category) === 'indoor'  ? 'selected' : '' }}>Indoor</option>
                        <option value="outdoor" {{ old('category', $product?->category) === 'outdoor' ? 'selected' : '' }}>Outdoor</option>
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Harga Dasar (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="price_adult" class="form-control @error('price_adult') is-invalid @enderror"
                           value="{{ old('price_adult', $product?->price_adult) }}" min="0" required>
                    @error('price_adult')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Maks Pax <span class="text-danger">*</span></label>
                    <input type="number" name="max_pax" class="form-control @error('max_pax') is-invalid @enderror"
                           value="{{ old('max_pax', $product?->max_pax ?? 10) }}" min="1" required>
                    @error('max_pax')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Min Pax</label>
                    <input type="number" name="min_pax" class="form-control"
                           value="{{ old('min_pax', $product?->min_pax ?? 1) }}" min="1">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Durasi (menit)</label>
                    <input type="number" name="duration_minutes" class="form-control"
                           value="{{ old('duration_minutes', $product?->duration_minutes) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Min Usia</label>
                    <input type="number" name="min_age" class="form-control"
                           value="{{ old('min_age', $product?->min_age) }}" min="0">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Level</label>
                    <select name="level" class="form-select">
                        <option value="">-- Semua Level --</option>
                        <option value="beginner"     {{ old('level', $product?->level) === 'beginner'     ? 'selected' : '' }}>Pemula</option>
                        <option value="intermediate" {{ old('level', $product?->level) === 'intermediate' ? 'selected' : '' }}>Menengah</option>
                        <option value="advanced"     {{ old('level', $product?->level) === 'advanced'     ? 'selected' : '' }}>Mahir</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   {{ old('status', $product?->status ?? 'active')   === 'active'   ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $product?->status)                === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        <option value="archived" {{ old('status', $product?->status)                === 'archived' ? 'selected' : '' }}>Diarsip</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="5">{{ old('description', $product?->description) }}</textarea>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                               {{ old('is_featured', $product?->is_featured) ? 'checked' : '' }}>
                        <label class="form-check-label">Unggulan (Featured)</label>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">
                        {{ $product ? 'Update Produk' : 'Buat Produk' }}
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
