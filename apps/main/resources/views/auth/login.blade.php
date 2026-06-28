@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <h5 class="fw-bold mb-1">Selamat Datang</h5>
    <p class="text-muted small mb-4">Masuk ke akun Anda untuk melanjutkan.</p>

    @php
        $loginPostRoute = app()->bound('current_tenant')
            ? route('tenant.login')
            : route('login');
        $registerRoute = app()->bound('current_tenant')
            ? route('tenant.register')
            : route('register');
    @endphp
    <form method="POST" action="{{ $loginPostRoute }}">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-semibold small">Email</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="email@example.com" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3" x-data="{ show: false }">
            <label class="form-label fw-semibold small">Password</label>
            <div class="input-group">
                <input :type="show ? 'text' : 'password'" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="••••••••" required>
                <button type="button" class="btn btn-outline-secondary" @click="show = !show" tabindex="-1">
                    <i class="bi bi-eye" x-show="!show"></i>
                    <i class="bi bi-eye-slash" x-show="show" x-cloak></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small" for="remember">Ingat saya</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Masuk</button>
    </form>

    <hr class="my-4">
    <p class="text-center small mb-0">
        Belum punya akun? <a href="{{ $registerRoute }}" class="text-primary fw-semibold">Daftar sekarang</a>
    </p>

    @if(app()->bound('current_tenant'))
    <p class="text-center small mt-2 mb-0 text-muted">
        Admin? <a href="{{ route('tenant.admin.login') }}" class="text-muted fw-semibold">Masuk ke panel admin</a>
    </p>
    @endif
@endsection
