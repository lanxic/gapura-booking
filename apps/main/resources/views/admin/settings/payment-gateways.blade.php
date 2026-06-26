@extends('layouts.admin')

@section('title', 'Metode Pembayaran')

@section('content')

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('admin.settings.general') }}">Umum</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('admin.settings.payment-gateways') }}">Metode Pembayaran</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('admin.settings.legal') }}">Legal</a>
    </li>
</ul>

{{-- Online Gateways --}}
<h6 class="fw-bold mb-3">Pembayaran Online</h6>
<p class="text-muted small mb-3">Hanya satu gateway online yang bisa aktif sekaligus.</p>

<div class="row g-3 mb-5">
    @foreach($online as $gw)
    <div class="col-md-6">
        <div class="card {{ $gw->is_active ? 'border-primary' : '' }}" x-data="{ open: false }">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="fw-bold">{{ strtoupper($gw->name) }}</span>
                        @if($gw->is_active)
                            <span class="badge bg-primary ms-2">Aktif</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" @click="open = !open">
                            <i class="bi bi-gear"></i>
                        </button>
                        <form method="POST" action="{{ route('admin.settings.payment-gateways.activate', $gw->name) }}">
                            @csrf
                            <button class="btn btn-sm {{ $gw->is_active ? 'btn-danger' : 'btn-success' }}">
                                {{ $gw->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </div>
                </div>

                <div x-show="open" x-collapse class="mt-3">
                    <form method="POST" action="{{ route('admin.settings.payment-gateways.update', $gw->name) }}">
                        @csrf @method('PUT')
                        @foreach($gw->config ?? [] as $key => $value)
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                            <input type="text" name="config[{{ $key }}]" class="form-control form-control-sm"
                                   value="{{ $value }}" placeholder="{{ $key }}">
                        </div>
                        @endforeach
                        <button class="btn btn-sm btn-primary mt-1">Simpan Konfigurasi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Offline Gateways --}}
<h6 class="fw-bold mb-3">Pembayaran Offline</h6>
<p class="text-muted small mb-3">Metode offline dapat aktif bersamaan.</p>

<div class="row g-3">
    @foreach($offline as $gw)
    <div class="col-md-6">
        <div class="card {{ $gw->is_active ? 'border-success' : '' }}">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold">{{ ucwords(str_replace('_', ' ', $gw->name)) }}</span>
                    @if($gw->is_active)
                        <span class="badge bg-success ms-2">Aktif</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.settings.payment-gateways.activate', $gw->name) }}">
                    @csrf
                    <button class="btn btn-sm {{ $gw->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                        {{ $gw->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection
