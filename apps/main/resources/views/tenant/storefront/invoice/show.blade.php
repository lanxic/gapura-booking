@extends('layouts.tenant-storefront')

@section('title', 'Invoice ' . $invoice->invoice_code)

@push('head')
@if(session('snap_token') && $gateway?->type === 'online' && in_array($invoice->status, ['pending']))
<script src="{{ $gateway->config['snap_url'] ?? 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ $gateway->client_key }}"></script>
@endif
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7"
             x-data="{
                status: '{{ $invoice->status }}',
                polling: {{ in_array($invoice->status, ['pending']) && $gateway?->type !== 'offline' ? 'true' : 'false' }},
                init() { if (this.polling) this.poll(); },
                poll() {
                    setInterval(async () => {
                        if (!this.polling) return;
                        const res  = await fetch('/api/v1/invoices/{{ $invoice->invoice_code }}');
                        const data = await res.json();
                        this.status = data.data?.status ?? this.status;
                        if (!['pending'].includes(this.status)) {
                            this.polling = false;
                            window.location.reload();
                        }
                    }, 5000);
                }
             }">

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">

                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="fw-bold mb-1">Invoice</h4>
                            <p class="text-muted mb-0 small font-monospace">{{ $invoice->invoice_code }}</p>
                        </div>
                        <span class="badge fs-6 px-3 py-2"
                              :class="{
                                'bg-warning text-dark': status === 'pending',
                                'bg-success':           status === 'paid',
                                'bg-danger':            ['expired','failed','cancelled'].includes(status),
                              }"
                              x-text="status">{{ $invoice->status }}</span>
                    </div>

                    {{-- Polling indicator (online payment only) --}}
                    <template x-if="polling">
                        <div class="alert alert-info py-2 d-flex align-items-center gap-2 small mb-4">
                            <div class="spinner-border spinner-border-sm text-info"></div>
                            <span>Menunggu konfirmasi pembayaran...</span>
                        </div>
                    </template>

                    {{-- Booking confirmed --}}
                    @if($invoice->booking)
                    <div class="alert alert-success py-2 small mb-4">
                        <i class="bi bi-check-circle me-1"></i>
                        Booking dikonfirmasi: <strong>{{ $invoice->booking->booking_code }}</strong>
                        — <a href="{{ route('tenant.account.booking.detail', $invoice->booking->booking_code) }}" class="alert-link">Lihat tiket</a>
                    </div>
                    @endif

                    <hr>

                    <div class="mb-3">
                        <div class="text-muted small mb-1">Pemesan</div>
                        <div class="fw-semibold">{{ $invoice->guest_name }}</div>
                        <div class="small text-muted">{{ $invoice->guest_email }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small mb-1">Jatuh Tempo</div>
                        <div class="fw-semibold">{{ $invoice->due_at?->format('d M Y H:i') }}</div>
                    </div>

                    <hr>

                    @foreach($invoice->items ?? [] as $item)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small">{{ $item['name'] ?? '' }} × {{ $item['qty'] ?? $item['quantity'] ?? 1 }}</span>
                        <span class="small">Rp {{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @endforeach

                    @if($invoice->discount_amount > 0)
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span class="small">Diskon</span>
                        <span class="small">− Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
                        <span>Total</span>
                        <span class="text-primary">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>

                    {{-- ═══ MIDTRANS SNAP (Online Payment) ═══ --}}
                    @if(session('snap_token') && $gateway?->type === 'online' && in_array($invoice->status, ['pending']))
                    <div class="d-grid mb-2">
                        <button id="pay-button" class="btn btn-success btn-lg fw-semibold">
                            <i class="bi bi-credit-card me-2"></i>Bayar Sekarang
                        </button>
                    </div>
                    @push('scripts')
                    <script>
                        document.getElementById('pay-button').addEventListener('click', function () {
                            snap.pay('{{ session('snap_token') }}', {
                                onSuccess: () => window.location.reload(),
                                onPending: () => window.location.reload(),
                                onError:   () => alert('Pembayaran gagal. Silakan coba lagi.'),
                                onClose:   () => {},
                            });
                        });
                    </script>
                    @endpush

                    @elseif($gateway?->type === 'online' && in_array($invoice->status, ['pending']))
                    {{-- Snap token expired / belum ada, bisa retry --}}
                    <form method="POST" action="{{ route('tenant.invoice.retry', $invoice->invoice_code) }}" class="d-grid mb-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-arrow-clockwise me-2"></i>Buka Halaman Pembayaran
                        </button>
                    </form>
                    @endif

                    {{-- ═══ OFFLINE PAYMENT INSTRUCTIONS ═══ --}}
                    @if($gateway?->type === 'offline' && in_array($invoice->status, ['pending', 'draft']))

                    @if($gateway->name === 'cash')
                    <div class="alert alert-warning border-0 mb-3" style="background:#fffbeb">
                        <div class="fw-semibold mb-2"><i class="bi bi-cash-coin me-2"></i>Instruksi Pembayaran Tunai</div>
                        @if($gateway->notes)
                        <p class="small mb-0">{{ $gateway->notes }}</p>
                        @else
                        <p class="small mb-0">Lakukan pembayaran tunai secara langsung kepada petugas di lokasi pada saat check-in.</p>
                        @endif
                    </div>
                    @endif

                    @if($gateway->name === 'bank_transfer')
                    @php $btConfig = $gateway->config ?? []; @endphp
                    <div class="alert alert-info border-0 mb-3" style="background:#f0f7ff">
                        <div class="fw-semibold mb-2"><i class="bi bi-bank me-2"></i>Instruksi Transfer Bank</div>
                        @if(!empty($btConfig['bank_name']) || !empty($btConfig['account_number']))
                        <table class="table table-sm table-borderless mb-1 small">
                            @if(!empty($btConfig['bank_name']))
                            <tr><td class="text-muted ps-0" style="width:40%">Bank</td><td class="fw-semibold">{{ $btConfig['bank_name'] }}</td></tr>
                            @endif
                            @if(!empty($btConfig['account_name']))
                            <tr><td class="text-muted ps-0">Atas Nama</td><td class="fw-semibold">{{ $btConfig['account_name'] }}</td></tr>
                            @endif
                            @if(!empty($btConfig['account_number']))
                            <tr>
                                <td class="text-muted ps-0">No. Rekening</td>
                                <td>
                                    <span class="fw-bold font-monospace">{{ $btConfig['account_number'] }}</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-primary"
                                            onclick="navigator.clipboard.writeText('{{ $btConfig['account_number'] }}');this.innerHTML='<i class=\'bi bi-check\'></i>'">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            @endif
                        </table>
                        @endif
                        <p class="small mb-1">Transfer tepat sebesar:
                            <strong class="text-primary">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong>
                        </p>
                        @if($gateway->notes)
                        <p class="small mb-0 text-muted">{{ $gateway->notes }}</p>
                        @else
                        <p class="small mb-0 text-muted">Kirimkan bukti transfer via WhatsApp/email. Booking dikonfirmasi setelah pembayaran diverifikasi.</p>
                        @endif
                    </div>
                    @endif

                    @endif
                    {{-- ── end offline instructions ── --}}

                    <div class="mt-3 text-center">
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
