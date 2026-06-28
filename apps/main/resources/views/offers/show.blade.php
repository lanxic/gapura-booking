@extends('layouts.app')

@section('title', $offer->title)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            @if($offer->banner_url)
                <img src="{{ $offer->banner_url }}" alt="{{ $offer->title }}"
                     class="img-fluid rounded-3 w-100 mb-4" style="max-height: 350px; object-fit: cover;">
            @endif

            <span class="badge bg-danger-subtle text-danger mb-2">
                {{ $offer->discount_type === 'percent' ? $offer->discount_value . '%' : 'Rp ' . number_format($offer->discount_value, 0, ',', '.') }} OFF
            </span>
            <h1 class="fw-bold mb-3">{{ $offer->title }}</h1>

            @if($offer->valid_until)
                <p class="text-muted"><i class="bi bi-clock me-1"></i>Berlaku hingga {{ $offer->valid_until->format('d M Y') }}</p>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <p class="mb-0">{!! nl2br(e($offer->description)) !!}</p>
                </div>
            </div>

            @if($offer->promo_codes_count > 0 || $offer->type === 'promo_code')
                <div class="card border-primary mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Gunakan Kode Promo</h6>
                        <p class="text-muted small mb-0">Masukkan kode promo saat checkout untuk mendapatkan diskon ini.</p>
                    </div>
                </div>
            @endif

            <a href="{{ route('activities.index') }}" class="btn btn-primary">
                <i class="bi bi-calendar-event me-2"></i>Jelajahi Aktivitas
            </a>

        </div>
    </div>
</div>
@endsection
