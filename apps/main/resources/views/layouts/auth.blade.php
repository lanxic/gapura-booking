<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Amartha eTicket</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

    <div class="w-100" style="max-width: 420px; padding: 1rem;">
        <div class="text-center mb-4">
            <a href="{{ route('home') }}" class="text-decoration-none">
                <h3 class="fw-bold text-primary">🎫 Amartha eTicket</h3>
            </a>
        </div>

        <div class="card p-4 shadow-sm">
            @yield('content')
        </div>
    </div>

</body>
</html>
