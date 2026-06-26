@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.settings.general') }}">Umum</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.storefront') }}">Storefront</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.social') }}">Sosial Media</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.payment-gateways') }}">Metode Pembayaran</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.legal') }}">Legal</a></li>
</ul>

<div class="card" style="max-width: 600px;">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-4 text-muted text-uppercase small">Informasi Situs</h6>
        <form method="POST" action="{{ route('admin.settings.general.update') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold small">Nama Situs</label>
                <input type="text" name="site_name" class="form-control"
                       value="{{ old('site_name', $settings['site_name'] ?? '') }}"
                       placeholder="Contoh: Taman Safari Bali">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Email Kontak</label>
                <input type="email" name="site_email" class="form-control"
                       value="{{ old('site_email', $settings['site_email'] ?? '') }}"
                       placeholder="info@example.com">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Nomor Telepon</label>
                <input type="text" name="site_phone" class="form-control"
                       value="{{ old('site_phone', $settings['site_phone'] ?? '') }}"
                       placeholder="+62 361 000000">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Alamat</label>
                <textarea name="site_address" class="form-control" rows="2"
                          placeholder="Jl. ...">{{ old('site_address', $settings['site_address'] ?? '') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small">URL Logo <span class="text-muted fw-normal">(ditampilkan di navbar)</span></label>
                <input type="url" name="logo_url" class="form-control"
                       value="{{ old('logo_url', $settings['logo_url'] ?? '') }}"
                       placeholder="https://...">
                @if(!empty($settings['logo_url']))
                <div class="mt-2">
                    <img src="{{ $settings['logo_url'] }}" alt="Logo" style="height:40px;object-fit:contain">
                </div>
                @endif
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

@endsection
