@extends('layouts.tenant-admin')

@section('title', 'Profil Akun')

@section('breadcrumb')
    <li class="breadcrumb-item active">Profil</li>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4" style="max-width:720px">

    {{-- Avatar + info --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex align-items-center gap-4 p-4">
                <div class="avatar-lg flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">{{ $user->name }}</h5>
                    <div class="text-muted small">{{ $user->email }}</div>
                    @if($user->phone)
                    <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $user->phone }}</div>
                    @endif
                    <div class="mt-2">
                        <span class="badge bg-primary-subtle text-primary">
                            {{ $user->role?->label() ?? $user->role }}
                        </span>
                        @if(isset($tenant))
                        <span class="badge bg-secondary-subtle text-secondary ms-1">
                            {{ $tenant->name }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit form --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header py-3 px-4">
                <h6 class="mb-0 fw-semibold">Informasi Akun</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('tenant.admin.profile.update') }}">
                    @csrf @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nama Lengkap</label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email</label>
                            <input type="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">No. Telepon</label>
                            <input type="text" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $user->phone ?? '') }}"
                                   placeholder="08xx-xxxx-xxxx">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Role</label>
                            <input type="text" class="form-control bg-light"
                                   value="{{ $user->role?->label() ?? $user->role }}" disabled>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-semibold mb-1 mt-3">Ubah Password</h6>
                    <p class="text-muted small mb-3">Kosongkan jika tidak ingin mengubah password.</p>

                    <div class="row g-3">
                        <div class="col-12" x-data="{ show: false }">
                            <label class="form-label fw-semibold small">Password Saat Ini</label>
                            <div class="input-group">
                                <input :type="show ? 'text' : 'password'" name="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary" @click="show = !show" tabindex="-1">
                                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            @error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" x-data="{ show: false }">
                            <label class="form-label fw-semibold small">Password Baru</label>
                            <div class="input-group">
                                <input :type="show ? 'text' : 'password'" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" @click="show = !show" tabindex="-1">
                                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" x-data="{ show: false }">
                            <label class="form-label fw-semibold small">Konfirmasi Password Baru</label>
                            <div class="input-group">
                                <input :type="show ? 'text' : 'password'" name="password_confirmation"
                                       class="form-control" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" @click="show = !show" tabindex="-1">
                                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-floppy me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection
