@extends('layouts.tenant-admin')

@section('title', $product ? 'Edit Produk' : 'Tambah Produk')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tenant.admin.products.index') }}">Produk</a></li>
    <li class="breadcrumb-item active">{{ $product ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')

<form method="POST" action="{{ $product ? route('tenant.admin.products.update', $product) : route('tenant.admin.products.store') }}">
    @csrf
    @if($product) @method('PUT') @endif

    <div class="row g-4">

        {{-- Left column: main fields --}}
        <div class="col-lg-8">

            {{-- Informasi Dasar --}}
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Dasar</h6>
                </div>
                <div class="card-body p-4">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control form-control-lg @error('name') is-invalid @enderror"
                               value="{{ old('name', $product?->name) }}"
                               placeholder="Contoh: Hiking Gunung Bunder" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="5"
                                  placeholder="Jelaskan aktivitas ini secara singkat dan menarik...">{{ old('description', $product?->description) }}</textarea>
                        <div class="form-text">Tampil di halaman detail produk.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Tipe</label>
                            <select name="type" class="form-select">
                                <option value="aktivitas" {{ old('type', $product?->type ?? 'aktivitas') === 'aktivitas' ? 'selected' : '' }}>
                                    Aktivitas
                                </option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="">— Pilih —</option>
                                <option value="indoor"  {{ old('category', $product?->category) === 'indoor'  ? 'selected' : '' }}>Indoor</option>
                                <option value="outdoor" {{ old('category', $product?->category) === 'outdoor' ? 'selected' : '' }}>Outdoor</option>
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                </div>
            </div>

            {{-- Harga & Kapasitas --}}
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-tags me-2 text-success"></i>Harga & Kapasitas</h6>
                </div>
                <div class="card-body p-4">

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Harga Dewasa <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="price_adult"
                                       class="form-control @error('price_adult') is-invalid @enderror"
                                       value="{{ old('price_adult', $product?->price_adult) }}"
                                       placeholder="0" min="0" required>
                                @error('price_adult')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Harga Anak</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="price_child"
                                       class="form-control @error('price_child') is-invalid @enderror"
                                       value="{{ old('price_child', $product?->price_child) }}"
                                       placeholder="0" min="0">
                                @error('price_child')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">Biarkan kosong jika sama dengan harga dewasa.</div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Durasi</label>
                            <div class="input-group">
                                <input type="number" name="duration_minutes"
                                       class="form-control"
                                       value="{{ old('duration_minutes', $product?->duration_minutes) }}"
                                       placeholder="60" min="1">
                                <span class="input-group-text">menit</span>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Min Pax</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-people"></i></span>
                                <input type="number" name="min_pax" class="form-control"
                                       value="{{ old('min_pax', $product?->min_pax ?? 1) }}" min="1" placeholder="1">
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Maks Pax <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-people-fill"></i></span>
                                <input type="number" name="max_pax"
                                       class="form-control @error('max_pax') is-invalid @enderror"
                                       value="{{ old('max_pax', $product?->max_pax ?? 10) }}" min="1" required>
                                @error('max_pax')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Min Usia</label>
                            <div class="input-group">
                                <input type="number" name="min_age" class="form-control"
                                       value="{{ old('min_age', $product?->min_age) }}" min="0" placeholder="0">
                                <span class="input-group-text">tahun</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- Right column: status, settings --}}
        <div class="col-lg-4">

            {{-- Publish --}}
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-send me-2 text-primary"></i>Publish</h6>
                </div>
                <div class="card-body p-4">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active"   {{ old('status', $product?->status ?? 'active')   === 'active'   ? 'selected' : '' }}>
                                ✅ Aktif
                            </option>
                            <option value="inactive" {{ old('status', $product?->status)                === 'inactive' ? 'selected' : '' }}>
                                ⏸ Nonaktif
                            </option>
                            <option value="archived" {{ old('status', $product?->status)                === 'archived' ? 'selected' : '' }}>
                                📦 Diarsip
                            </option>
                        </select>
                    </div>

                    <div class="p-3 rounded-3 border bg-light mb-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                   id="isFeatured"
                                   {{ old('is_featured', $product?->is_featured) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="isFeatured">
                                <i class="bi bi-star-fill text-warning me-1"></i>Produk Unggulan
                            </label>
                        </div>
                        <div class="form-text mt-1">Tampil di bagian Featured di beranda.</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            {{ $product ? 'Simpan Perubahan' : 'Buat Produk' }}
                        </button>
                        <a href="{{ route('tenant.admin.products.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>
                </div>
            </div>

            {{-- Level --}}
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-steps me-2 text-warning"></i>Detail Aktivitas</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Tingkat Kesulitan</label>
                        <select name="level" class="form-select">
                            <option value="">— Tidak ditentukan —</option>
                            <option value="beginner"     {{ old('level', $product?->level) === 'beginner'     ? 'selected' : '' }}>🟢 Pemula</option>
                            <option value="intermediate" {{ old('level', $product?->level) === 'intermediate' ? 'selected' : '' }}>🟡 Menengah</option>
                            <option value="advanced"     {{ old('level', $product?->level) === 'advanced'     ? 'selected' : '' }}>🔴 Mahir</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Info: slots --}}
            @if($product)
            <div class="card" style="border: 1.5px dashed #dee2e6;">
                <div class="card-body p-3 text-center">
                    <i class="bi bi-calendar-week fs-4 text-muted mb-2 d-block"></i>
                    <p class="small text-muted mb-2">Kelola jadwal & slot ketersediaan produk ini.</p>
                    <a href="{{ route('tenant.admin.products.slots', $product->id) }}"
                       class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-clock me-1"></i>Kelola Slot
                    </a>
                </div>
            </div>
            @endif

        </div>
    </div>

</form>

@endsection
