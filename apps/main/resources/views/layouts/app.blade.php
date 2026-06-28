<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('head')
</head>
<body>

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            @php $navLogoUrl = \App\Models\SystemSetting::get('general', 'logo_url'); @endphp
            <a class="navbar-brand text-primary fw-bold" href="{{ route('home') }}">
                @if($navLogoUrl)
                    <img src="{{ $navLogoUrl }}" alt="{{ config('app.name') }}" style="height:36px;object-fit:contain">
                @else
                    🎫 {{ config('app.name') }}
                @endif
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('activities.*') ? 'active fw-semibold' : '' }}"
                           href="{{ route('activities.index') }}">Aktivitas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('offers.*') ? 'active fw-semibold' : '' }}"
                           href="{{ route('offers.index') }}">Penawaran</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    {{-- Currency indicator --}}
                    <li class="nav-item">
                        <span class="nav-link text-muted small">
                            <i class="bi bi-cash-coin me-1"></i>IDR
                        </span>
                    </li>
                    @auth('web')
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>{{ Auth::guard('web')->user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('account.bookings') }}">
                                    <i class="bi bi-ticket-perforated me-2"></i>Booking Saya
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
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
                            <a class="nav-link" href="{{ route('login') }}">Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm ms-1 px-3" href="{{ route('register') }}">Daftar</a>
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

    {{-- Main Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    @php
        $generalSettings    = \App\Models\SystemSetting::getGroup('general');
        $socialSettings     = \App\Models\SystemSetting::getGroup('social');
        $siteName    = $generalSettings['site_name']    ?? $generalSettings['property_name'] ?? config('app.name');
        $siteEmail   = $generalSettings['site_email']   ?? null;
        $sitePhone   = $generalSettings['site_phone']   ?? null;
        $siteAddress = $generalSettings['site_address'] ?? null;
    @endphp
    <footer class="site-footer py-5">
        <div class="container">

            {{-- Social media row (dari DB) --}}
            @php
                $hasSocial = collect(['facebook','instagram','twitter','youtube','whatsapp','tiktok'])
                    ->contains(fn($k) => !empty($socialSettings[$k]));
            @endphp
            @if($hasSocial)
            <div class="footer-social mb-4">
                @if(!empty($socialSettings['facebook']))
                    <a href="{{ $socialSettings['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                @endif
                @if(!empty($socialSettings['instagram']))
                    <a href="{{ $socialSettings['instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                @endif
                @if(!empty($socialSettings['twitter']))
                    <a href="{{ $socialSettings['twitter'] }}" target="_blank" rel="noopener" aria-label="Twitter / X"><i class="bi bi-twitter-x"></i></a>
                @endif
                @if(!empty($socialSettings['youtube']))
                    <a href="{{ $socialSettings['youtube'] }}" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                @endif
                @if(!empty($socialSettings['whatsapp']))
                    <a href="{{ $socialSettings['whatsapp'] }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                @endif
                @if(!empty($socialSettings['tiktok']))
                    <a href="{{ $socialSettings['tiktok'] }}" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                @endif
            </div>
            @endif

            <hr class="footer-divider">

            {{-- Contact columns --}}
            <div class="row text-center g-4 mt-1 mb-3">
                @if($siteEmail)
                <div class="col-md-4">
                    <i class="bi bi-envelope footer-contact-icon"></i>
                    <p class="footer-contact-label mb-0">Hubungi Kami</p>
                    <p class="footer-contact-value mb-0">{{ $siteEmail }}</p>
                </div>
                @endif
                @if($sitePhone)
                <div class="col-md-4">
                    <i class="bi bi-telephone footer-contact-icon"></i>
                    <p class="footer-contact-label mb-0">Nomor Telepon</p>
                    <p class="footer-contact-value mb-0">{{ $sitePhone }}</p>
                </div>
                @endif
                @if($siteAddress)
                <div class="col-md-4">
                    <i class="bi bi-geo-alt footer-contact-icon"></i>
                    <p class="footer-contact-label mb-0">Alamat</p>
                    <p class="footer-contact-value mb-0">{{ $siteAddress }}</p>
                </div>
                @endif
                <div class="col-md-4">
                    <i class="bi bi-book footer-contact-icon"></i>
                    <p class="footer-contact-label mb-0">Legal</p>
                    <p class="footer-contact-value mb-0">
                        <a href="{{ route('legal.show', 'privacy-policy') }}" class="d-block">Kebijakan Privasi</a>
                        <a href="{{ route('legal.show', 'terms-of-service') }}">Syarat & Ketentuan</a>
                    </p>
                </div>
            </div>

            <hr class="footer-divider">

            <p class="footer-copy text-center mb-0">
                Hak Cipta &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
