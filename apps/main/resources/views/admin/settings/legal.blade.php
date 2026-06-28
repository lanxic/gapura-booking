@extends('layouts.admin')

@section('title', 'Konten Legal')

@section('content')

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.general') }}">Umum</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.storefront') }}">Storefront</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.social') }}">Sosial Media</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.payment-gateways') }}">Metode Pembayaran</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.settings.legal') }}">Legal</a>
    </li>
</ul>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.settings.legal.update') }}">
            @csrf

            <div class="mb-4">
                <label class="form-label fw-semibold">Kebijakan Privasi</label>
                <p class="text-muted small">Dapat menggunakan HTML.</p>
                <textarea name="privacy_policy" class="form-control font-monospace" rows="10"
                          style="font-size: .8rem;">{{ old('privacy_policy', $settings['privacy_policy'] ?? '') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Syarat & Ketentuan</label>
                <p class="text-muted small">Dapat menggunakan HTML.</p>
                <textarea name="terms_of_service" class="form-control font-monospace" rows="10"
                          style="font-size: .8rem;">{{ old('terms_of_service', $settings['terms_of_service'] ?? '') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Konten Legal</button>
        </form>
    </div>
</div>

@endsection
