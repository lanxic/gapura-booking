@extends('layouts.tenant-storefront')

@section('title', 'Beranda')

@section('content')

{{-- Hero Banner --}}
<div class="hero-banner d-flex align-items-center justify-content-center text-white text-center"
     style="min-height: 340px;">
    <div class="hero-banner__overlay"></div>
    <div class="position-relative" style="z-index: 1;">
        @if($tenant->logo_url)
            <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->name }}"
                 style="height:56px;object-fit:contain;filter:brightness(0) invert(1);margin-bottom:.75rem;display:block;margin-left:auto;margin-right:auto">
        @endif
        <h1 class="display-5 fw-bold mb-2">{{ $tenant->name }}</h1>
        <p class="lead mb-0 opacity-75">Temukan pengalaman seru dan pesan tiket dengan mudah.</p>
    </div>
</div>

{{-- Section title --}}
<div class="section-title">
    <h2 class="mb-0">Semua Produk</h2>
    <div class="title-divider"></div>
</div>

<div class="container pb-5" x-data="{ view: 'list' }">

    {{-- Filter + view toggle --}}
    <div class="d-flex justify-content-end align-items-center mb-3 gap-2">

        <form method="GET" action="{{ route('tenant.home') }}"
              class="d-flex gap-2 flex-grow-1 flex-wrap me-auto">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:260px"
                   placeholder="Cari produk..." value="{{ request('search') }}">
            <select name="category" class="form-select form-select-sm" style="max-width:160px">
                <option value="">Semua Kategori</option>
                <option value="indoor"  {{ request('category') === 'indoor'  ? 'selected' : '' }}>Indoor</option>
                <option value="outdoor" {{ request('category') === 'outdoor' ? 'selected' : '' }}>Outdoor</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request('search') || request('category'))
                <a href="{{ route('tenant.home') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            @endif
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

    {{-- ── LIST VIEW ──────────────────────────────────────────────────────── --}}
    <div x-show="view === 'list'" x-cloak>
        <div class="d-flex flex-column gap-3">
            @forelse($products as $product)
            <div class="activity-list-card">

                @php $imgs = $product->media->take(2); @endphp
                <div class="alc-images {{ $imgs->count() >= 2 ? 'has-two' : '' }}">
                    @if($imgs->count() >= 2)
                        <img src="{{ $imgs->first()->url }}" alt="{{ $product->name }}">
                        <img src="{{ $imgs->get(1)->url }}" alt="{{ $product->name }}">
                    @elseif($imgs->count() === 1)
                        <img src="{{ $imgs->first()->url }}" alt="{{ $product->name }}">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light"
                             style="width:100%;min-height:200px">
                            <i class="bi bi-image text-muted fs-1"></i>
                        </div>
                    @endif
                </div>

                <div class="alc-body">
                    <span class="badge-instant">
                        <i class="bi bi-lightning-charge-fill"></i> Konfirmasi Instan
                        @if($product->is_featured)
                            &nbsp;·&nbsp;<span class="text-warning"><i class="bi bi-star-fill"></i> Unggulan</span>
                        @endif
                    </span>

                    <h5 class="fw-bold mb-0">
                        <a href="{{ route('tenant.products.show', $product->slug) }}"
                           class="text-dark text-decoration-none">{{ $product->name }}</a>
                    </h5>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if($product->category)
                            <span class="badge bg-primary-subtle">{{ ucfirst($product->category) }}</span>
                        @endif
                        @if($product->duration_minutes)
                            <span class="text-muted small">
                                <i class="bi bi-clock me-1"></i>{{ $product->duration_minutes }} menit
                            </span>
                        @endif
                        @if($product->min_pax)
                            <span class="text-muted small">
                                <i class="bi bi-people me-1"></i>Min. {{ $product->min_pax }} pax
                            </span>
                        @endif
                        @if($product->level)
                            <span class="text-muted small">
                                <i class="bi bi-bar-chart me-1"></i>{{ ucfirst($product->level) }}
                            </span>
                        @endif
                    </div>

                    @if($product->short_description)
                        <p class="text-muted small mb-0">{{ $product->short_description }}</p>
                    @endif

                    <div class="alc-footer">
                        <div>
                            <div class="text-muted" style="font-size:.72rem">Mulai dari</div>
                            <span class="fw-bold text-primary fs-5">
                                Rp {{ number_format($product->price_adult, 0, ',', '.') }}
                            </span>
                        </div>
                        <a href="{{ route('tenant.products.show', $product->slug) }}"
                           class="btn btn-primary btn-sm px-4">Beli Sekarang</a>
                    </div>
                </div>

            </div>
            @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-search fs-1 d-block mb-3"></i>
                Tidak ada produk yang ditemukan.
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── GRID VIEW ──────────────────────────────────────────────────────── --}}
    <div class="row g-4" x-show="view === 'grid'" x-cloak>
        @forelse($products as $product)
        <div class="col-md-6 col-lg-4">
            <div class="card activity-card h-100">
                @if($product->cloudinary_thumbnail_url)
                    <img src="{{ $product->cloudinary_thumbnail_url }}"
                         alt="{{ $product->name }}" class="card-img-top">
                @elseif($product->media->isNotEmpty())
                    <img src="{{ $product->media->first()->url }}"
                         alt="{{ $product->name }}" class="card-img-top">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center"
                         style="height:200px;border-radius:12px 12px 0 0">
                        <i class="bi bi-image text-muted fs-1"></i>
                    </div>
                @endif
                <div class="card-body">
                    <div class="d-flex gap-1 flex-wrap mb-2">
                        @if($product->is_featured)
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="bi bi-star-fill me-1"></i>Unggulan
                            </span>
                        @endif
                        @if($product->category)
                            <span class="badge bg-primary-subtle">{{ ucfirst($product->category) }}</span>
                        @endif
                    </div>
                    <h5 class="card-title fw-bold">{{ $product->name }}</h5>
                    @if($product->short_description)
                        <p class="card-text text-muted small">{{ $product->short_description }}</p>
                    @endif
                    @if($product->duration_minutes || $product->min_pax)
                    <div class="d-flex gap-3 text-muted small mt-auto">
                        @if($product->duration_minutes)
                            <span><i class="bi bi-clock me-1"></i>{{ $product->duration_minutes }} mnt</span>
                        @endif
                        @if($product->min_pax)
                            <span><i class="bi bi-people me-1"></i>Min. {{ $product->min_pax }}</span>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center pb-3 px-3">
                    <span class="fw-bold text-primary">
                        Rp {{ number_format($product->price_adult, 0, ',', '.') }}
                    </span>
                    <a href="{{ route('tenant.products.show', $product->slug) }}"
                       class="btn btn-primary btn-sm">Beli Sekarang</a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center text-muted py-5">
            <i class="bi bi-search fs-1 d-block mb-3"></i>
            Tidak ada produk yang ditemukan.
        </div>
        @endforelse
    </div>

</div>

{{-- Trust bar --}}
<section class="py-4 bg-white border-top">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <i class="bi bi-lightning-charge-fill fs-3 text-warning mb-2 d-block"></i>
                <div class="fw-semibold small">Konfirmasi Instan</div>
                <div class="text-muted" style="font-size:.75rem">Tiket langsung diterima</div>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-shield-check-fill fs-3 text-success mb-2 d-block"></i>
                <div class="fw-semibold small">Pembayaran Aman</div>
                <div class="text-muted" style="font-size:.75rem">Dijamin & terenkripsi</div>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-qr-code fs-3 text-primary mb-2 d-block"></i>
                <div class="fw-semibold small">E-Tiket Digital</div>
                <div class="text-muted" style="font-size:.75rem">Tunjukkan QR code saat tiba</div>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-headset fs-3 text-info mb-2 d-block"></i>
                <div class="fw-semibold small">Dukungan 24/7</div>
                <div class="text-muted" style="font-size:.75rem">Siap membantu kapan saja</div>
            </div>
        </div>
    </div>
</section>

@endsection
