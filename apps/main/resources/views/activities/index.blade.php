@extends('layouts.app')

@section('title', 'Semua Aktivitas')

@section('content')

@php
    $heroImage = \App\Models\SystemSetting::get('storefront', 'hero_image_url');
@endphp

{{-- Hero banner --}}
<div class="hero-banner"
     @if($heroImage) style="background-image:url({{ $heroImage }})" @endif>
</div>

{{-- Section title --}}
<div class="section-title">
    <h2 class="mb-0">Semua Aktivitas</h2>
    <div class="title-divider"></div>
</div>

<div class="container pb-5">

    <div x-data="{ view: 'list' }">

    {{-- Filter + view toggle --}}
    <div class="d-flex justify-content-end align-items-center mb-3 gap-2">

        {{-- Filter form --}}
        <form method="GET" action="{{ route('activities.index') }}" class="d-flex gap-2 flex-grow-1 flex-wrap me-auto">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:260px"
                   placeholder="Cari aktivitas..." value="{{ request('search') }}">
            <select name="category" class="form-select form-select-sm" style="max-width:160px">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                        {{ ucfirst($cat) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>

        {{-- View toggle --}}
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary"
                    :class="{ 'active': view === 'list' }" @click="view = 'list'" title="List">
                <i class="bi bi-list-ul"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary"
                    :class="{ 'active': view === 'grid' }" @click="view = 'grid'" title="Grid">
                <i class="bi bi-grid-3x3-gap"></i>
            </button>
        </div>
    </div>

    {{-- ─── LIST / GRID VIEWS ───────────────────────────────────────────────── --}}

        {{-- List cards --}}
        <div x-show="view === 'list'" x-cloak>
        <div class="d-flex flex-column gap-3">
            @forelse($activities as $activity)
            <div class="activity-list-card">

                {{-- Images --}}
                @php
                    $imgs = $activity->media->take(2);
                @endphp
                <div class="alc-images {{ $imgs->count() >= 2 ? 'has-two' : '' }}">
                    @if($imgs->count() >= 2)
                        <img src="{{ $imgs->first()->url }}" alt="{{ $activity->name }}">
                        <img src="{{ $imgs->get(1)->url }}" alt="{{ $activity->name }}">
                    @elseif($imgs->count() === 1)
                        <img src="{{ $imgs->first()->url }}" alt="{{ $activity->name }}">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light" style="width:100%;min-height:200px">
                            <i class="bi bi-calendar-event text-muted fs-1"></i>
                        </div>
                    @endif
                </div>

                {{-- Body --}}
                <div class="alc-body">
                    {{-- Konfirmasi Instan --}}
                    <span class="badge-instant">
                        <i class="bi bi-lightning-charge-fill"></i> Konfirmasi Instan
                    </span>

                    {{-- Title --}}
                    <h5 class="fw-bold mb-0">
                        <a href="{{ route('activities.show', $activity->slug) }}"
                           class="text-dark text-decoration-none stretched-link-text">
                            {{ $activity->name }}
                        </a>
                    </h5>

                    {{-- Category + meta --}}
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if($activity->category)
                            <span class="badge bg-primary-subtle">{{ ucfirst($activity->category) }}</span>
                        @endif
                        @if($activity->duration_minutes)
                            <span class="text-muted small">
                                <i class="bi bi-clock me-1"></i>{{ $activity->duration_minutes }} menit
                            </span>
                        @endif
                        @if($activity->min_pax)
                            <span class="text-muted small">
                                <i class="bi bi-people me-1"></i>Min. {{ $activity->min_pax }} pax
                            </span>
                        @endif
                    </div>

                    {{-- Description --}}
                    <p class="text-muted small mb-0">{{ $activity->short_description }}</p>

                    {{-- Footer: price + CTA --}}
                    <div class="alc-footer">
                        <span class="fw-bold text-primary fs-5">
                            Rp {{ number_format($activity->base_price, 0, ',', '.') }}
                        </span>
                        <a href="{{ route('activities.show', $activity->slug) }}"
                           class="btn btn-primary btn-sm px-4">
                            Beli Sekarang
                        </a>
                    </div>
                </div>

            </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-search fs-1 d-block mb-3"></i>
                    Tidak ada aktivitas yang ditemukan.
                </div>
            @endforelse
        </div>
        </div>

        {{-- Grid cards (toggle) --}}
        <div class="row g-4" x-show="view === 'grid'" x-cloak>
            @forelse($activities as $activity)
            <div class="col-md-6 col-lg-4">
                <div class="card activity-card h-100">
                    @if($activity->cloudinary_thumbnail_url)
                        <img src="{{ $activity->cloudinary_thumbnail_url }}" alt="{{ $activity->name }}" class="card-img-top">
                    @else
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height:200px; border-radius: 12px 12px 0 0;">
                            <i class="bi bi-calendar-event text-muted fs-1"></i>
                        </div>
                    @endif
                    <div class="card-body">
                        @if($activity->category)
                            <span class="badge bg-primary-subtle mb-2">{{ ucfirst($activity->category) }}</span>
                        @endif
                        <h5 class="card-title fw-bold">{{ $activity->name }}</h5>
                        <p class="card-text text-muted small">{{ $activity->short_description }}</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center pb-3 px-3">
                        <span class="fw-bold text-primary">Rp {{ number_format($activity->base_price, 0, ',', '.') }}</span>
                        <a href="{{ route('activities.show', $activity->slug) }}" class="btn btn-primary btn-sm">Beli Sekarang</a>
                    </div>
                </div>
            </div>
            @empty
                <div class="col-12 text-center text-muted py-5">
                    <i class="bi bi-search fs-1 d-block mb-3"></i>
                    Tidak ada aktivitas yang ditemukan.
                </div>
            @endforelse
        </div>

    </div>

    {{-- Pagination --}}
    @if($activities->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $activities->links('pagination::bootstrap-5') }}
        </div>
    @endif

</div>
@endsection
