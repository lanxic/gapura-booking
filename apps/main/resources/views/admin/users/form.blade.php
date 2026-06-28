@extends('layouts.admin')

@section('title', $user ? 'Edit User' : 'Tambah User')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Manajemen User</a></li>
    <li class="breadcrumb-item active">{{ $user ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')
<div class="card" style="max-width: 520px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if($user) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold small">Nama</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user?->name) }}" required>
            </div>

            @if(!$user)
            <div class="mb-3">
                <label class="form-label fw-semibold small">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            @endif

            <div class="mb-3" x-data="{ show: false }">
                <label class="form-label fw-semibold small">Password {{ $user ? '(kosongkan jika tidak diubah)' : '' }}</label>
                <div class="input-group">
                    <input :type="show ? 'text' : 'password'" name="password"
                           class="form-control" {{ !$user ? 'required' : '' }}>
                    <button type="button" class="btn btn-outline-secondary" @click="show = !show" tabindex="-1">
                        <i class="bi bi-eye" x-show="!show"></i>
                        <i class="bi bi-eye-slash" x-show="show" x-cloak></i>
                    </button>
                </div>
            </div>

            <div class="mb-3" x-data="{ role: '{{ old('role', $user?->role->value ?? 'tenant_admin') }}' }">
                <label class="form-label fw-semibold small">Role</label>
                <select name="role" class="form-select" required x-model="role">
                    <option value="super_admin" {{ old('role', $user?->role->value) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="admin" {{ old('role', $user?->role->value) === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="tenant_admin" {{ old('role', $user?->role->value) === 'tenant_admin' ? 'selected' : '' }}>Tenant Admin</option>
                    <option value="scanner" {{ old('role', $user?->role->value) === 'scanner' ? 'selected' : '' }}>Scanner</option>
                </select>

                {{-- Tenant selector: show only for tenant_admin and scanner --}}
                <div class="mt-2" x-show="role === 'tenant_admin' || role === 'scanner'">
                    <label class="form-label fw-semibold small mt-2">Tenant</label>
                    <select name="tenant_id" class="form-select">
                        <option value="">— Pilih Tenant —</option>
                        @foreach(\App\Models\Tenant::orderBy('name')->get() as $t)
                        <option value="{{ $t->id }}"
                            {{ old('tenant_id', $user?->tenant_id) == $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small">No HP</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user?->phone) }}">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ $user ? 'Update' : 'Buat User' }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
