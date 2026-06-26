@extends('layouts.admin')

@section('title', 'Pengaturan Storefront')

@section('content')

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.general') }}">Umum</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.settings.storefront') }}">Storefront</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.social') }}">Sosial Media</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.payment-gateways') }}">Metode Pembayaran</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.legal') }}">Legal</a></li>
</ul>

<div class="card" style="max-width: 600px;">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-4 text-muted text-uppercase small">Hero / Banner Halaman Utama</h6>
        <form method="POST" action="{{ route('admin.settings.storefront.update') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold small">URL Gambar Banner</label>
                <input type="url" name="hero_image_url" class="form-control"
                       value="{{ old('hero_image_url', $settings['hero_image_url'] ?? '') }}"
                       placeholder="https://... (kosongkan untuk gradient hijau)">
                @if(!empty($settings['hero_image_url']))
                <div class="mt-2 rounded overflow-hidden" style="max-width:300px;height:100px">
                    <img src="{{ $settings['hero_image_url'] }}" alt="Banner"
                         style="width:100%;height:100%;object-fit:cover">
                </div>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Judul Hero</label>
                <input type="text" name="hero_title" class="form-control"
                       value="{{ old('hero_title', $settings['hero_title'] ?? '') }}"
                       placeholder="Temukan Aktivitas Seru">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Subjudul Hero</label>
                <input type="text" name="hero_subtitle" class="form-control"
                       value="{{ old('hero_subtitle', $settings['hero_subtitle'] ?? '') }}"
                       placeholder="Pesan tiket aktivitas terbaik...">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small">Label Tombol CTA</label>
                <input type="text" name="hero_cta_label" class="form-control"
                       value="{{ old('hero_cta_label', $settings['hero_cta_label'] ?? '') }}"
                       placeholder="Jelajahi Aktivitas">
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

@endsection
