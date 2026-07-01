<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Admin Amartha eTicket</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/scss/admin.scss', 'resources/js/admin.js'])
    @stack('head')
</head>
<body>
<div class="d-flex" x-data="{ sidebarOpen: true }" :class="{ 'sidebar-collapsed': !sidebarOpen }">

    {{-- Sidebar --}}
    <aside class="sidebar d-flex flex-column">
        <div class="sidebar-brand d-flex align-items-center gap-2">
            <span>🎫</span>
            <span>Amartha Admin</span>
        </div>

        <nav class="flex-grow-1 py-2">
            <div class="nav-section-label"><span>Utama</span></div>
            <a href="{{ route('admin.dashboard') }}" title="Dashboard"
               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>

            <div class="nav-section-label"><span>Multi-Tenant</span></div>
            <a href="{{ route('admin.tenants.index') }}" title="Tenant"
               class="nav-link {{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}">
                <i class="bi bi-buildings"></i><span>Tenant</span>
            </a>

            <div class="nav-section-label"><span>Konten</span></div>
            <a href="{{ route('admin.products.index') }}" title="Produk"
               class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i><span>Produk</span>
            </a>
            <a href="{{ route('admin.offers.index') }}" title="Penawaran"
               class="nav-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}">
                <i class="bi bi-tag"></i><span>Penawaran</span>
            </a>

            <div class="nav-section-label"><span>Transaksi</span></div>
            <a href="{{ route('admin.bookings.index') }}" title="Booking"
               class="nav-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                <i class="bi bi-ticket-perforated"></i><span>Booking</span>
            </a>
            <a href="{{ route('admin.invoices.index') }}" title="Invoice"
               class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i><span>Invoice</span>
            </a>

            <div class="nav-section-label"><span>Pengguna</span></div>
            <a href="{{ route('admin.customers.index') }}" title="Pelanggan"
               class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i><span>Pelanggan</span>
            </a>
            <a href="{{ route('admin.users.index') }}" title="Manajemen User"
               class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i><span>Manajemen User</span>
            </a>

            <div class="nav-section-label"><span>Sistem</span></div>
            <a href="{{ route('admin.settings.general') }}" title="Pengaturan"
               class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i><span>Pengaturan</span>
            </a>
        </nav>

        @php $adminUser = Auth::guard('admin_session')->user(); @endphp
        <div class="sidebar-footer" x-data="{ profileOpen: false }" @click.outside="profileOpen = false">
            <button type="button"
                    class="sidebar-profile-btn"
                    @click="profileOpen = !profileOpen"
                    :class="{ 'active': profileOpen }">
                <div class="avatar-sm flex-shrink-0">{{ strtoupper(substr($adminUser?->name ?? 'A', 0, 2)) }}</div>
                <div class="profile-info">
                    <span class="profile-name">{{ $adminUser?->name }}</span>
                    <span class="profile-role">{{ $adminUser?->role?->label() ?? $adminUser?->role }}</span>
                </div>
                <i class="bi bi-chevron-up profile-chevron" :class="{ 'rotated': !profileOpen }"></i>
            </button>

            <div class="profile-dropdown" x-show="profileOpen" x-cloak>
                <a href="{{ route('admin.profile.show') }}" class="profile-dropdown-item">
                    <i class="bi bi-person-circle"></i> Profil Saya
                </a>
                <div class="profile-dropdown-divider"></div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="profile-dropdown-item text-danger w-100">
                        <i class="bi bi-box-arrow-left"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="main-content d-flex flex-column">

        {{-- Topbar --}}
        <div class="topbar d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light" @click="sidebarOpen = !sidebarOpen">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="fw-semibold text-secondary small">@yield('title', 'Dashboard')</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="badge bg-primary">{{ Auth::guard('admin_session')->user()?->role }}</span>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible m-3 mb-0 rounded-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible m-3 mb-0 rounded-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Page Content --}}
        <div class="page-header">
            <h4 class="mb-0 fw-bold">@yield('title', 'Dashboard')</h4>
            @hasSection('breadcrumb')
                <nav aria-label="breadcrumb" class="mt-1">
                    <ol class="breadcrumb mb-0 small">
                        @yield('breadcrumb')
                    </ol>
                </nav>
            @endif
        </div>

        <div class="content-area flex-grow-1">
            @yield('content')
        </div>
    </div>
</div>

@include('components.confirm-modal')
@stack('scripts')
</body>
</html>
