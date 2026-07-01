@extends('layouts.tenant-storefront')

@section('title', 'Invoice ' . $invoice->invoice_code)

@php
    $isOnlinePending  = $invoice->status === 'pending' && $gateway?->type === 'online';
    $hasSnapToken     = (bool) session('snap_token');
    $isOfflinePending = in_array($invoice->status, ['pending', 'draft']) && $gateway?->type === 'offline';
    $isPaid           = $invoice->status === 'paid';
    $isFailed         = in_array($invoice->status, ['expired', 'failed', 'cancelled']);
@endphp

@push('head')
@if($hasSnapToken && $isOnlinePending)
<script src="{{ $gateway->config['snap_url'] ?? 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ $gateway->client_key }}"></script>
@endif
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8"
             x-data="{
                 status: '{{ $invoice->status }}',
                 paying: false,
                 poll() {
                     setInterval(async () => {
                         try {
                             const res  = await fetch('/api/v1/invoices/{{ $invoice->invoice_code }}');
                             const data = await res.json();
                             const s    = data.data?.status;
                             if (s) this.status = s;
                             if (!['pending'].includes(s)) {
                                 window.location.reload();
                             }
                         } catch {}
                     }, 4000);
                 },
                 @if($hasSnapToken && $isOnlinePending)
                 payNow() {
                     this.paying = true;
                     snap.pay('{{ session('snap_token') }}', {
                         onSuccess: () => window.location.reload(),
                         onPending: () => window.location.reload(),
                         onError:   () => { this.paying = false; },
                         onClose:   () => { if (this.paying) this.poll(); },
                     });
                 }
                 @endif
             }">

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">

                    {{-- ── Header ──────────────────────────────────────────── --}}
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <div class="text-muted small mb-1">Nomor Invoice</div>
                            <div class="fw-bold font-monospace fs-6">{{ $invoice->invoice_code }}</div>
                        </div>
                        @php
                            $statusMap = [
                                'pending'   => ['bg-warning text-dark', 'pending'],
                                'paid'      => ['bg-success text-white', 'lunas'],
                                'expired'   => ['bg-secondary text-white', 'expired'],
                                'failed'    => ['bg-danger text-white', 'gagal'],
                                'cancelled' => ['bg-secondary text-white', 'dibatalkan'],
                                'draft'     => ['bg-light text-muted border', 'draft'],
                            ];
                            [$badgeClass, $badgeLabel] = $statusMap[$invoice->status] ?? ['bg-secondary text-white', $invoice->status];
                        @endphp
                        <span class="badge px-3 py-2 rounded-pill {{ $badgeClass }}" style="font-size:.8rem"
                              :class="{
                                  'bg-warning text-dark': status === 'pending',
                                  'bg-success text-white': status === 'paid',
                                  'bg-secondary text-white': ['expired','cancelled','draft'].includes(status),
                                  'bg-danger text-white': status === 'failed',
                              }"
                              x-text="status === 'paid' ? 'lunas' : status === 'failed' ? 'gagal' : status === 'cancelled' ? 'dibatalkan' : status">
                            {{ $badgeLabel }}
                        </span>
                    </div>

                    {{-- ── Status alerts ───────────────────────────────────── --}}
                    @if($isPaid && $invoice->booking)
                    <div class="alert alert-success border-0 rounded-3 py-3 mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
                        <div>
                            <div class="fw-semibold small">Pembayaran berhasil!</div>
                            <div class="small">Kode booking: <strong>{{ $invoice->booking->booking_code }}</strong>
                                — <a href="{{ route('tenant.account.booking.detail', $invoice->booking->booking_code) }}" class="alert-link">Lihat tiket</a>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($isFailed)
                    <div class="alert alert-danger border-0 rounded-3 py-3 mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-x-circle-fill fs-5 flex-shrink-0"></i>
                        <div class="small fw-semibold">Invoice ini sudah {{ $badgeLabel }}.</div>
                    </div>
                    @endif

                    {{-- Spinner: hanya muncul setelah user klik Bayar --}}
                    @if($isOnlinePending)
                    <template x-if="paying">
                        <div class="alert border-0 rounded-3 py-2 mb-4 d-flex align-items-center gap-2 small"
                             style="background:#f0f9ff;color:#0369a1">
                            <div class="spinner-border spinner-border-sm flex-shrink-0" style="color:#0369a1"></div>
                            <span>Memverifikasi pembayaran, mohon tunggu...</span>
                        </div>
                    </template>
                    @endif

                    <hr class="my-3">

                    {{-- ── Pemesan ─────────────────────────────────────────── --}}
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Pemesan</div>
                        <div class="fw-semibold">{{ $invoice->guest_name }}</div>
                        <div class="small text-muted">{{ $invoice->guest_email }}</div>
                    </div>

                    @if($invoice->due_at && !$isPaid && !$isFailed)
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Batas Pembayaran</div>
                        <div class="fw-semibold">{{ $invoice->due_at->format('d M Y, H:i') }}</div>
                    </div>
                    @endif

                    <hr class="my-3">

                    {{-- ── Item list ───────────────────────────────────────── --}}
                    <div class="mb-3">
                        @foreach($invoice->items ?? [] as $item)
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <span class="small">{{ $item['name'] ?? '' }} &times; {{ $item['qty'] ?? $item['quantity'] ?? 1 }}</span>
                            <span class="small fw-semibold text-nowrap ms-3">Rp&nbsp;{{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @endforeach

                        @if(($invoice->discount_amount ?? 0) > 0)
                        <div class="d-flex justify-content-between align-items-baseline mb-2 text-success">
                            <span class="small">Diskon</span>
                            <span class="small fw-semibold text-nowrap ms-3">− Rp&nbsp;{{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-3 border-top border-bottom mb-4">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold fs-5 text-primary">Rp&nbsp;{{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>

                    {{-- ── Payment actions ─────────────────────────────────── --}}

                    {{-- Online: ada snap token → tombol bayar --}}
                    @if($hasSnapToken && $isOnlinePending)
                    <button @click="payNow()" :disabled="paying"
                            class="btn btn-success btn-lg w-100 fw-semibold mb-2 d-flex align-items-center justify-content-center gap-2">
                        <template x-if="!paying">
                            <span><i class="bi bi-credit-card me-2"></i>Bayar Sekarang</span>
                        </template>
                        <template x-if="paying">
                            <span>
                                <span class="spinner-border spinner-border-sm me-2"></span>Memproses...
                            </span>
                        </template>
                    </button>

                    {{-- Online: snap token habis → retry --}}
                    @elseif($isOnlinePending)
                    <form method="POST" action="{{ route('tenant.invoice.retry', $invoice->invoice_code) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-lg w-100 fw-semibold">
                            <i class="bi bi-arrow-clockwise me-2"></i>Buka Halaman Pembayaran
                        </button>
                    </form>
                    @endif

                    {{-- Offline: instruksi pembayaran --}}
                    @if($isOfflinePending)
                    @if($gateway->name === 'cash')
                    <div class="rounded-3 p-3 mb-3 d-flex gap-3 align-items-start" style="background:#fffbeb;border:1px solid #fde68a">
                        <i class="bi bi-cash-coin fs-4 text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <div class="fw-semibold small mb-1">Pembayaran Tunai</div>
                            <p class="small mb-0 text-muted">
                                {{ $gateway->notes ?: 'Lakukan pembayaran tunai kepada petugas di lokasi saat check-in.' }}
                            </p>
                        </div>
                    </div>
                    @endif

                    @if($gateway->name === 'bank_transfer')
                    @php $btConfig = $gateway->config ?? []; @endphp
                    <div class="rounded-3 p-3 mb-3" style="background:#f0f7ff;border:1px solid #bfdbfe">
                        <div class="d-flex gap-3 align-items-start mb-2">
                            <i class="bi bi-bank fs-4 text-primary flex-shrink-0 mt-1"></i>
                            <div class="fw-semibold small">Transfer Bank</div>
                        </div>
                        <table class="table table-sm table-borderless mb-2 small">
                            @if(!empty($btConfig['bank_name']))
                            <tr>
                                <td class="text-muted ps-0" style="width:40%">Bank</td>
                                <td class="fw-semibold">{{ $btConfig['bank_name'] }}</td>
                            </tr>
                            @endif
                            @if(!empty($btConfig['account_name']))
                            <tr>
                                <td class="text-muted ps-0">Atas Nama</td>
                                <td class="fw-semibold">{{ $btConfig['account_name'] }}</td>
                            </tr>
                            @endif
                            @if(!empty($btConfig['account_number']))
                            <tr>
                                <td class="text-muted ps-0">No. Rekening</td>
                                <td>
                                    <span class="fw-bold font-monospace">{{ $btConfig['account_number'] }}</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-primary"
                                            onclick="navigator.clipboard.writeText('{{ $btConfig['account_number'] }}').then(() => { this.innerHTML='<i class=\'bi bi-check-lg\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i>', 2000); })">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            @endif
                        </table>
                        <div class="small text-muted">
                            Transfer tepat <strong class="text-primary">Rp&nbsp;{{ number_format($invoice->total_amount, 0, ',', '.') }}</strong>
                            {{ $gateway->notes ? '— ' . $gateway->notes : '— Kirim bukti transfer untuk konfirmasi.' }}
                        </div>
                    </div>
                    @endif
                    @endif

                    <div class="text-center mt-3">
                        <a href="{{ route('tenant.home') }}" class="btn btn-outline-secondary btn-sm px-4">
                            Kembali ke Beranda
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
