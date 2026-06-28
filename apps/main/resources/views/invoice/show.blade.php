@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_code)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7"
             x-data="{
                status: '{{ $invoice->status }}',
                polling: {{ in_array($invoice->status, ['pending','awaiting_payment']) ? 'true' : 'false' }},
                init() {
                    if (this.polling) this.poll();
                },
                poll() {
                    setInterval(async () => {
                        if (!this.polling) return;
                        const res = await fetch('/api/v1/invoices/{{ $invoice->invoice_code }}');
                        const data = await res.json();
                        this.status = data.data?.status ?? this.status;
                        if (!['pending','awaiting_payment'].includes(this.status)) {
                            this.polling = false;
                            window.location.reload();
                        }
                    }, 5000);
                }
             }">

            <div class="card">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="fw-bold mb-1">Invoice</h4>
                            <p class="text-muted mb-0 small font-monospace">{{ $invoice->invoice_code }}</p>
                        </div>
                        <span class="invoice-status-badge badge"
                              :class="{
                                'bg-warning text-dark': ['pending','awaiting_payment'].includes(status),
                                'bg-success': status === 'paid',
                                'bg-danger': ['expired','cancelled'].includes(status),
                                'bg-info': status === 'partial'
                              }"
                              x-text="status">{{ $invoice->status }}</span>
                    </div>

                    {{-- Polling indicator --}}
                    <template x-if="polling">
                        <div class="alert alert-info py-2 d-flex align-items-center gap-2 small mb-4">
                            <div class="spinner-border spinner-border-sm text-info"></div>
                            <span>Menunggu konfirmasi pembayaran...</span>
                        </div>
                    </template>

                    {{-- Booking info --}}
                    @if($invoice->booking)
                    <div class="alert alert-success py-2 small mb-4">
                        <i class="bi bi-check-circle me-1"></i>
                        Booking dikonfirmasi: <strong>{{ $invoice->booking->booking_code }}</strong>
                        — <a href="{{ route('account.booking.detail', $invoice->booking->booking_code) }}" class="alert-link">Lihat tiket</a>
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
                        <span class="small">{{ $item['name'] ?? '' }} × {{ $item['qty'] ?? 1 }}</span>
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
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total</span>
                        <span class="text-primary">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>

                    {{-- Midtrans Snap --}}
                    @if(session('snap_token') && in_array($invoice->status, ['pending','awaiting_payment']))
                    <div class="mt-4">
                        <button id="pay-button" class="btn btn-success w-100 fw-semibold">
                            <i class="bi bi-credit-card me-2"></i>Bayar Sekarang
                        </button>
                    </div>
                    @push('head')
                    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
                    @endpush
                    @push('scripts')
                    <script>
                        document.getElementById('pay-button').addEventListener('click', function () {
                            snap.pay('{{ session('snap_token') }}', {
                                onSuccess: () => window.location.reload(),
                                onPending: () => window.location.reload(),
                                onError: () => alert('Pembayaran gagal.'),
                            });
                        });
                    </script>
                    @endpush
                    @elseif(in_array($invoice->status, ['pending','awaiting_payment']))
                    <form method="POST" action="{{ route('invoice.retry', $invoice->invoice_code) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-clockwise me-2"></i>Coba Bayar Ulang
                        </button>
                    </form>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
