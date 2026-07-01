@extends('layouts.admin')

@section('title', 'Manajemen User')

@section('content')

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah User
    </a>
</div>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Tenant</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td class="fw-semibold">{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    @php
                        $roleColor = match($user->role->value) {
                            'super_admin'  => 'bg-danger',
                            'admin'        => 'bg-warning text-dark',
                            'tenant_admin' => 'bg-primary',
                            'scanner'      => 'bg-info text-dark',
                            default        => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $roleColor }}">{{ $user->role->value }}</span>
                </td>
                <td class="text-muted small">
                    {{ $user->tenant?->name ?? '—' }}
                </td>
                <td>
                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmModal('Hapus user ini?', () => this.closest('form').submit())">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada user.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>

@endsection
