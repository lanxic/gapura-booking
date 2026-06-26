@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <h5 class="fw-bold mb-1">Selamat Datang</h5>
    <p class="text-muted small mb-4">Masuk ke akun Anda untuk melanjutkan.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-semibold small">Email</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="email@example.com" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold small">Password</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
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
        Belum punya akun? <a href="{{ route('register') }}" class="text-primary fw-semibold">Daftar sekarang</a>
    </p>
@endsection
