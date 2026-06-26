<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — Amartha eTicket</title>
    @vite(['resources/scss/admin.scss', 'resources/js/admin.js'])
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div style="max-width: 400px; width: 100%; padding: 1rem;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">🎫 Admin Panel</h3>
            <p class="text-muted small">Amartha eTicket</p>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Masuk sebagai Admin</h5>

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Masuk</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
