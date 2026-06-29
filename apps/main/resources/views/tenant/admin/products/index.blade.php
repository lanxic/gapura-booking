@extends('layouts.tenant-admin')

@section('title', 'Produk')

@section('content')

<div x-data="{
    selected: [],
    allChecked: false,
    toggleAll(rows) {
        this.allChecked = !this.allChecked;
        this.selected  = this.allChecked ? rows : [];
    },
    toggle(id) {
        this.selected.includes(id)
            ? this.selected = this.selected.filter(i => i !== id)
            : this.selected.push(id);
    }
}">

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width:220px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari produk..."
                       value="{{ request('search') }}">
            </div>
            <select name="status" class="form-select form-select-sm" style="width:140px">
                <option value="">Semua Status</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Diarsip</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            @if(request('search') || request('status'))
                <a href="{{ route('tenant.admin.products.index') }}" class="btn btn-sm btn-outline-danger">Reset</a>
            @endif
        </form>
        <a href="{{ route('tenant.admin.products.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Produk
        </a>
    </div>

    {{-- Bulk action bar --}}
    <div class="d-flex align-items-center gap-2 mb-2" x-show="selected.length > 0" x-cloak>
        <span class="small text-muted" x-text="selected.length + ' produk dipilih'"></span>
        <form method="POST" action="{{ route('tenant.admin.products.bulk-destroy') }}"
              @submit.prevent="
                if (!confirm('Hapus ' + selected.length + ' produk yang dipilih?')) return;
                $el.querySelector('[name=ids]').value = selected.join(',');
                $el.submit();
              ">
            @csrf
            @method('DELETE')
            <input type="hidden" name="ids" value="">
            <button type="submit" class="btn btn-sm btn-danger">
                <i class="bi bi-trash me-1"></i>Hapus Dipilih
            </button>
        </form>
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="selected = []; allChecked = false">
            Batalkan
        </button>
    </div>

    {{-- Table --}}
    @php $ids = $products->pluck('id')->toArray(); @endphp
    <div class="table-card">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:40px" class="ps-3">
                        <input type="checkbox" class="form-check-input"
                               :checked="allChecked"
                               @change="toggleAll({{ json_encode($ids) }})">
                    </th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Harga Dewasa</th>
                    <th>Harga Anak</th>
                    <th>Maks Pax</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr :class="selected.includes({{ $product->id }}) ? 'table-active' : ''">
                    <td class="ps-3">
                        <input type="checkbox" class="form-check-input"
                               :checked="selected.includes({{ $product->id }})"
                               @change="toggle({{ $product->id }})">
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $product->name }}</div>
                        <div class="text-muted small">{{ $product->slug }}</div>
                    </td>
                    <td>
                        <span class="badge bg-{{ $product->category === 'outdoor' ? 'success' : 'info' }}-subtle
                                           text-{{ $product->category === 'outdoor' ? 'success' : 'info' }}
                                           border border-{{ $product->category === 'outdoor' ? 'success' : 'info' }}-subtle">
                            {{ $product->category === 'outdoor' ? 'Outdoor' : 'Indoor' }}
                        </span>
                    </td>
                    <td class="small">Rp {{ number_format($product->price_adult, 0, ',', '.') }}</td>
                    <td class="small text-muted">
                        {{ $product->price_child ? 'Rp ' . number_format($product->price_child, 0, ',', '.') : '—' }}
                    </td>
                    <td class="small">{{ $product->max_pax }} pax</td>
                    <td>
                        @php
                            $statusMap = [
                                'active'   => ['success',   'Aktif'],
                                'inactive' => ['secondary', 'Nonaktif'],
                                'archived' => ['warning',   'Diarsip'],
                            ];
                            [$sc, $sl] = $statusMap[$product->status] ?? ['secondary', $product->status];
                        @endphp
                        <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} border border-{{ $sc }}-subtle">
                            {{ $sl }}
                        </span>
                    </td>
                    <td class="text-end pe-3">
                        <a href="{{ route('tenant.admin.products.slots', $product->id) }}"
                           class="btn btn-sm btn-outline-info" title="Kelola Slot">
                            <i class="bi bi-calendar-week"></i>
                        </a>
                        <a href="{{ route('tenant.admin.products.edit', $product) }}"
                           class="btn btn-sm btn-outline-secondary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('tenant.admin.products.destroy', $product) }}"
                              class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-box-seam fs-2 d-block mb-2 opacity-50"></i>
                        Belum ada produk.
                        <a href="{{ route('tenant.admin.products.create') }}" class="d-block mt-2 btn btn-sm btn-primary">
                            Tambah Produk Pertama
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination + info --}}
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <div class="small text-muted">
            Menampilkan {{ $products->firstItem() }}–{{ $products->lastItem() }}
            dari {{ $products->total() }} produk
        </div>
        {{ $products->withQueryString()->links('pagination::bootstrap-5') }}
    </div>

</div>

@endsection
