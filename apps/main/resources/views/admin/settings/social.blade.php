@extends('layouts.admin')

@section('title', 'Pengaturan Sosial Media')

@section('content')

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.general') }}">Umum</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.storefront') }}">Storefront</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.settings.social') }}">Sosial Media</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.payment-gateways') }}">Metode Pembayaran</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.legal') }}">Legal</a></li>
</ul>

<div class="card" style="max-width: 600px;">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-1 text-muted text-uppercase small">Link Sosial Media</h6>
        <p class="text-muted small mb-4">Tampil di footer jika diisi. Kosongkan untuk menyembunyikan.</p>
        <form method="POST" action="{{ route('admin.settings.social.update') }}">
            @csrf

            @foreach([
                'facebook'  => ['bi-facebook',   'Facebook'],
                'instagram' => ['bi-instagram',  'Instagram'],
                'twitter'   => ['bi-twitter-x',  'Twitter / X'],
                'youtube'   => ['bi-youtube',     'YouTube'],
                'whatsapp'  => ['bi-whatsapp',    'WhatsApp'],
                'tiktok'    => ['bi-tiktok',      'TikTok'],
            ] as $key => [$icon, $label])
            <div class="mb-3">
                <label class="form-label fw-semibold small">
                    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
                </label>
                <input type="url" name="{{ $key }}" class="form-control"
                       value="{{ old($key, $settings[$key] ?? '') }}"
                       placeholder="https://...">
            </div>
            @endforeach

            <button type="submit" class="btn btn-primary mt-2">Simpan</button>
        </form>
    </div>
</div>

@endsection
