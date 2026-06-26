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

            <div class="mb-3">
                <label class="form-label fw-semibold small">Password {{ $user ? '(kosongkan jika tidak diubah)' : '' }}</label>
                <input type="password" name="password" class="form-control" {{ !$user ? 'required' : '' }}>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Role</label>
                <select name="role" class="form-select" required>
                    <option value="admin" {{ old('role', $user?->role->value) === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="super_admin" {{ old('role', $user?->role->value) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="scanner" {{ old('role', $user?->role->value) === 'scanner' ? 'selected' : '' }}>Scanner</option>
                </select>
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
