@extends('layouts.tenant-storefront')

@section('title', 'Checkout')

@push('head')
<style>
.co-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:1.25rem 1.5rem;margin-bottom:1.25rem}
.co-card-title{font-weight:600;font-size:.95rem;margin-bottom:1rem;color:#212529}
.co-notice{font-size:.85rem;color:#6c757d}
/* Step breadcrumb */
.step-wrap{display:flex;align-items:center;justify-content:center;gap:.75rem;margin-bottom:1.75rem}
.step-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0}
.step-num-done{background:#e5e7eb;color:#6b7280}
.step-num-active{background:#f59e0b;color:#fff}
.step-lbl{font-size:.85rem}
.step-arrow{color:#9ca3af;font-size:.75rem}
/* Payment method cards */
.pm-group-lbl{font-size:.7rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:.5rem}
.pm-card{display:flex;align-items:center;gap:.85rem;border:2px solid #e5e7eb;border-radius:10px;padding:.85rem 1rem;cursor:pointer;transition:border-color .15s,background .15s;user-select:none;margin-bottom:.5rem}
.pm-card:hover{border-color:#93c5fd;background:#f8faff}
.pm-card.pm-active{border-color:#2563eb;background:#eff6ff}
.pm-radio{width:18px;height:18px;border-radius:50%;border:2px solid #d1d5db;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s}
.pm-active .pm-radio{border-color:#2563eb;background:#2563eb}
.pm-radio-dot{width:6px;height:6px;border-radius:50%;background:#fff;opacity:0;transition:opacity .15s}
.pm-active .pm-radio-dot{opacity:1}
.pm-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0}
.pm-icon-online{background:#ede9fe;color:#7c3aed}
.pm-icon-cash{background:#dcfce7;color:#16a34a}
.pm-icon-bank{background:#dbeafe;color:#2563eb}
.pm-name{font-weight:600;font-size:.875rem;color:#111827;line-height:1.2}
.pm-desc{font-size:.765rem;color:#6b7280;margin-top:.15rem;line-height:1.35}
.pm-badge{font-size:.65rem;font-weight:700;padding:.18rem .5rem;border-radius:999px;flex-shrink:0;align-self:flex-start;letter-spacing:.03em}
.pm-badge-online{background:#ede9fe;color:#6d28d9}
.pm-badge-tunai{background:#d1fae5;color:#065f46}
.pm-badge-bank{background:#dbeafe;color:#1d4ed8}
/* Order summary */
.os-label{font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9ca3af;margin-bottom:.75rem}
.os-row{display:flex;justify-content:space-between;align-items:baseline;gap:.5rem;margin-bottom:.35rem}
.os-name{font-weight:600;font-size:.875rem;color:#111827;line-height:1.25}
.os-sub{font-size:.775rem;color:#6b7280;margin-top:.1rem}
.os-price{font-size:.875rem;color:#111827;white-space:nowrap;font-weight:500}
.os-total-row{display:flex;justify-content:space-between;align-items:center;padding:.6rem 0}
.os-total-lbl{font-size:.85rem;color:#374151}
.os-total-val{font-size:.875rem;color:#374151;font-weight:500}
.os-grand-lbl{font-size:.95rem;font-weight:700;color:#111827}
.os-grand-val{font-size:1.05rem;font-weight:700;color:#111827}
</style>
@endpush

@section('content')
@php
    $isCart    = isset($items) && $items->isNotEmpty();
    $itemCount = $isCart ? $items->count() : 1;
@endphp

<div class="container py-4" style="max-width:1100px">

    {{-- Step indicator --}}
    <div class="step-wrap">
        <div class="d-flex align-items-center gap-2">
            <span class="step-num step-num-done">1</span>
            <span class="step-lbl text-muted">Pilih Produk</span>
        </div>
        <i class="bi bi-chevron-right step-arrow"></i>
        <div class="d-flex align-items-center gap-2">
            <span class="step-num step-num-active">2</span>
            <span class="step-lbl fw-semibold">Data Pembeli</span>
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger small">{{ session('error') }}</div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger small">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- Cart clear form --}}
    @if($isCart)
    <form id="clearCartForm" method="POST" action="{{ route('tenant.cart.clear') }}" style="display:none">
        @csrf @method('DELETE')
    </form>
    @endif

    <div class="row g-4 align-items-start">

        {{-- LEFT: Checkout form --}}
        <div class="col-lg-7">
            <form method="POST" action="{{ route('tenant.checkout.store') }}" id="checkoutForm">
                @csrf

                @if($isCart)
                    <input type="hidden" name="cart_checkout" value="1">
                @else
                    <input type="hidden" name="slot_id"     value="{{ $slot->id }}">
                    <input type="hidden" name="pax_count"   value="{{ $pax }}">
                    <input type="hidden" name="addons_json" value="{{ $addonsJson ?? '[]' }}">
                @endif

                {{-- Data Pembeli --}}
                <div class="co-card">
                    <div class="co-card-title">Data Pembeli</div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <input type="text" name="guest_name"
                                   class="form-control @error('guest_name') is-invalid @enderror"
                                   value="{{ old('guest_name', $user->name ?? '') }}"
                                   placeholder="Nama Lengkap*" required>
                            @error('guest_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <input type="email" name="guest_email"
                                   class="form-control @error('guest_email') is-invalid @enderror"
                                   value="{{ old('guest_email', $user->email ?? '') }}"
                                   placeholder="Email*" required
                                   {{ $user ? 'readonly' : '' }}>
                            @error('guest_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            @php
                                $rawPhone = old('guest_phone', $user->phone ?? '');
                                $rawPhone = preg_replace('/^\+62/', '', $rawPhone);
                                if (preg_match('/^62\d/', $rawPhone)) $rawPhone = substr($rawPhone, 2);
                                $rawPhone = ltrim($rawPhone, '0');
                            @endphp
                            <div class="input-group">
                                <span class="input-group-text" style="font-size:.85rem;gap:.3rem;background:#f8f9fa">🇮🇩 +62</span>
                                <input type="text" name="guest_phone"
                                       class="form-control @error('guest_phone') is-invalid @enderror"
                                       value="{{ $rawPhone }}"
                                       placeholder="8xxxxxxxxx" required
                                       @input="
                                           const el = $event.target;
                                           let v = el.value, stripped = v;
                                           stripped = stripped.replace(/^\+62/, '');
                                           if (/^62\d/.test(stripped)) stripped = stripped.slice(2);
                                           stripped = stripped.replace(/^0+/, '');
                                           if (stripped !== v) {
                                               const pos = Math.max(0, el.selectionStart - (v.length - stripped.length));
                                               el.value = stripped;
                                               el.setSelectionRange(pos, pos);
                                           }
                                       ">
                                @error('guest_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        @if(!$user)
                        <div class="col-sm-6" x-data="{ show: false }">
                            <div class="input-group">
                                <input :type="show ? 'text' : 'password'" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       value="{{ old('password') }}"
                                       placeholder="Password">
                                <button type="button" class="input-group-text bg-white" style="cursor:pointer"
                                        @click="show = !show">
                                    <i :class="show ? 'bi bi-eye-slash' : 'bi bi-eye'" style="font-size:.9rem"></i>
                                </button>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text text-muted mt-1" style="font-size:.75rem">
                                *) Password minimal 6 karakter
                            </div>
                        </div>
                        @endif
                    </div>

                    @if(!$user)
                    <div class="d-flex align-items-start gap-2 mt-3 p-2 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0">
                        <i class="bi bi-person-check-fill text-success mt-1" style="font-size:.85rem;flex-shrink:0"></i>
                        <span class="text-success" style="font-size:.78rem">
                            Akun akan dibuat otomatis dengan data di atas sehingga Anda dapat melacak pesanan kapan saja.
                        </span>
                    </div>
                    @else
                    <div class="d-flex align-items-center gap-2 mt-3" style="font-size:.8rem;color:#6b7280">
                        <i class="bi bi-person-check-fill text-success"></i>
                        Masuk sebagai <strong>{{ $user->name }}</strong>
                    </div>
                    @endif

                    <hr class="my-3">

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeTerms"
                               name="agree_terms" value="1" required
                               {{ old('agree_terms') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="agreeTerms">
                            Dengan melakukan pembelian ini, saya menyatakan telah membaca dan menyetujui
                            <a href="{{ route('tenant.legal.show', 'terms') }}" target="_blank" class="text-primary">
                                Syarat &amp; Ketentuan</a>.*
                        </label>
                    </div>
                </div>

                {{-- Payment Method --}}
                @php
                    $defaultMethod = old('payment_method',
                        $activeOnlineGateway ? $activeOnlineGateway->name : ($activeOfflineGateways->first()?->name ?? 'cash')
                    );
                @endphp
                <div class="co-card" x-data="{ pm: '{{ $defaultMethod }}' }">
                    <div class="co-card-title">Metode Pembayaran</div>

                    @if($activeOnlineGateway)
                    <div class="pm-group-lbl">Pembayaran Online</div>
                    <div class="pm-card" :class="{ 'pm-active': pm === '{{ $activeOnlineGateway->name }}' }"
                         @click="pm = '{{ $activeOnlineGateway->name }}'">
                        <input type="radio" name="payment_method" value="{{ $activeOnlineGateway->name }}"
                               x-model="pm" style="position:absolute;opacity:0;pointer-events:none">
                        <div class="pm-radio"><div class="pm-radio-dot"></div></div>
                        <div class="pm-icon pm-icon-online">
                            @if($activeOnlineGateway->name === 'midtrans')
                            <i class="bi bi-credit-card-2-front-fill"></i>
                            @else
                            <i class="bi bi-wallet2-fill"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            @if($activeOnlineGateway->name === 'midtrans')
                            <div class="pm-name">Midtrans</div>
                            <div class="pm-desc">Kartu kredit, transfer bank, GoPay, OVO, QRIS, dan lainnya</div>
                            @elseif($activeOnlineGateway->name === 'doku')
                            <div class="pm-name">DOKU</div>
                            <div class="pm-desc">Kartu kredit, virtual account, e-wallet</div>
                            @else
                            <div class="pm-name">{{ ucfirst($activeOnlineGateway->name) }}</div>
                            @endif
                        </div>
                        <span class="pm-badge pm-badge-online">Online</span>
                    </div>
                    @endif

                    @if($activeOfflineGateways->isNotEmpty())
                    @if($activeOnlineGateway)<div class="pm-group-lbl" style="margin-top:.75rem">Pembayaran Offline</div>@endif
                    @foreach($activeOfflineGateways as $offlineGw)
                    <div class="pm-card" :class="{ 'pm-active': pm === '{{ $offlineGw->name }}' }"
                         @click="pm = '{{ $offlineGw->name }}'">
                        <input type="radio" name="payment_method" value="{{ $offlineGw->name }}"
                               x-model="pm" style="position:absolute;opacity:0;pointer-events:none">
                        <div class="pm-radio"><div class="pm-radio-dot"></div></div>
                        <div class="pm-icon {{ $offlineGw->name === 'bank_transfer' ? 'pm-icon-bank' : 'pm-icon-cash' }}">
                            @if($offlineGw->name === 'cash')
                            <i class="bi bi-cash-stack"></i>
                            @elseif($offlineGw->name === 'bank_transfer')
                            <i class="bi bi-bank2"></i>
                            @else
                            <i class="bi bi-receipt"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            @if($offlineGw->name === 'cash')
                            <div class="pm-name">Bayar Tunai</div>
                            <div class="pm-desc">Pembayaran diterima langsung di lokasi oleh petugas</div>
                            @elseif($offlineGw->name === 'bank_transfer')
                            <div class="pm-name">Transfer Bank</div>
                            <div class="pm-desc">Transfer ke rekening kami, detail dikirim setelah checkout</div>
                            @else
                            <div class="pm-name">{{ ucfirst(str_replace('_', ' ', $offlineGw->name)) }}</div>
                            @endif
                        </div>
                        <span class="pm-badge {{ $offlineGw->name === 'bank_transfer' ? 'pm-badge-bank' : 'pm-badge-tunai' }}">
                            {{ $offlineGw->name === 'bank_transfer' ? 'Transfer' : 'Tunai' }}
                        </span>
                    </div>
                    @endforeach
                    @endif

                    @if(!$activeOnlineGateway && $activeOfflineGateways->isEmpty())
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Tidak ada metode pembayaran yang tersedia. Hubungi admin.
                    </div>
                    @endif

                    @error('payment_method')
                    <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                    @enderror
                </div>

            </form>
        </div>

        {{-- RIGHT: Order summary --}}
        <div class="col-lg-5">
            <div class="co-card">
                <div class="os-label">Order Summary</div>

                @if($isCart)
                    @foreach($items as $item)
                    @php
                        $itemPrice = ($item['slot']->price_adult ?? 0) * ($item['pax_adult'] ?? $item['pax'] ?? 1);
                        $itemChildPrice = ($item['slot']->price_child ?? 0) * ($item['pax_child'] ?? 0);
                    @endphp
                    <div class="{{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                        <div class="os-row">
                            <div>
                                <div class="os-name">{{ $item['slot']->product->name }}</div>
                                <div class="os-sub">
                                    {{ \Carbon\Carbon::parse($item['slot']->date)->translatedFormat('d M Y') }}
                                    · {{ \Carbon\Carbon::parse($item['slot']->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($item['slot']->end_time)->format('H:i') }}
                                </div>
                                @if(($item['pax_adult'] ?? 0) > 0)
                                <div class="os-sub">{{ $item['pax_adult'] }}× Dewasa</div>
                                @endif
                                @if(($item['pax_child'] ?? 0) > 0)
                                <div class="os-sub">{{ $item['pax_child'] }}× Anak</div>
                                @endif
                            </div>
                            <div class="os-price">IDR {{ number_format($itemPrice + $itemChildPrice, 0, ',', '.') }},-</div>
                        </div>
                        @foreach($item['addons'] ?? [] as $addon)
                        <div class="os-row mt-1">
                            <div>
                                <div style="font-size:.8rem;color:#374151">{{ $addon['name'] }}</div>
                                <div class="os-sub">{{ $addon['qty'] }}× item</div>
                            </div>
                            <div style="font-size:.8rem;color:#6b7280">
                                {{ ($addon['price'] ?? 0) > 0 ? 'IDR '.number_format(($addon['price'] ?? 0) * $addon['qty'], 0, ',', '.') . ',-' : 'Gratis' }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                @else
                    @php
                        $linePrice = ($slot->price_adult ?? 0) * $pax;
                    @endphp
                    <div class="os-row">
                        <div>
                            <div class="os-name">{{ $slot->product->name }}</div>
                            <div class="os-sub">
                                {{ \Carbon\Carbon::parse($slot->date)->translatedFormat('d M Y') }}
                                · {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                            </div>
                            <div class="os-sub">{{ $pax }}× Peserta</div>
                        </div>
                        <div class="os-price">IDR {{ number_format($linePrice, 0, ',', '.') }},-</div>
                    </div>
                    @if(isset($addonItems) && $addonItems->isNotEmpty())
                    @foreach($addonItems as $addon)
                    <div class="os-row mt-1">
                        <div>
                            <div style="font-size:.8rem;color:#374151">{{ $addon['name'] }}</div>
                            <div class="os-sub">{{ $addon['qty'] }}× item</div>
                        </div>
                        <div style="font-size:.8rem;color:#6b7280">
                            {{ $addon['price'] > 0 ? 'IDR '.number_format($addon['subtotal'], 0, ',', '.').',-' : 'Gratis' }}
                        </div>
                    </div>
                    @endforeach
                    @endif
                @endif

                <hr class="my-3">

                <div class="os-total-row" style="border-bottom:1px solid #f3f4f6">
                    <span class="os-total-lbl">Sub Total</span>
                    <span class="os-total-val">IDR {{ number_format($grandTotal, 0, ',', '.') }},-</span>
                </div>
                <div class="os-total-row">
                    <span class="os-grand-lbl">Grand Total</span>
                    <span class="os-grand-val">IDR {{ number_format($grandTotal, 0, ',', '.') }},-</span>
                </div>

                <hr class="my-3">

                {{-- Promo code --}}
                <div x-data="{ open: false }">
                    <button type="button" class="btn btn-link p-0 text-primary" style="font-size:.82rem;text-decoration:none"
                            @click="open = !open">
                        <i class="bi bi-tag me-1"></i>
                        Punya Kode Promo? <span x-text="open ? 'Tutup' : 'Klik di sini'"></span>
                    </button>
                    <div x-show="open" x-collapse class="mt-2">
                        <div class="input-group">
                            <input type="text" name="promo_code" form="checkoutForm"
                                   class="form-control form-control-sm"
                                   value="{{ old('promo_code') }}"
                                   placeholder="Masukkan kode promo">
                            <button class="btn btn-outline-primary btn-sm" type="button">Terapkan</button>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-2">
                <div class="col-5 d-flex">
                    <a href="{{ route('tenant.cart.index') }}"
                       class="btn w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2"
                       style="background:#f3f4f6;color:#374151;border:none">
                        <i class="bi bi-arrow-left" style="font-size:.85rem"></i> Kembali
                    </a>
                </div>
                <div class="col-7 d-flex">
                    <button type="submit" form="checkoutForm"
                            class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        Lanjut Pembayaran <i class="bi bi-arrow-right" style="font-size:.85rem"></i>
                    </button>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
