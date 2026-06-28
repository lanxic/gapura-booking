@extends('layouts.admin')

@section('title', 'Metode Pembayaran')

@section('content')

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.general') }}">Umum</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.storefront') }}">Storefront</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.social') }}">Sosial Media</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.settings.payment-gateways') }}">Metode Pembayaran</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.legal') }}">Legal</a></li>
</ul>

{{-- Online Gateways --}}
<h6 class="fw-bold mb-3">Pembayaran Online</h6>
<p class="text-muted small mb-3">Hanya satu gateway online yang bisa aktif sekaligus.</p>

@php
$dualEnvGateways = ['midtrans', 'doku'];
$gatewayLabels = [
    'midtrans' => [
        'merchant_id' => 'Merchant ID',
        'server_key'  => 'Server Key',
        'client_key'  => 'Client Key',
        'sandbox_placeholder_merchant' => 'G123456789',
        'sandbox_placeholder_server'   => 'SB-Mid-server-xxxx',
        'sandbox_placeholder_client'   => 'SB-Mid-client-xxxx',
        'production_placeholder_server'=> 'Mid-server-xxxx',
        'production_placeholder_client'=> 'Mid-client-xxxx',
    ],
    'doku' => [
        'merchant_id' => 'Client ID',
        'server_key'  => 'Secret Key',
        'client_key'  => null,
        'sandbox_placeholder_merchant' => 'MCH-xxxx',
        'sandbox_placeholder_server'   => 'SK-sandbox-xxxx',
        'sandbox_placeholder_client'   => null,
        'production_placeholder_server'=> 'SK-xxxx',
        'production_placeholder_client'=> null,
    ],
];
@endphp

<div class="row g-3 mb-5">
    @foreach($online as $gw)
    <div class="{{ in_array($gw->name, $dualEnvGateways) ? 'col-12' : 'col-md-6' }}">
        <div class="card {{ $gw->is_active ? 'border-primary' : '' }}" x-data="{ open: false }">
            <div class="card-body p-3">

                {{-- Card header --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="fw-bold">{{ strtoupper($gw->name) }}</span>
                        @if($gw->is_active)
                            <span class="badge bg-primary ms-2">Aktif</span>
                        @endif
                        @if(in_array($gw->name, $dualEnvGateways))
                            <span class="badge {{ $gw->environment === 'production' ? 'bg-success' : 'bg-warning text-dark' }} ms-1">
                                {{ ucfirst($gw->environment) }}
                            </span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="open = !open">
                            <i class="bi bi-gear me-1"></i>Konfigurasi
                        </button>
                        <form method="POST" action="{{ route('admin.settings.payment-gateways.activate', $gw->name) }}">
                            @csrf
                            <button class="btn btn-sm {{ $gw->is_active ? 'btn-danger' : 'btn-success' }}">
                                {{ $gw->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Dual-environment form (Midtrans & DOKU) --}}
                @if(in_array($gw->name, $dualEnvGateways))
                @php $labels = $gatewayLabels[$gw->name]; @endphp
                <div x-show="open" x-cloak class="mt-3">
                    <form method="POST" action="{{ route('admin.settings.payment-gateways.update', $gw->name) }}">
                        @csrf @method('PUT')

                        {{-- Environment aktif --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Environment Aktif</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="environment"
                                           value="sandbox" id="{{ $gw->name }}_env_sandbox"
                                           {{ $gw->environment === 'sandbox' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="{{ $gw->name }}_env_sandbox">
                                        <span class="badge bg-warning text-dark">Sandbox</span>
                                        <span class="text-muted small ms-1">— untuk testing</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="environment"
                                           value="production" id="{{ $gw->name }}_env_production"
                                           {{ $gw->environment === 'production' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="{{ $gw->name }}_env_production">
                                        <span class="badge bg-success">Production</span>
                                        <span class="text-muted small ms-1">— transaksi nyata</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            {{-- Sandbox panel --}}
                            <div class="col-md-6">
                                <div class="p-3 rounded border border-warning-subtle bg-warning-subtle">
                                    <p class="small fw-semibold mb-2 text-warning-emphasis">
                                        <i class="bi bi-bug-fill me-1"></i>Sandbox (Testing)
                                    </p>

                                    <div class="mb-2">
                                        <label class="form-label small mb-1">{{ $labels['merchant_id'] }}</label>
                                        <input type="text" name="sandbox[merchant_id]"
                                               class="form-control form-control-sm"
                                               value="{{ $gw->config['sandbox']['merchant_id'] ?? '' }}"
                                               placeholder="{{ $labels['sandbox_placeholder_merchant'] }}">
                                    </div>

                                    <div class="mb-2" x-data="{ show: false }">
                                        <label class="form-label small mb-1">{{ $labels['server_key'] }}</label>
                                        <div class="input-group input-group-sm">
                                            <input :type="show ? 'text' : 'password'"
                                                   name="sandbox[server_key]"
                                                   class="form-control form-control-sm"
                                                   placeholder="{{ !empty($gw->config['sandbox']['server_key']) ? '••••••• (sudah diset)' : $labels['sandbox_placeholder_server'] }}"
                                                   autocomplete="new-password">
                                            <button type="button" class="btn btn-outline-secondary" @click="show = !show">
                                                <i class="bi bi-eye" x-show="!show"></i>
                                                <i class="bi bi-eye-slash" x-show="show" x-cloak></i>
                                            </button>
                                        </div>
                                        @if(!empty($gw->config['sandbox']['server_key']))
                                            <div class="form-text">Kosongkan jika tidak ingin mengubah.</div>
                                        @endif
                                    </div>

                                    @if($labels['client_key'])
                                    <div class="mb-0" x-data="{ show: false }">
                                        <label class="form-label small mb-1">{{ $labels['client_key'] }}</label>
                                        <div class="input-group input-group-sm">
                                            <input :type="show ? 'text' : 'password'"
                                                   name="sandbox[client_key]"
                                                   class="form-control form-control-sm"
                                                   placeholder="{{ !empty($gw->config['sandbox']['client_key']) ? '••••••• (sudah diset)' : $labels['sandbox_placeholder_client'] }}"
                                                   autocomplete="new-password">
                                            <button type="button" class="btn btn-outline-secondary" @click="show = !show">
                                                <i class="bi bi-eye" x-show="!show"></i>
                                                <i class="bi bi-eye-slash" x-show="show" x-cloak></i>
                                            </button>
                                        </div>
                                        @if(!empty($gw->config['sandbox']['client_key']))
                                            <div class="form-text">Kosongkan jika tidak ingin mengubah.</div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Production panel --}}
                            <div class="col-md-6">
                                <div class="p-3 rounded border border-success-subtle bg-success-subtle">
                                    <p class="small fw-semibold mb-2 text-success-emphasis">
                                        <i class="bi bi-globe me-1"></i>Production (Live)
                                    </p>

                                    <div class="mb-2">
                                        <label class="form-label small mb-1">{{ $labels['merchant_id'] }}</label>
                                        <input type="text" name="production[merchant_id]"
                                               class="form-control form-control-sm"
                                               value="{{ $gw->config['production']['merchant_id'] ?? '' }}"
                                               placeholder="{{ $labels['sandbox_placeholder_merchant'] }}">
                                    </div>

                                    <div class="mb-2" x-data="{ show: false }">
                                        <label class="form-label small mb-1">{{ $labels['server_key'] }}</label>
                                        <div class="input-group input-group-sm">
                                            <input :type="show ? 'text' : 'password'"
                                                   name="production[server_key]"
                                                   class="form-control form-control-sm"
                                                   placeholder="{{ !empty($gw->config['production']['server_key']) ? '••••••• (sudah diset)' : $labels['production_placeholder_server'] }}"
                                                   autocomplete="new-password">
                                            <button type="button" class="btn btn-outline-secondary" @click="show = !show">
                                                <i class="bi bi-eye" x-show="!show"></i>
                                                <i class="bi bi-eye-slash" x-show="show" x-cloak></i>
                                            </button>
                                        </div>
                                        @if(!empty($gw->config['production']['server_key']))
                                            <div class="form-text">Kosongkan jika tidak ingin mengubah.</div>
                                        @endif
                                    </div>

                                    @if($labels['client_key'])
                                    <div class="mb-0" x-data="{ show: false }">
                                        <label class="form-label small mb-1">{{ $labels['client_key'] }}</label>
                                        <div class="input-group input-group-sm">
                                            <input :type="show ? 'text' : 'password'"
                                                   name="production[client_key]"
                                                   class="form-control form-control-sm"
                                                   placeholder="{{ !empty($gw->config['production']['client_key']) ? '••••••• (sudah diset)' : $labels['production_placeholder_client'] }}"
                                                   autocomplete="new-password">
                                            <button type="button" class="btn btn-outline-secondary" @click="show = !show">
                                                <i class="bi bi-eye" x-show="!show"></i>
                                                <i class="bi bi-eye-slash" x-show="show" x-cloak></i>
                                            </button>
                                        </div>
                                        @if(!empty($gw->config['production']['client_key']))
                                            <div class="form-text">Kosongkan jika tidak ingin mengubah.</div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-sm btn-primary">
                                <i class="bi bi-floppy me-1"></i>Simpan Konfigurasi
                            </button>
                            <span class="text-muted small ms-2">
                                Kredensial environment aktif akan otomatis digunakan saat transaksi.
                            </span>
                        </div>
                    </form>
                </div>

                {{-- Other online gateways: generic config --}}
                @else
                <div x-show="open" x-cloak class="mt-3">
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
                @endif

            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Offline Gateways --}}
<h6 class="fw-bold mb-3">Pembayaran Offline</h6>
<p class="text-muted small mb-3">Metode offline dapat aktif bersamaan.</p>

@php
$offlineMeta = [
    'cash' => [
        'label' => 'Tunai (Cash)',
        'icon'  => 'bi-cash-coin',
        'desc'  => 'Pembayaran diterima langsung oleh petugas di lokasi.',
    ],
    'bank_transfer' => [
        'label' => 'Transfer Bank',
        'icon'  => 'bi-bank',
        'desc'  => 'Pembayaran via transfer ke rekening bank.',
    ],
];
@endphp

<div class="row g-3">
    @foreach($offline as $gw)
    @php $meta = $offlineMeta[$gw->name] ?? ['label' => ucwords(str_replace('_', ' ', $gw->name)), 'icon' => 'bi-credit-card', 'desc' => '']; @endphp
    <div class="col-md-6">
        <div class="card h-100 {{ $gw->is_active ? 'border-success' : '' }}" x-data="{ open: false }">
            <div class="card-body p-3">

                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi {{ $meta['icon'] }} fs-4 text-secondary"></i>
                        <div>
                            <span class="fw-bold d-block">{{ $meta['label'] }}</span>
                            <span class="text-muted small">{{ $meta['desc'] }}</span>
                        </div>
                    </div>
                    @if($gw->is_active)
                        <span class="badge bg-success ms-2 flex-shrink-0">Aktif</span>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="open = !open">
                        <i class="bi bi-gear me-1"></i>Konfigurasi
                    </button>
                    <form method="POST" action="{{ route('admin.settings.payment-gateways.activate', $gw->name) }}">
                        @csrf
                        <button class="btn btn-sm {{ $gw->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                            {{ $gw->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </div>

                {{-- Form --}}
                <div x-show="open" x-cloak class="mt-3 pt-3 border-top">
                    <form method="POST" action="{{ route('admin.settings.payment-gateways.update', $gw->name) }}">
                        @csrf @method('PUT')

                        @if($gw->name === 'bank_transfer')
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Nama Bank</label>
                            <input type="text" name="config[bank_name]" class="form-control form-control-sm"
                                   value="{{ $gw->config['bank_name'] ?? '' }}"
                                   placeholder="Contoh: BCA, Mandiri, BNI">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Nama Pemilik Rekening</label>
                            <input type="text" name="config[account_name]" class="form-control form-control-sm"
                                   value="{{ $gw->config['account_name'] ?? '' }}"
                                   placeholder="Sesuai nama di buku tabungan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nomor Rekening</label>
                            <input type="text" name="config[account_number]" class="form-control form-control-sm"
                                   value="{{ $gw->config['account_number'] ?? '' }}"
                                   placeholder="Contoh: 1234567890">
                        </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Instruksi Pembayaran</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="3"
                                      placeholder="{{ $gw->name === 'bank_transfer' ? 'Contoh: Transfer dalam 1x24 jam. Konfirmasi via WhatsApp ke 08xx.' : 'Contoh: Tunjukkan kode booking kepada petugas di lokasi.' }}">{{ $gw->notes }}</textarea>
                            <div class="form-text">Ditampilkan ke pelanggan setelah checkout.</div>
                        </div>

                        <button class="btn btn-sm btn-primary">
                            <i class="bi bi-floppy me-1"></i>Simpan
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection
