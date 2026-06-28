@extends('layouts.auth')

@section('title', 'Daftar Akun')

@section('content')
    <h5 class="fw-bold mb-1">Buat Akun Baru</h5>
    <p class="text-muted small mb-4">Daftar untuk mulai memesan tiket aktivitas.</p>

    @php
        $registerPostRoute = app()->bound('current_tenant') ? route('tenant.register') : route('register');
        $loginRoute        = app()->bound('current_tenant') ? route('tenant.login')    : route('login');
    @endphp
    <form method="POST" action="{{ $registerPostRoute }}">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-semibold small">Nama Lengkap</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" placeholder="Nama Anda" required autofocus>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold small">Email</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="email@example.com" required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold small">Nomor HP</label>
            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone') }}" placeholder="08xx xxxx xxxx">
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3" x-data="{ show: false }">
            <label class="form-label fw-semibold small">Password</label>
            <div class="input-group">
                <input :type="show ? 'text' : 'password'" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Minimal 8 karakter" required>
                <button type="button" class="btn btn-outline-secondary" @click="show = !show" tabindex="-1">
                    <i class="bi bi-eye" x-show="!show"></i>
                    <i class="bi bi-eye-slash" x-show="show" x-cloak></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4" x-data="{ show: false }">
            <label class="form-label fw-semibold small">Konfirmasi Password</label>
            <div class="input-group">
                <input :type="show ? 'text' : 'password'" name="password_confirmation"
                       class="form-control" placeholder="Ulangi password" required>
                <button type="button" class="btn btn-outline-secondary" @click="show = !show" tabindex="-1">
                    <i class="bi bi-eye" x-show="!show"></i>
                    <i class="bi bi-eye-slash" x-show="show" x-cloak></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Buat Akun</button>
    </form>

    <hr class="my-4">
    <p class="text-center small mb-0">
        Sudah punya akun? <a href="{{ $loginRoute }}" class="text-primary fw-semibold">Masuk di sini</a>
    </p>
@endsection
