@extends('layouts.admin')

@section('title', $tenant ? 'Edit Tenant' : 'Tambah Tenant')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.tenants.index') }}">Tenant</a></li>
    <li class="breadcrumb-item active">{{ $tenant ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')

<div class="card" style="max-width: 640px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ $tenant ? route('admin.tenants.update', $tenant) : route('admin.tenants.store') }}">
            @csrf
            @if($tenant) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold small">Nama Tenant <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $tenant?->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Subdomain Slug <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $tenant?->slug) }}" placeholder="namatenant">
                        <span class="input-group-text small text-muted">.localhost</span>
                    </div>
                    <div class="form-text">Hanya huruf, angka, dan tanda hubung.</div>
                    @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Prefix Invoice <span class="text-danger">*</span></label>
                    <input type="text" name="invoice_prefix" class="form-control @error('invoice_prefix') is-invalid @enderror"
                           value="{{ old('invoice_prefix', $tenant?->invoice_prefix) }}"
                           placeholder="TNA" maxlength="10" style="text-transform:uppercase">
                    <div class="form-text">Maks 10 huruf. Contoh: TNA untuk "Tenant A".</div>
                    @error('invoice_prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Domain Kustom</label>
                    <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror"
                           value="{{ old('domain', $tenant?->domain) }}" placeholder="www.tenant.com">
                    <div class="form-text">Opsional. Isi jika tenant punya domain sendiri.</div>
                    @error('domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small">URL Logo</label>
                    <input type="url" name="logo_url" class="form-control @error('logo_url') is-invalid @enderror"
                           value="{{ old('logo_url', $tenant?->logo_url) }}" placeholder="https://...">
                    @error('logo_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $tenant?->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">Tenant Aktif</label>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">
                        {{ $tenant ? 'Update Tenant' : 'Buat Tenant' }}
                    </button>
                    <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
