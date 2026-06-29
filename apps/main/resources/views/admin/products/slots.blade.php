@extends('layouts.admin')

@section('title', 'Slot — ' . $product->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produk</a></li>
    <li class="breadcrumb-item active">Slot</li>
@endsection

@section('content')

<div class="row g-4">

    {{-- Generate Slots --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill text-warning"></i> Generate Slot</h6>

                <form method="POST" action="{{ route('admin.products.generate-slots', $product->id) }}"
                    x-data="{
                        slotMode: 'all_day',
                        startDate: '',
                        endDate: '',
                        selectedDays: [0,1,2,3,4,5,6],
                        activeRange: '7h',

                        init() {
                            this.setRange('7h');
                        },

                        setRange(type) {
                            const today = new Date();
                            let start = new Date(today);
                            let end = new Date(today);

                            if (type === '7h') {
                                end.setDate(today.getDate() + 6);
                            } else if (type === '30h') {
                                end.setDate(today.getDate() + 29);
                            } else if (type === 'bln') {
                                start = new Date(today.getFullYear(), today.getMonth(), 1);
                                end   = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                            }

                            this.startDate   = this.fmtDate(start);
                            this.endDate     = this.fmtDate(end);
                            this.activeRange = type;
                        },

                        fmtDate(d) {
                            return d.getFullYear() + '-'
                                + String(d.getMonth() + 1).padStart(2, '0') + '-'
                                + String(d.getDate()).padStart(2, '0');
                        },

                        toggleDay(d) {
                            if (this.selectedDays.includes(d)) {
                                this.selectedDays = this.selectedDays.filter(x => x !== d);
                            } else {
                                this.selectedDays = [...this.selectedDays, d].sort((a, b) => a - b);
                            }
                        },

                        setDayPreset(preset) {
                            if (preset === 'all')     this.selectedDays = [0,1,2,3,4,5,6];
                            else if (preset === 'weekday') this.selectedDays = [1,2,3,4,5];
                            else if (preset === 'weekend') this.selectedDays = [0,6];
                        }
                    }"
                    x-init="init()">
                    @csrf

                    {{-- Mode Slot --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mode Slot</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-sm"
                                :class="slotMode === 'all_day' ? 'btn-primary' : 'btn-outline-secondary'"
                                @click="slotMode = 'all_day'">
                                <i class="bi bi-sun"></i> All Day
                            </button>
                            <button type="button" class="btn btn-sm"
                                :class="slotMode === 'custom' ? 'btn-primary' : 'btn-outline-secondary'"
                                @click="slotMode = 'custom'">
                                <i class="bi bi-clock"></i> Custom Jam
                            </button>
                        </div>
                        <p class="text-muted small mt-1 mb-0" x-show="slotMode === 'all_day'">
                            Slot tidak terikat jam, cocok untuk tiket harian.
                        </p>
                    </div>

                    {{-- Rentang Tanggal --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Rentang Tanggal</label>
                            <div class="d-flex gap-1">
                                <button type="button"
                                    class="btn btn-sm py-0 px-2"
                                    :class="activeRange === '7h' ? 'btn-secondary' : 'btn-outline-secondary'"
                                    style="font-size:.7rem;"
                                    @click="setRange('7h')">7H</button>
                                <button type="button"
                                    class="btn btn-sm py-0 px-2"
                                    :class="activeRange === '30h' ? 'btn-secondary' : 'btn-outline-secondary'"
                                    style="font-size:.7rem;"
                                    @click="setRange('30h')">30H</button>
                                <button type="button"
                                    class="btn btn-sm py-0 px-2"
                                    :class="activeRange === 'bln' ? 'btn-secondary' : 'btn-outline-secondary'"
                                    style="font-size:.7rem;"
                                    @click="setRange('bln')">Bln Ini</button>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label x-small text-muted mb-0" style="font-size:.75rem;">Dari</label>
                                <input type="date" name="start_date" class="form-control form-control-sm"
                                    x-model="startDate" @change="activeRange = ''" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label x-small text-muted mb-0" style="font-size:.75rem;">Sampai</label>
                                <input type="date" name="end_date" class="form-control form-control-sm"
                                    x-model="endDate" @change="activeRange = ''" required>
                            </div>
                        </div>
                    </div>

                    {{-- Hari --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Hari</label>
                            <div class="d-flex gap-1">
                                <button type="button"
                                    class="btn btn-outline-secondary btn-sm py-0 px-2"
                                    style="font-size:.7rem;"
                                    @click="setDayPreset('all')">Semua</button>
                                <button type="button"
                                    class="btn btn-outline-secondary btn-sm py-0 px-2"
                                    style="font-size:.7rem;"
                                    @click="setDayPreset('weekday')">Sen–Jum</button>
                                <button type="button"
                                    class="btn btn-outline-secondary btn-sm py-0 px-2"
                                    style="font-size:.7rem;"
                                    @click="setDayPreset('weekend')">Sab-Min</button>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            @php $days = ['Min','Sen','Sel','Rab','Kam','Jum','Sab']; @endphp
                            @foreach($days as $i => $day)
                            <button type="button"
                                class="btn btn-sm"
                                :class="selectedDays.includes({{ $i }}) ? 'btn-primary' : 'btn-outline-secondary'"
                                @click="toggleDay({{ $i }})"
                                style="min-width:2.5rem; font-size:.8rem;">{{ $day }}</button>
                            @endforeach
                        </div>
                        {{-- Submit hidden inputs for selected days --}}
                        <template x-for="d in selectedDays" :key="d">
                            <input type="hidden" name="days_of_week[]" :value="d">
                        </template>
                    </div>

                    {{-- Jam — Custom Jam mode only (removed from DOM when All Day) --}}
                    <template x-if="slotMode === 'custom'">
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
                    </template>

                    {{-- Hidden time defaults for All Day mode --}}
                    <template x-if="slotMode === 'all_day'">
                        <div>
                            <input type="hidden" name="start_time" value="09:00">
                            <input type="hidden" name="end_time" value="17:00">
                        </div>
                    </template>

                    {{-- Kapasitas --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kapasitas</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-people"></i></span>
                            <input type="number" name="capacity" class="form-control"
                                value="{{ $product->max_pax }}" min="1" required>
                            <span class="input-group-text">pax</span>
                        </div>
                    </div>

                    {{-- Harga --}}
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
                                    value="{{ $product->price_child ?? 0 }}" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-lightning-charge-fill"></i> Generate Slot
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Slot List --}}
    <div class="col-lg-8">
        <div class="table-card">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Maks</th>
                        <th>Terisi</th>
                        <th>Harga</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slots as $slot)
                    <tr>
                        <td>{{ $slot->date->format('d M Y') }}</td>
                        <td>{{ $slot->start_time->format('H:i') }} — {{ $slot->end_time->format('H:i') }}</td>
                        <td>{{ $slot->capacity }}</td>
                        <td>{{ $slot->booked_count }}</td>
                        <td class="small">
                            <div>Rp {{ number_format($slot->price_adult, 0, ',', '.') }}</div>
                            @if($slot->price_child)
                            <div class="text-muted">Anak: Rp {{ number_format($slot->price_child, 0, ',', '.') }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $slot->status === 'available' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($slot->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada slot.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $slots->links('pagination::bootstrap-5') }}</div>
    </div>

</div>

@endsection
