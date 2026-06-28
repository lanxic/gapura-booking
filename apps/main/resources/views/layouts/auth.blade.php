<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Amartha eTicket</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

    <div class="w-100" style="max-width: 420px; padding: 1rem;">
        <div class="text-center mb-4">
            @php
                $isTenant = app()->bound('current_tenant');
                $homeUrl  = $isTenant ? route('tenant.home') : route('admin.login');
            @endphp
            <a href="{{ $homeUrl }}" class="text-decoration-none">
                @if($isTenant && isset($tenant) && $tenant->logo_url)
                    <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->name }}" style="height:40px;object-fit:contain;display:block;margin:0 auto .5rem">
                    <h3 class="fw-bold text-primary">{{ $tenant->name }}</h3>
                @elseif($isTenant && isset($tenant))
                    <h3 class="fw-bold text-primary">{{ $tenant->name }}</h3>
                @else
                    <h3 class="fw-bold text-primary">🎫 Amartha eTicket</h3>
                @endif
            </a>
        </div>

        <div class="card p-4 shadow-sm">
            @yield('content')
        </div>
    </div>

</body>
</html>
