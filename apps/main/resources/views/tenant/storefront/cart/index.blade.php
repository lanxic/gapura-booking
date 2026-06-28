@extends('layouts.tenant-storefront')

@section('title', 'Keranjang Saya')

@section('content')
<div class="container py-5" style="max-width:960px">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="fw-bold text-primary mb-0">Keranjang Saya</h2>
        <a href="{{ route('tenant.home') }}" class="btn btn-outline-secondary btn-sm px-4">
            Kembali ke Beranda
        </a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($items->isEmpty())
    <div class="text-center py-5">
        <i class="bi bi-bag-x text-muted" style="font-size:3rem"></i>
        <p class="text-muted mt-3">Keranjang kamu kosong.</p>
        <a href="{{ route('tenant.products.index') }}" class="btn btn-primary mt-2">Lihat Produk</a>
    </div>
    @else

    <div class="row g-4 align-items-start">

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0">
                    @foreach($items as $item)
                    <div class="p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-semibold mb-1" style="font-size:.95rem">
                                    {{ $item['slot']->product->name }}
                                </div>
                                <div class="text-muted small mb-2">
                                    {{ \Carbon\Carbon::parse($item['slot']->start_time)->format('H:i') }}
                                    – {{ \Carbon\Carbon::parse($item['slot']->end_time)->format('H:i') }}
                                </div>

                                <div class="small text-muted mb-1">
                                    <span class="fw-semibold text-dark">Kuantitas:</span>
                                </div>
                                @if(($item['pax_adult'] ?? 0) > 0)
                                <div class="small text-muted">{{ $item['pax_adult'] }} X Adult — Rp {{ number_format($item['price_adult'] ?? 0, 0, ',', '.') }}</div>
                                @endif
                                @if(($item['pax_child'] ?? 0) > 0)
                                <div class="small text-muted">{{ $item['pax_child'] }} X Child — Rp {{ number_format($item['price_child'] ?? 0, 0, ',', '.') }}</div>
                                @endif

                                <div class="small text-muted mt-2 mb-1">
                                    <span class="fw-semibold text-dark">Tanggal Kunjungan:</span>
                                </div>
                                <div class="small text-muted">
                                    {{ \Carbon\Carbon::parse($item['slot']->date)->format('d M Y') }}
                                </div>

                                @if(isset($item['addons']) && $item['addons']->isNotEmpty())
                                <div class="small text-muted mt-2">
                                    <span class="fw-semibold text-dark">Tambahan:</span>
                                    @foreach($item['addons'] as $addon)
                                    <div>{{ $addon['qty'] }}× {{ $addon['name'] }} — Rp {{ number_format($addon['subtotal'], 0, ',', '.') }}</div>
                                    @endforeach
                                </div>
                                @endif

                                <form method="POST"
                                      action="{{ route('tenant.cart.remove', $item['index']) }}"
                                      class="mt-3 d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-link p-0 text-danger"
                                            style="font-size:.85rem;text-decoration:none"
                                            onclick="return confirm('Hapus item ini dari keranjang?')">
                                        <i class="bi bi-trash3 me-1"></i>Hapus
                                    </button>
                                </form>
                            </div>

                            <div class="text-end flex-shrink-0">
                                <div class="fw-semibold" style="font-size:.95rem">
                                    IDR {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom">Ringkasan</h6>

                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Sub Total ({{ $items->count() }} barang)</span>
                        <span>IDR {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>

                    <div class="d-flex justify-content-between fw-bold mt-3 pt-2 border-top">
                        <span>Total</span>
                        <span class="text-primary" style="font-size:1.05rem">
                            IDR {{ number_format($grandTotal, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-center gap-3 mt-5">
        <a href="{{ route('tenant.products.index') }}"
           class="btn btn-outline-secondary px-5 py-2 fw-semibold">
            Kembali
        </a>
        <a href="{{ route('tenant.checkout.index') }}"
           class="btn btn-primary px-5 py-2 fw-semibold">
            Langkah Berikutnya
        </a>
    </div>

    @endif

</div>
@endsection
