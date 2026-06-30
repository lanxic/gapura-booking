@extends('layouts.tenant-admin')

@section('title', 'Slot — ' . $product->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tenant.admin.products.index') }}">Produk</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tenant.admin.products.edit', $product->id) }}">{{ Str::limit($product->name, 24) }}</a></li>
    <li class="breadcrumb-item active">Slot</li>
@endsection

@section('content')

@php
    $totalSlots     = $slots->total();
    $availableSlots = $slots->getCollection()->where('status', 'available')->count();
    $totalBooked    = $slots->getCollection()->sum('booked_count');
    $totalCapacity  = $slots->getCollection()->sum('capacity');
@endphp

<div class="row g-4">

    {{-- ── Left: Generator Panel ──────────────────────────────── --}}
    <div class="col-xl-4 col-lg-5">

        {{-- Product summary --}}
        <div class="card mb-4" style="border-left: 4px solid var(--bs-primary);">
            <div class="card-body py-3 px-4">
                <div class="small text-muted mb-1">Produk</div>
                <div class="fw-bold">{{ $product->name }}</div>
                <div class="small text-muted mt-1">
                    Kapasitas default: <strong>{{ $product->max_pax }} pax</strong>
                </div>
            </div>
        </div>

        {{-- Generate form --}}
        <div class="card" x-data="{
            mode: 'all_day',
            startDate: '',
            endDate: '',
            startTime: '09:00',
            endTime: '17:00',
            activeRange: '7d',
            days: [0,1,2,3,4,5,6],
            init() { this.setDatePreset('7d'); },
            toggleDay(i) {
                this.days.includes(i)
                    ? this.days = this.days.filter(d => d !== i)
                    : this.days.push(i);
            },
            selectPreset(preset) {
                if (preset === 'all')     this.days = [0,1,2,3,4,5,6];
                if (preset === 'weekday') this.days = [1,2,3,4,5];
                if (preset === 'weekend') this.days = [0,6];
            },
            setDatePreset(preset) {
                const today = new Date();
                const fmt = d => d.toISOString().slice(0,10);
                this.startDate = fmt(today);
                if (preset === '7d')  { const e = new Date(today); e.setDate(e.getDate()+6);  this.endDate = fmt(e); }
                if (preset === '30d') { const e = new Date(today); e.setDate(e.getDate()+29); this.endDate = fmt(e); }
                if (preset === 'month') {
                    const e = new Date(today.getFullYear(), today.getMonth()+1, 0);
                    this.endDate = fmt(e);
                }
                this.activeRange = preset;
            }
        }" x-init="init()">
            <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-magic me-2 text-primary"></i>Generate Slot</h6>
            </div>
            <div class="card-body p-4">

                <form id="generateSlotForm" method="POST" action="{{ route('tenant.admin.products.generate-slots', $product->id) }}">
                    @csrf

                    {{-- Mode toggle --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Mode Slot</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm flex-fill"
                                    :class="mode === 'all_day' ? 'btn-primary' : 'btn-outline-secondary'"
                                    @click="mode = 'all_day'">
                                <i class="bi bi-sun me-1"></i>All Day
                            </button>
                            <button type="button" class="btn btn-sm flex-fill"
                                    :class="mode === 'custom' ? 'btn-primary' : 'btn-outline-secondary'"
                                    @click="mode = 'custom'">
                                <i class="bi bi-clock me-1"></i>Custom Jam
                            </button>
                        </div>
                        <div class="form-text" x-show="mode === 'all_day'">Slot tidak terikat jam, cocok untuk tiket harian.</div>
                        <div class="form-text" x-show="mode === 'custom'">Tentukan jam mulai & selesai spesifik.</div>
                    </div>

                    {{-- Date range --}}
                    <div class="mb-1">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold small mb-0">Rentang Tanggal</label>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-xs" :class="activeRange==='7d' ? 'btn-secondary' : 'btn-outline-secondary'" style="font-size:.7rem;padding:.15rem .4rem" @click="setDatePreset('7d')">7H</button>
                                <button type="button" class="btn btn-xs" :class="activeRange==='30d' ? 'btn-secondary' : 'btn-outline-secondary'" style="font-size:.7rem;padding:.15rem .4rem" @click="setDatePreset('30d')">30H</button>
                                <button type="button" class="btn btn-xs" :class="activeRange==='month' ? 'btn-secondary' : 'btn-outline-secondary'" style="font-size:.7rem;padding:.15rem .4rem" @click="setDatePreset('month')">Bln Ini</button>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">Dari</label>
                            <input type="date" name="start_date" class="form-control form-control-sm"
                                   x-model="startDate" @change="activeRange = ''" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">Sampai</label>
                            <input type="date" name="end_date" class="form-control form-control-sm"
                                   x-model="endDate" @change="activeRange = ''" required>
                        </div>
                    </div>

                    {{-- Day picker --}}
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold small mb-0">Hari</label>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-outline-secondary" style="font-size:.7rem;padding:.15rem .5rem" @click="selectPreset('all')">Semua</button>
                                <button type="button" class="btn btn-outline-secondary" style="font-size:.7rem;padding:.15rem .5rem" @click="selectPreset('weekday')">Sen–Jum</button>
                                <button type="button" class="btn btn-outline-secondary" style="font-size:.7rem;padding:.15rem .5rem" @click="selectPreset('weekend')">Sab–Min</button>
                            </div>
                        </div>
                        <div class="d-flex gap-1 flex-wrap">
                            @php $dayLabels = ['Min','Sen','Sel','Rab','Kam','Jum','Sab']; @endphp
                            @foreach($dayLabels as $i => $day)
                            <button type="button"
                                    class="btn btn-sm"
                                    :class="days.includes({{ $i }}) ? 'btn-primary' : 'btn-outline-secondary'"
                                    style="min-width:44px"
                                    @click="toggleDay({{ $i }})">{{ $day }}</button>
                            @endforeach
                        </div>
                        {{-- Submit selected days as hidden inputs --}}
                        <template x-for="d in days" :key="d">
                            <input type="hidden" name="days_of_week[]" :value="d">
                        </template>
                    </div>

                    {{-- Hidden time inputs (always submitted with correct values) --}}
                    <input type="hidden" name="start_time" :value="mode === 'all_day' ? '09:00' : startTime">
                    <input type="hidden" name="end_time"   :value="mode === 'all_day' ? '17:00' : endTime">

                    {{-- Custom time inputs (visible only in custom mode) --}}
                    <div x-show="mode === 'custom'" class="mb-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Jam Mulai</label>
                                <input type="time" class="form-control form-control-sm"
                                       x-model="startTime">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Jam Selesai</label>
                                <input type="time" class="form-control form-control-sm"
                                       x-model="endTime">
                            </div>
                        </div>
                    </div>

                    {{-- Capacity & Price --}}
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-semibold">Kapasitas</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-people"></i></span>
                                <input type="number" name="capacity" class="form-control"
                                       value="{{ $product->max_pax }}" min="1" required>
                                <span class="input-group-text">pax</span>
                            </div>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-semibold">
                                Spare
                                <span class="text-muted fw-normal" style="font-size:.7rem"
                                      title="Buffer pax untuk transaksi bersamaan di sisa slot terakhir">
                                    <i class="bi bi-info-circle"></i>
                                </span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="spare_capacity" class="form-control"
                                       value="0" min="0" max="10">
                                <span class="input-group-text">pax</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Harga Dewasa</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="price_adult" class="form-control"
                                       value="{{ $product->price_adult }}" min="0" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Harga Anak</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="price_child" class="form-control"
                                       value="{{ $product->price_child }}" min="0"
                                       placeholder="0">
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary w-100"
                            @click="$dispatch('open-generate-modal', { start: startDate, end: endDate, days: days })">
                        <i class="bi bi-lightning-charge-fill me-1"></i>Generate Slot
                    </button>
                </form>

            </div>
        </div>
        {{-- ── Tambah Slot Satuan ─────────────────────────────── --}}
        <div class="card mt-4" x-data="{ open: {{ $errors->isNotEmpty() ? 'true' : 'false' }} }">
            <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between"
                 style="cursor:pointer" @click="open = !open">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-plus-circle me-2 text-success"></i>Tambah Slot Satuan
                </h6>
                <i class="bi text-muted" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </div>
            <div class="card-body p-4" x-show="open" x-cloak>
                <p class="text-muted small mb-3">Tambah satu slot pada tanggal dan jam tertentu.</p>
                @if($errors->isNotEmpty())
                <div class="alert alert-danger small py-2 mb-3">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif
                <form method="POST" action="{{ route('tenant.admin.products.store-slot', $product->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal</label>
                        <input type="date" name="date" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jam Mulai</label>
                            <input type="time" name="start_time" class="form-control form-control-sm"
                                   value="09:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jam Selesai</label>
                            <input type="time" name="end_time" class="form-control form-control-sm"
                                   value="17:00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kapasitas</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-people"></i></span>
                            <input type="number" name="capacity" class="form-control"
                                   value="{{ $product->max_pax }}" min="1" required>
                            <span class="input-group-text">pax</span>
                        </div>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Harga Dewasa</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="price_adult" class="form-control"
                                       value="{{ $product->price_adult }}" min="0" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Harga Anak</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="price_child" class="form-control"
                                       value="{{ $product->price_child }}" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Slot
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Right: Slot Table ──────────────────────────────────── --}}
    <div class="col-xl-8 col-lg-7">

        {{-- Stats row --}}
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="card text-center py-3">
                    <div class="fw-bold fs-4 text-primary">{{ number_format($totalSlots) }}</div>
                    <div class="small text-muted">Total Slot</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center py-3">
                    <div class="fw-bold fs-4 text-success">{{ $slots->getCollection()->where('status', 'available')->count() }}</div>
                    <div class="small text-muted">Tersedia (halaman ini)</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center py-3">
                    <div class="fw-bold fs-4 text-info">{{ number_format($totalBooked) }}</div>
                    <div class="small text-muted">Total Terisi</div>
                </div>
            </div>
        </div>

        {{-- Slot table --}}
        <div class="table-card"
             x-data="{
                 selected: [],
                 get allIds() { return {{ $slots->pluck('id')->toJson() }}; },
                 get allSelected() { return this.allIds.length > 0 && this.allIds.every(id => this.selected.includes(id)); },
                 toggleAll() { this.selected = this.allSelected ? [] : [...this.allIds]; },
                 toggle(id) {
                     this.selected.includes(id)
                         ? this.selected = this.selected.filter(i => i !== id)
                         : this.selected.push(id);
                 },
             }">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex gap-1">
                    @php
                        $filterTabs = [
                            'available'      => ['label' => 'Tersedia',       'color' => 'success'],
                            'tidak_tersedia' => ['label' => 'Tidak Tersedia', 'color' => 'danger'],
                            'all'            => ['label' => 'Semua',          'color' => 'primary'],
                        ];
                    @endphp
                    @foreach($filterTabs as $val => $tab)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $val, 'page' => 1]) }}"
                       class="btn btn-xs {{ $filterStatus === $val ? 'btn-'.$tab['color'] : 'btn-outline-'.$tab['color'] }}"
                       style="font-size:.72rem;padding:.2rem .55rem">
                        {{ $tab['label'] }}
                    </a>
                    @endforeach
                </div>
                <span class="badge bg-light text-dark border">{{ $slots->total() }} slot</span>
            </div>

            {{-- Bulk action bar --}}
            <div class="px-3 py-2 border-bottom bg-primary-subtle d-flex align-items-center gap-3"
                 x-show="selected.length > 0" x-cloak>
                <span class="small fw-semibold text-primary" x-text="selected.length + ' slot dipilih'"></span>
                <form method="POST" action="{{ route('tenant.admin.slots.bulk-update') }}"
                      class="d-flex align-items-center gap-2"
                      @submit.prevent="if(selected.length===0){alert('Pilih slot terlebih dahulu');return;}
                                        $el.querySelector('input[name=slot_ids_json]').value=JSON.stringify(selected);
                                        $el.submit()">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="slot_ids_json" value="">
                    <select name="status" class="form-select form-select-sm" style="width:160px">
                        <option value="available">Tersedia</option>
                        <option value="blocked">Tidak Tersedia</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="selected = []">Batalkan Pilihan</button>
                </form>
            </div>

            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:36px">
                            <input type="checkbox" class="form-check-input"
                                   :checked="allSelected" @change="toggleAll()">
                        </th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Terisi</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                @forelse($slots as $slot)
                @php
                    $isAllDay = ($slot->start_time->format('H:i') === '09:00' && $slot->end_time->format('H:i') === '17:00')
                             || ($slot->start_time->format('H:i') === '00:00' && $slot->end_time->format('H:i') === '23:59');
                    $fillPct  = $slot->total_capacity > 0
                        ? round($slot->booked_count / $slot->total_capacity * 100)
                        : 0;
                    $fillColor = $fillPct >= 100 ? 'danger' : ($fillPct >= 70 ? 'warning' : 'success');
                @endphp
                <tbody x-data="{ editing: false }">
                    <tr>
                        <td class="ps-3" style="width:36px">
                            <input type="checkbox" class="form-check-input"
                                   :checked="selected.includes({{ $slot->id }})"
                                   @change="toggle({{ $slot->id }})">
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $slot->date->format('d M Y') }}</div>
                            <div class="small text-muted">{{ $slot->date->translatedFormat('l') }}</div>
                        </td>
                        <td>
                            <span class="text-nowrap fw-semibold" style="font-size:.85rem">
                                {{ $slot->start_time->format('H:i') }}
                                <i class="bi bi-arrow-right text-muted mx-1" style="font-size:.7rem"></i>
                                {{ $slot->end_time->format('H:i') }}
                            </span>
                            @if($isAllDay)
                            <div class="mt-1">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:.68rem">
                                    <i class="bi bi-sun-fill me-1"></i>All Day
                                </span>
                            </div>
                            @endif
                        </td>
                        <td style="min-width:110px">
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-grow-1" style="min-width:60px">
                                    <div class="progress" style="height:5px; border-radius:3px">
                                        <div class="progress-bar bg-{{ $fillColor }}"
                                             style="width:{{ $fillPct }}%"></div>
                                    </div>
                                </div>
                                <span class="small text-muted text-nowrap">
                                    {{ $slot->booked_count }}/{{ $slot->total_capacity }}
                                    @if($slot->spare_capacity > 0)
                                    <span class="text-warning" title="Termasuk {{ $slot->spare_capacity }} spare">+{{ $slot->spare_capacity }}</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td class="small">
                            <div>Rp {{ number_format($slot->price_adult, 0, ',', '.') }}</div>
                            @if($slot->price_child)
                            <div class="text-muted" style="font-size:.75rem">Anak: Rp {{ number_format($slot->price_child, 0, ',', '.') }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $isAvailable = $slot->status === 'available';
                                [$sc, $sl] = $isAvailable
                                    ? ['success', 'Tersedia']
                                    : ['danger',  'Tidak Tersedia'];
                            @endphp
                            <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} border border-{{ $sc }}-subtle">
                                {{ $sl }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <button class="btn btn-xs btn-outline-secondary" style="padding:.2rem .5rem;font-size:.75rem"
                                    @click="editing = !editing" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                    {{-- Inline edit row --}}
                    <tr x-show="editing" x-cloak>
                        <td colspan="7" class="px-3 py-3 bg-light">
                            <form method="POST" action="{{ route('tenant.admin.slots.update', $slot->id) }}"
                                  class="d-flex flex-wrap gap-3 align-items-end">
                                @csrf @method('PUT')
                                <div>
                                    <label class="form-label small fw-semibold mb-1">Jam Mulai</label>
                                    <input type="time" name="start_time" class="form-control form-control-sm"
                                           value="{{ $slot->start_time->format('H:i') }}" style="width:110px">
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold mb-1">Jam Selesai</label>
                                    <input type="time" name="end_time" class="form-control form-control-sm"
                                           value="{{ $slot->end_time->format('H:i') }}" style="width:110px">
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold mb-1">Kapasitas</label>
                                    <input type="number" name="capacity" class="form-control form-control-sm"
                                           value="{{ $slot->capacity }}" min="0" style="width:80px">
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold mb-1">Spare <i class="bi bi-info-circle text-muted" title="Buffer transaksi bersamaan"></i></label>
                                    <input type="number" name="spare_capacity" class="form-control form-control-sm"
                                           value="{{ $slot->spare_capacity }}" min="0" max="10" style="width:70px">
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold mb-1">Harga Dewasa</label>
                                    <div class="input-group input-group-sm" style="width:150px">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="price_adult" class="form-control"
                                               value="{{ $slot->price_adult }}" min="0">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold mb-1">Harga Anak</label>
                                    <div class="input-group input-group-sm" style="width:150px">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="price_child" class="form-control"
                                               value="{{ $slot->price_child }}" min="0" placeholder="0">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold mb-1">Status</label>
                                    <select name="status" class="form-select form-select-sm" style="width:160px">
                                        <option value="available" {{ $slot->status === 'available' ? 'selected' : '' }}>Tersedia</option>
                                        <option value="blocked"   {{ $slot->status !== 'available' ? 'selected' : '' }}>Tidak Tersedia</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="editing = false">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-50"></i>
                            Belum ada slot. Generate slot menggunakan form di sebelah kiri.
                        </td>
                    </tr>
                </tbody>
                @endforelse
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $slots->links('pagination::bootstrap-5') }}
        </div>

    </div>
</div>

{{-- ── Confirm Generate Modal ──────────────────────────────── --}}
<div x-data="{
        open: false,
        startDate: '',
        endDate: '',
        days: [],
        dayNames: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
        get dayLabels() {
            return [...this.days].sort((a,b)=>a-b).map(d => this.dayNames[d]).join(', ');
        },
        get dateRange() {
            if (!this.startDate || !this.endDate) return '—';
            const fmt = s => {
                const [y,m,d] = s.split('-');
                return d + '/' + m + '/' + y;
            };
            return fmt(this.startDate) + ' s/d ' + fmt(this.endDate);
        }
     }"
     @open-generate-modal.window="open = true; startDate = $event.detail.start; endDate = $event.detail.end; days = $event.detail.days"
     x-cloak>

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="position-fixed top-0 start-0 w-100 h-100"
         style="background:rgba(0,0,0,.45);z-index:1050"
         @click="open = false">
    </div>

    {{-- Dialog --}}
    <div x-show="open"
         x-transition:enter.duration.200ms
         x-transition:leave.duration.150ms
         class="position-fixed top-50 start-50 translate-middle"
         style="z-index:1055;width:100%;max-width:420px;padding:0 1rem">

        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">

            {{-- Header --}}
            <div class="card-header border-0 px-4 pt-4 pb-3 d-flex align-items-center justify-content-between"
                 style="background:linear-gradient(135deg,#eef2ff 0%,#f8f9ff 100%)">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background:#4f46e5;flex-shrink:0">
                        <i class="bi bi-lightning-charge-fill text-white fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold" style="color:#1e1b4b">Generate Slot</h6>
                        <p class="mb-0 text-muted small">Konfirmasi sebelum membuat slot</p>
                    </div>
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary border-0 rounded-circle d-flex align-items-center justify-content-center p-0"
                        style="width:32px;height:32px"
                        @click="open = false">
                    <i class="bi bi-x-lg" style="font-size:.8rem"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="card-body px-4 py-3">
                <div class="rounded-3 p-3 mb-3 d-flex gap-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                    <div class="flex-fill">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em">Rentang Tanggal</div>
                        <div class="fw-semibold text-dark small" x-text="dateRange"></div>
                    </div>
                    <div style="width:1px;background:#e2e8f0;flex-shrink:0"></div>
                    <div class="flex-fill">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em">Hari</div>
                        <div class="fw-semibold text-dark small" x-text="dayLabels || '—'"></div>
                    </div>
                </div>
                <p class="mb-0 small text-muted">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    Slot yang sudah ada pada tanggal yang sama tidak akan digandakan.
                </p>
            </div>

            {{-- Footer --}}
            <div class="card-footer border-0 px-4 pb-4 pt-2 d-flex gap-2 justify-content-end"
                 style="background:transparent">
                <button type="button" class="btn btn-outline-secondary px-4" @click="open = false">
                    Batal
                </button>
                <button type="button"
                        class="btn btn-primary px-4 d-flex align-items-center gap-2"
                        @click="open = false; document.getElementById('generateSlotForm').submit()">
                    <i class="bi bi-lightning-charge-fill"></i>
                    Ya, Generate
                </button>
            </div>

        </div>
    </div>
</div>

@endsection
