@extends('layouts.app')

@section('title', 'Penawaran')

@section('content')
<div class="container py-5">
    <h2 class="fw-bold mb-4">Penawaran Spesial</h2>

    <div class="row g-4">
        @forelse($offers as $offer)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                @if($offer->banner_url)
                    <img src="{{ $offer->banner_url }}" alt="{{ $offer->title }}" class="card-img-top"
                         style="height: 180px; object-fit: cover;">
                @endif
                <div class="card-body">
                    <span class="badge bg-danger-subtle text-danger mb-2">{{ $offer->discount_type === 'percent' ? $offer->discount_value . '%' : 'Rp ' . number_format($offer->discount_value, 0, ',', '.') }} OFF</span>
                    <h5 class="card-title fw-bold">{{ $offer->title }}</h5>
                    <p class="card-text text-muted small">{{ Str::limit($offer->description, 100) }}</p>
                    @if($offer->valid_until)
                        <p class="text-muted small"><i class="bi bi-clock me-1"></i>Berlaku hingga {{ $offer->valid_until->format('d M Y') }}</p>
                    @endif
                </div>
                <div class="card-footer bg-transparent border-0 pb-3 px-3">
                    <a href="{{ route('offers.show', $offer->slug) }}" class="btn btn-outline-primary btn-sm">Lihat Detail</a>
                </div>
            </div>
        </div>
        @empty
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-tag fs-1 d-block mb-3"></i>
                Belum ada penawaran aktif.
            </div>
        @endforelse
    </div>

    @if($offers->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $offers->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
