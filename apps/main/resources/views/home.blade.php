@extends('layouts.app')

@section('title', 'Beranda')

@section('content')

@php
    $hero       = \App\Models\SystemSetting::getGroup('storefront');
    $heroImage  = $hero['hero_image_url'] ?? null;
    $heroTitle  = $hero['hero_title']     ?? 'Temukan Aktivitas Seru';
    $heroSub    = $hero['hero_subtitle']  ?? 'Pesan tiket aktivitas terbaik dengan mudah dan cepat.';
    $heroBtn    = $hero['hero_cta_label'] ?? 'Jelajahi Aktivitas';
@endphp

{{-- Hero banner --}}
<div class="hero-banner d-flex align-items-center justify-content-center text-white text-center"
     style="min-height: 420px; {{ $heroImage ? 'background-image:url(' . e($heroImage) . ');' : '' }}">
    <div class="position-relative" style="z-index: 1;">
        <h1 class="display-5 fw-bold mb-3">{{ $heroTitle }}</h1>
        <p class="lead mb-4 opacity-75">{{ $heroSub }}</p>
        <a href="{{ route('activities.index') }}" class="btn btn-light btn-lg px-5 fw-semibold">
            {{ $heroBtn }}
        </a>
    </div>
</div>

{{-- Featured Activities --}}
@if($featured->isNotEmpty())
<section class="py-5 bg-white">
    <div class="container">
        <div class="section-title pt-0 text-start mb-4">
            <h2 class="mb-0">Aktivitas Unggulan</h2>
            <div class="title-divider ms-0"></div>
        </div>
        <div class="d-flex flex-column gap-3">
            @foreach($featured as $activity)
            <div class="activity-list-card">
                @php $imgs = $activity->media->take(2); @endphp
                <div class="alc-images {{ $imgs->count() >= 2 ? 'has-two' : '' }}">
                    @if($imgs->count() >= 2)
                        <img src="{{ $imgs->first()->url }}" alt="{{ $activity->name }}">
                        <img src="{{ $imgs->get(1)->url }}" alt="{{ $activity->name }}">
                    @elseif($imgs->count() === 1)
                        <img src="{{ $imgs->first()->url }}" alt="{{ $activity->name }}">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light" style="width:100%;min-height:200px">
                            <i class="bi bi-image text-muted fs-1"></i>
                        </div>
                    @endif
                </div>
                <div class="alc-body">
                    <span class="badge-instant"><i class="bi bi-lightning-charge-fill"></i> Konfirmasi Instan</span>
                    <h5 class="fw-bold mb-0">{{ $activity->name }}</h5>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if($activity->category)
                            <span class="badge bg-primary-subtle">{{ ucfirst($activity->category) }}</span>
                        @endif
                        @if($activity->duration_minutes)
                            <span class="text-muted small"><i class="bi bi-clock me-1"></i>{{ $activity->duration_minutes }} menit</span>
                        @endif
                    </div>
                    <p class="text-muted small mb-0">{{ $activity->short_description }}</p>
                    <div class="alc-footer">
                        <span class="fw-bold text-primary fs-5">Rp {{ number_format($activity->base_price, 0, ',', '.') }}</span>
                        <a href="{{ route('activities.show', $activity->slug) }}" class="btn btn-primary btn-sm px-4">Beli Sekarang</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Latest Activities --}}
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0 text-primary">Aktivitas Terbaru</h2>
                <div style="width:56px;height:4px;background:var(--safari-green);border-radius:2px;margin-top:.4rem;"></div>
            </div>
            <a href="{{ route('activities.index') }}" class="btn btn-outline-primary btn-sm">Lihat Semua</a>
        </div>
        <div class="row g-4">
            @forelse($latest as $activity)
            <div class="col-md-6 col-lg-3">
                <div class="card activity-card h-100">
                    @if($activity->cloudinary_thumbnail_url)
                        <img src="{{ $activity->cloudinary_thumbnail_url }}" alt="{{ $activity->name }}" class="card-img-top">
                    @else
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height:160px; border-radius: 12px 12px 0 0;">
                            <i class="bi bi-calendar-event text-muted fs-2"></i>
                        </div>
                    @endif
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1">{{ $activity->name }}</h6>
                        <p class="text-muted small mb-2">{{ Str::limit($activity->short_description, 60) }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary small">Rp {{ number_format($activity->base_price, 0, ',', '.') }}</span>
                            <a href="{{ route('activities.show', $activity->slug) }}" class="btn btn-outline-primary btn-sm py-0 px-3">Detail</a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
                <div class="col-12 text-center text-muted py-4">Belum ada aktivitas tersedia.</div>
            @endforelse
        </div>
    </div>
</section>

@endsection
