<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $tenant->name) — {{ $tenant->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('head')
</head>
<body>

    {{-- Demo ribbon — hanya untuk tenant adventure --}}
    @if($tenant->slug === 'adventure')
    <div style="
        position: fixed;
        top: 20px;
        right: -32px;
        z-index: 9999;
        width: 140px;
        background: #dc3545;
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-align: center;
        text-transform: uppercase;
        padding: 5px 0;
        transform: rotate(45deg);
        transform-origin: center center;
        box-shadow: 0 2px 6px rgba(0,0,0,.25);
        pointer-events: none;
    ">DEMO</div>
    @endif

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand text-primary fw-bold d-flex align-items-center gap-2" href="{{ route('tenant.home') }}">
                @if($tenant->logo_url)
                    <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->name }}" style="height:34px;object-fit:contain">
                @else
                    <i class="bi bi-ticket-perforated-fill"></i>
                @endif
                {{ $tenant->name }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTenant">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTenant">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tenant.products.*') ? 'active fw-semibold' : '' }}"
                           href="{{ route('tenant.products.index') }}">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tenant.offers.*') ? 'active fw-semibold' : '' }}"
                           href="{{ route('tenant.offers.index') }}">Penawaran</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    @php $cartCount = count(session('cart', [])); @endphp
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="{{ route('tenant.cart.index') }}">
                            <i class="bi bi-cart3 fs-5"></i>
                            @if($cartCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem">{{ $cartCount }}</span>
                            @endif
                        </a>
                    </li>
                    @auth('web')
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>{{ Auth::guard('web')->user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('tenant.account.bookings') }}">
                                    <i class="bi bi-ticket-perforated me-2"></i>Booking Saya
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('tenant.logout') }}">
                                        @csrf
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tenant.login') }}">Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm ms-1 px-3" href="{{ route('tenant.register') }}">Daftar</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible m-0 rounded-0" role="alert">
            <div class="container">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible m-0 rounded-0" role="alert">
            <div class="container">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="site-footer py-5">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <h5 class="fw-bold mb-2">
                        @if($tenant->logo_url)
                            <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->name }}" style="height:28px;object-fit:contain;filter:brightness(0) invert(1)" class="me-2">
                        @endif
                        {{ $tenant->name }}
                    </h5>
                    <p class="small opacity-75 mb-0">Pesan tiket aktivitas seru dengan mudah dan cepat.</p>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-semibold mb-3">Navigasi</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-1"><a href="{{ route('tenant.home') }}"><i class="bi bi-house me-1"></i>Beranda</a></li>
                        <li class="mb-1"><a href="{{ route('tenant.products.index') }}"><i class="bi bi-grid me-1"></i>Produk</a></li>
                        <li class="mb-1"><a href="{{ route('tenant.offers.index') }}"><i class="bi bi-tag me-1"></i>Penawaran</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-semibold mb-3">Akun</h6>
                    <ul class="list-unstyled mb-0 small">
                        @auth('web')
                            <li class="mb-1"><a href="{{ route('tenant.account.bookings') }}"><i class="bi bi-ticket-perforated me-1"></i>Booking Saya</a></li>
                        @else
                            <li class="mb-1"><a href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</a></li>
                            <li class="mb-1"><a href="{{ route('register') }}"><i class="bi bi-person-plus me-1"></i>Daftar</a></li>
                        @endauth
                        <li class="mb-1"><a href="{{ route('tenant.legal.show', 'terms-of-service') }}"><i class="bi bi-file-text me-1"></i>Syarat & Ketentuan</a></li>
                    </ul>
                </div>
            </div>
            <hr class="footer-divider">
            <p class="footer-copy text-center mb-0">
                Hak Cipta &copy; {{ date('Y') }} {{ $tenant->name }}. All rights reserved.
            </p>
        </div>
    </footer>

    @include('components.confirm-modal')
    @stack('scripts')
</body>
</html>
