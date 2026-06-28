@extends('layouts.app')

@section('title', 'Checkout')

@push('head')
<style>
.co-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:1.25rem 1.5rem;margin-bottom:1.25rem}
.co-card-title{font-weight:600;font-size:.95rem;margin-bottom:1rem;color:#212529}
.co-notice{font-size:.85rem;color:#6c757d}
.cart-item-name{font-weight:600;font-size:.88rem;color:#212529;margin-bottom:.1rem}
.cart-item-meta{font-size:.8rem;color:#6c757d;margin-bottom:.05rem}
</style>
@endpush

@section('content')
@php
    $isCart    = isset($items) && $items->isNotEmpty();
    $itemCount = $isCart ? $items->count() : 1;
@endphp

<div class="container py-4" style="max-width:1100px">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="fw-bold text-primary mb-0">Checkout</h2>
        <a href="{{ route('activities.index') }}"
           class="btn btn-outline-secondary btn-sm px-4">Kembali Ke Beranda</a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger small">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- Cart clear form (standalone, not nested) --}}
    @if($isCart)
    <form id="clearCartForm" method="POST" action="{{ route('cart.clear') }}" style="display:none">
        @csrf @method('DELETE')
    </form>
    @endif

    <div class="row g-4 align-items-start">

        {{-- ── LEFT: Checkout form (own <form> tag) ────────────────────────── --}}
        <div class="col-lg-7">
            <form method="POST" action="{{ route('checkout.store') }}" id="checkoutForm">
                @csrf

                {{-- Mode hidden fields --}}
                @if($isCart)
                    <input type="hidden" name="cart_checkout" value="1">
                @else
                    <input type="hidden" name="slot_id"     value="{{ $slot->id }}">
                    <input type="hidden" name="pax_count"   value="{{ $pax }}">
                    <input type="hidden" name="addons_json" value="{{ $addonsJson ?? '[]' }}">
                @endif

                {{-- Notice --}}
                <div class="co-card">
                    <div class="co-card-title">Hal-hal yang perlu diperhatikan</div>
                    <p class="co-notice mb-0">
                        Mohon dicek kembali transaksi anda, sebelum anda melakukan pembayaran
                    </p>
                </div>

                {{-- Contact Info --}}
                <div class="co-card">
                    <div class="co-card-title">Kontak Informasi</div>

                    {{-- Email --}}
                    <div class="mb-3">
                        <input type="email" name="guest_email"
                               class="form-control @error('guest_email') is-invalid @enderror"
                               value="{{ old('guest_email', $user->email ?? '') }}"
                               placeholder="Email*" required>
                        <div class="form-text small text-muted mt-1">
                            <i class="bi bi-question-circle me-1"></i>Harap berikan email yang valid untuk menerima e-tiket Anda.
                        </div>
                        @error('guest_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Name --}}
                    <div class="mb-3">
                        <input type="text" name="guest_name"
                               class="form-control @error('guest_name') is-invalid @enderror"
                               value="{{ old('guest_name', $user->name ?? '') }}"
                               placeholder="Nama*" required>
                        @error('guest_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Phone --}}
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text" style="font-size:.85rem;gap:.3rem">
                                🇮🇩 <span>+62</span>
                            </span>
                            <input type="text" name="guest_phone"
                                   class="form-control"
                                   value="{{ old('guest_phone', $user->phone ?? '') }}"
                                   placeholder="Nomor Telepon*">
                        </div>
                    </div>

                    {{-- Country --}}
                    <div class="mb-4">
                        <select name="country" class="form-select">
                            <option value="">Select Country*</option>
                            <option value="ID" {{ old('country','ID')==='ID'?'selected':'' }}>Indonesia</option>
                            <option value="MY" {{ old('country')==='MY'?'selected':'' }}>Malaysia</option>
                            <option value="SG" {{ old('country')==='SG'?'selected':'' }}>Singapore</option>
                            <option value="AU" {{ old('country')==='AU'?'selected':'' }}>Australia</option>
                            <option value="JP" {{ old('country')==='JP'?'selected':'' }}>Japan</option>
                            <option value="CN" {{ old('country')==='CN'?'selected':'' }}>China</option>
                            <option value="KR" {{ old('country')==='KR'?'selected':'' }}>South Korea</option>
                            <option value="US" {{ old('country')==='US'?'selected':'' }}>United States</option>
                            <option value="GB" {{ old('country')==='GB'?'selected':'' }}>United Kingdom</option>
                        </select>
                    </div>

                    {{-- Terms --}}
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeTerms"
                               name="agree_terms" value="1" required
                               {{ old('agree_terms') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="agreeTerms">
                            Dengan melakukan pembelian ini, saya menyatakan bahwa saya telah membaca,
                            memahami, dan menyetujui
                            <a href="{{ route('legal.show', 'terms') }}" target="_blank" class="text-primary">
                                Persyaratan dan Ketentuan berikut</a>.*
                        </label>
                    </div>
                </div>

            </form>{{-- /checkoutForm --}}
        </div>

        {{-- ── RIGHT: Cart summary (no nesting issue) ──────────────────────── --}}
        <div class="col-lg-5">
            <div class="co-card">

                {{-- Cart header --}}
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="fw-semibold" style="font-size:.92rem">
                        Keranjang Saya ({{ $itemCount }})
                    </span>
                    @if($isCart)
                    <button type="button"
                            class="btn btn-link p-0 text-muted"
                            style="line-height:1"
                            onclick="if(confirm('Kosongkan semua keranjang?')) document.getElementById('clearCartForm').submit()">
                        <i class="bi bi-trash3" style="font-size:1rem"></i>
                    </button>
                    @endif
                </div>

                {{-- Items --}}
                @if($isCart)
                    @foreach($items as $item)
                    <div class="{{ !$loop->last ? 'border-bottom mb-3 pb-3' : '' }}">
                        <div class="cart-item-name">{{ $item['slot']->activity->name }}</div>
                        <div class="cart-item-meta">
                            {{ $item['slot']->start_time->format('H:i') }} – {{ $item['slot']->end_time->format('H:i') }}
                        </div>
                        @if($item['pax_adult'] > 0)
                        <div class="cart-item-meta">{{ $item['pax_adult'] }} X ADULT</div>
                        @endif
                        @if($item['pax_child'] > 0)
                        <div class="cart-item-meta">{{ $item['pax_child'] }} X CHILD</div>
                        @endif
                        <div class="cart-item-meta">{{ $item['slot']->date->format('d M Y') }}</div>
                        @foreach($item['addons'] as $addon)
                        <div class="cart-item-meta text-primary" style="font-size:.75rem">
                            + {{ $addon['qty'] }}× {{ $addon['name'] }}
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                @else
                    <div>
                        <div class="cart-item-name">{{ $slot->activity->name }}</div>
                        <div class="cart-item-meta">
                            {{ $slot->start_time->format('H:i') }} – {{ $slot->end_time->format('H:i') }}
                        </div>
                        <div class="cart-item-meta">{{ $pax }} Peserta</div>
                        <div class="cart-item-meta">{{ $slot->date->format('d M Y') }}</div>
                    </div>
                @endif

                <hr class="my-3">

                {{-- Promo code --}}
                <div class="mb-3">
                    <div class="fw-semibold small mb-2">Masukkan Kode Promo</div>
                    <div class="input-group">
                        <input type="text" name="promo_code" form="checkoutForm"
                               class="form-control form-control-sm"
                               value="{{ old('promo_code') }}"
                               placeholder="Kode Promo">
                        <button class="btn btn-primary btn-sm" type="button">Terapkan</button>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Total --}}
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Total</span>
                    <span class="fw-bold text-primary" style="font-size:1.1rem">
                        IDR {{ number_format($grandTotal, 0, ',', '.') }}
                    </span>
                </div>

            </div>

            {{-- Action buttons --}}
            <div class="row g-2">
                <div class="col-6 d-flex">
                    <a href="{{ route('cart.index') }}"
                       class="btn w-100 py-2 fw-semibold d-flex align-items-center justify-content-center"
                       style="background:#c8e6c9;color:#1A4D2E;border:none">
                        Kembali
                    </a>
                </div>
                <div class="col-6 d-flex">
                    <button type="submit" form="checkoutForm"
                            class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center">
                        Lanjutkan Untuk Pembayaran
                    </button>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
