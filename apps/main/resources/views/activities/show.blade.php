@extends('layouts.app')

@section('title', $activity->name)

@push('head')
<style>
.detail-panel-title{font-weight:700;font-size:.92rem;color:#212529;padding-bottom:.5rem;margin-bottom:.8rem;border-bottom:1.5px solid #dee2e6}
.detail-section{margin-bottom:.85rem}
.detail-label{font-size:.8rem;font-weight:600;color:#212529;margin-bottom:.3rem}
.detail-body{font-size:.82rem;line-height:1.6;color:#495057}
.detail-list{margin:0;padding-left:1.2rem;font-size:.82rem;line-height:1.6;color:#495057}
.detail-list li+li{margin-top:.25rem}
</style>
@endpush

@section('content')

@php
    $today    = now()->toDateString();
    $tomorrow = now()->addDay()->toDateString();
    $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    $slotsJson = $activity->slots->map(fn($s) => [
        'id'        => $s->id,
        'date'      => $s->date->format('Y-m-d'),
        'time'      => $s->start_time->format('H:i') . ' – ' . $s->end_time->format('H:i'),
        'available' => $s->available_pax,
        'price'     => $s->price,
    ])->values()->toJson();

    $addonsJson = $activity->addons->map(fn($a) => [
        'id'      => $a->id,
        'name'    => $a->name,
        'price'   => $a->price,
        'unit'    => $a->unit,
        'max_qty' => $a->max_qty,
    ])->toJson();
@endphp

<div class="container py-3" style="max-width: 860px;">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}" class="text-primary text-decoration-none">Beranda</a>
            </li>
            <li class="breadcrumb-item active text-muted">{{ $activity->name }}</li>
        </ol>
    </nav>

</div>

{{-- ─── Image Gallery ────────────────────────────────────────────────────────── --}}
<div class="container" style="max-width: 860px;">

    @if($activity->media->isNotEmpty())
    <div x-data="{
            items: {{ $activity->media->values()->toJson() }},
            cur: 0,
            go(i)  { this.cur = i },
            next() { this.cur = (this.cur + 1) % this.items.length },
            prev() { this.cur = (this.cur - 1 + this.items.length) % this.items.length },
         }" class="mb-4">

        {{-- Main slide --}}
        <div class="gallery-main position-relative rounded-3 overflow-hidden mb-2">
            <img :src="items[cur].url" alt="{{ $activity->name }}"
                 class="w-100 d-block" style="height:420px;object-fit:cover">
            @if($activity->media->count() > 1)
                <button class="gallery-btn gallery-btn-prev" @click="prev()" type="button" aria-label="Prev">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="gallery-btn gallery-btn-next" @click="next()" type="button" aria-label="Next">
                    <i class="bi bi-chevron-right"></i>
                </button>
            @endif
        </div>

        {{-- Thumbnails --}}
        @if($activity->media->count() > 1)
        <div class="d-flex gap-2 pb-1" style="overflow-x:auto">
            @foreach($activity->media as $idx => $m)
            <img src="{{ $m->url }}" alt=""
                 class="gallery-thumb rounded-2"
                 :class="{ 'active': cur === {{ $idx }} }"
                 @click="go({{ $idx }})"
                 style="width:90px;height:62px;object-fit:cover;cursor:pointer">
            @endforeach
        </div>
        @endif
    </div>

    @elseif($activity->cloudinary_thumbnail_url)
    <div class="mb-4 rounded-3 overflow-hidden">
        <img src="{{ $activity->cloudinary_thumbnail_url }}" alt="{{ $activity->name }}"
             class="w-100 d-block" style="height:420px;object-fit:cover">
    </div>

    @else
    <div class="mb-4 rounded-3 bg-light d-flex align-items-center justify-content-center" style="height:300px">
        <i class="bi bi-image text-muted" style="font-size:3rem"></i>
    </div>
    @endif

</div>

{{-- ─── Main Content ─────────────────────────────────────────────────────────── --}}
<div class="container py-4" style="max-width: 860px;"
     x-data="{
         /* ── Addon state ─────────────────────────────────────────── */
         addonList: {{ $addonsJson }},
         addonQty:  {},
         setAddon(id, delta) {
             const addon = this.addonList.find(a => a.id === id);
             if (!addon) return;
             const cur  = this.addonQty[id] ?? 0;
             const next = Math.max(0, Math.min(cur + delta, addon.max_qty));
             if (next === 0) { delete this.addonQty[id]; this.addonQty = { ...this.addonQty }; }
             else            { this.addonQty = { ...this.addonQty, [id]: next }; }
         },
         getAddonQty(id) { return this.addonQty[id] ?? 0; },
         get addonsTotal() {
             return this.addonList.reduce((s, a) => s + a.price * (this.addonQty[a.id] ?? 0), 0);
         },
         get addonCount() {
             return Object.values(this.addonQty).filter(q => q > 0).length;
         },
         get addonsJson() {
             return JSON.stringify(
                 Object.entries(this.addonQty)
                     .filter(([,q]) => q > 0)
                     .map(([id, qty]) => ({ addon_id: parseInt(id), quantity: qty }))
             );
         },

         /* ── Slot / booking state ────────────────────────────────── */
         allSlots:     {{ $slotsJson }},
         selectedDate: '{{ $today }}',
         selectedSlot: null,
         paxAdult:     0,
         paxChild:     0,
         minPax:       {{ $activity->min_pax ?? 1 }},
         maxPax:       {{ $activity->max_pax }},
         get pax()         { return this.paxAdult + this.paxChild; },
         get maxTotal()    { return this.selectedSlot ? Math.min(this.selectedSlot.available, this.maxPax) : this.maxPax; },
         get slotsForDate() { return this.allSlots.filter(s => s.date === this.selectedDate); },
         get ticketTotal()  { return this.selectedSlot ? this.selectedSlot.price * this.pax : 0; },
         get grandTotal()   { return this.ticketTotal + this.addonsTotal; },
         setDate(d) {
             this.selectedDate = d; this.selectedSlot = null; this.showCal = false;
             const p = d.split('-'); this.calYear = parseInt(p[0]); this.calMonth = parseInt(p[1]) - 1;
         },
         selectSlot(slot) {
             if (slot.available < 1) return;
             this.selectedSlot = slot;
             this.paxAdult = 0;
             this.paxChild = 0;
         },
         formatRp(n) { return 'Rp ' + n.toLocaleString('id-ID'); },

         /* ── Custom calendar ─────────────────────────────────────── */
         showCal:      false,
         calYear:      new Date().getFullYear(),
         calMonth:     new Date().getMonth(),
         calMonthNames: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
         calDayNames:   ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
         get calDays() {
             const first  = new Date(this.calYear, this.calMonth, 1);
             const last   = new Date(this.calYear, this.calMonth + 1, 0);
             const offset = (first.getDay() + 6) % 7;
             const days   = Array(offset).fill(null);
             for (let d = 1; d <= last.getDate(); d++) days.push(d);
             while (days.length % 7 !== 0) days.push(null);
             return days;
         },
         calDateStr(d) {
             return d ? this.calYear + '-'
                 + String(this.calMonth + 1).padStart(2,'0') + '-'
                 + String(d).padStart(2,'0') : '';
         },
         calIsPast(d)     { return d && this.calDateStr(d) < '{{ $today }}'; },
         calIsToday(d)    { return d && this.calDateStr(d) === '{{ $today }}'; },
         calIsSelected(d) { return d && this.calDateStr(d) === this.selectedDate; },
         prevMonth() {
             if (this.calMonth === 0) { this.calMonth = 11; this.calYear--; }
             else this.calMonth--;
         },
         nextMonth() {
             if (this.calMonth === 11) { this.calMonth = 0; this.calYear++; }
             else this.calMonth++;
         },
         goToday() {
             this.calYear  = new Date().getFullYear();
             this.calMonth = new Date().getMonth();
         },
         openCal() {
             const parts   = this.selectedDate.split('-');
             this.calYear  = parseInt(parts[0]);
             this.calMonth = parseInt(parts[1]) - 1;
             this.showCal  = true;
         },
         selectCalDate(d) {
             if (!d || this.calIsPast(d)) return;
             this.setDate(this.calDateStr(d));
         },
         get calButtonLabel() {
             if (this.selectedDate !== '{{ $today }}' && this.selectedDate !== '{{ $tomorrow }}') {
                 const dt = new Date(this.selectedDate + 'T00:00:00');
                 return dt.getDate() + ' ' + this.calMonthNames[dt.getMonth()] + ' ' + dt.getFullYear();
             }
             return null;
         },

         /* ── Ticket expand state ──────────────────────────────────── */
         showBooking: false,
         openBooking() {
             const p = this.selectedDate.split('-');
             this.calYear  = parseInt(p[0]);
             this.calMonth = parseInt(p[1]) - 1;
             this.showBooking = true;
         },
         closeBooking() {
             this.showBooking = false;
             this.selectedSlot = null;
         },
         get slotDateMap() {
             const map = {};
             this.allSlots.forEach(s => {
                 if (!(s.date in map)) { map[s.date] = s.available > 0 ? 'available' : 'full'; }
                 else if (s.available > 0) { map[s.date] = 'available'; }
             });
             return map;
         },
         calDateStatus(d) { return d ? (this.slotDateMap[this.calDateStr(d)] ?? null) : null; },
         get minSlotPrice() {
             return this.allSlots.length ? Math.min(...this.allSlots.map(s => s.price)) : 0;
         },
     }">

    {{-- Category label --}}
    @if($activity->category)
    <p class="text-muted small mb-1">{{ ucfirst($activity->category) }}</p>
    @endif

    {{-- Title --}}
    <h1 class="fw-bold text-primary mb-3" style="font-size:1.8rem">{{ $activity->name }}</h1>

    {{-- Share row --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="https://wa.me/?text={{ urlencode($activity->name . ' ' . request()->url()) }}"
           target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-whatsapp"></i> WhatsApp
        </a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"
           target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-facebook"></i> Facebook
        </a>
        <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($activity->name) }}"
           target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-twitter-x"></i> X
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="navigator.share?.({ title:'{{ addslashes($activity->name) }}', url:'{{ request()->url() }}' })">
            <i class="bi bi-share"></i> Bagikan
        </button>
    </div>

    {{-- ─── Tab Navigation ─────────────────────────────────────────────────── --}}
    <ul class="nav activity-tabs mb-4" id="activityTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-desc" type="button">
                Deskripsi
            </button>
        </li>
        @if(!empty($activity->meta['what_to_expect']))
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-expect" type="button">
                Apa yang Diharapkan
            </button>
        </li>
        @endif
        @if(!empty($activity->meta['what_to_bring']) || !empty($activity->meta['cancellation_policy']))
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-notes" type="button">
                Hal-hal yang Perlu Diperhatikan
            </button>
        </li>
        @endif
        @if($activity->addons->isNotEmpty())
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-addons" type="button">
                Tambahan
                <span class="badge bg-primary ms-1" x-show="addonCount > 0" x-text="addonCount" x-cloak></span>
            </button>
        </li>
        @endif
        @if($activity->schedules->isNotEmpty())
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-hours" type="button">
                Jam Operasional
            </button>
        </li>
        @endif
        @if(!empty($activity->meta['location']))
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-location" type="button">
                Lokasi
            </button>
        </li>
        @endif
    </ul>

    {{-- ─── Tab Panes ──────────────────────────────────────────────────────── --}}
    <div class="tab-content mb-5">

        {{-- Deskripsi --}}
        <div class="tab-pane fade show active" id="tab-desc" role="tabpanel">

            {{-- Highlights --}}
            @if(!empty($activity->meta['highlights']))
            <div class="mb-4">
                <h6 class="fw-bold text-primary pb-1 mb-3"
                    style="border-bottom: 2px solid var(--safari-green); display:inline-block; padding-bottom:.25rem">
                    Sorotan
                </h6>
                <ul class="ps-3 mb-0">
                    @foreach((array)$activity->meta['highlights'] as $h)
                    <li class="mb-1">{{ $h }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Meta info --}}
            <div class="mb-4 p-3 bg-light rounded-3 small text-muted d-flex flex-wrap gap-3">
                @if($activity->duration_minutes)
                <span><i class="bi bi-clock me-1 text-primary"></i>Durasi: <strong>{{ $activity->duration_minutes }} menit</strong></span>
                @endif
                @if($activity->min_pax || $activity->max_pax)
                <span><i class="bi bi-people me-1 text-primary"></i>Peserta: <strong>{{ $activity->min_pax }}–{{ $activity->max_pax }} orang</strong></span>
                @endif
                @if($activity->min_age)
                <span><i class="bi bi-person me-1 text-primary"></i>Usia min: <strong>{{ $activity->min_age }} tahun</strong></span>
                @endif
                @if($activity->level)
                <span><i class="bi bi-bar-chart me-1 text-primary"></i>Level: <strong>{{ ucfirst($activity->level) }}</strong></span>
                @endif
                @if(!empty($activity->meta['location']))
                <span><i class="bi bi-geo-alt me-1 text-primary"></i>{{ $activity->meta['location'] }}</span>
                @endif
            </div>

            {{-- Description --}}
            @if($activity->description)
            <div class="activity-description lh-lg">
                {!! nl2br(e((string) $activity->description)) !!}
            </div>
            @endif

        </div>

        {{-- Tab: Tambahan Opsional ─────────────────────────────── --}}
        @if($activity->addons->isNotEmpty())
        <div class="tab-pane fade" id="tab-addons" role="tabpanel">

            <p class="text-muted small mb-4">
                Pilih layanan atau item tambahan yang ingin Anda sertakan. Harga akan ditambahkan ke total pesanan.
            </p>

            <div class="d-flex flex-column gap-3">
                @foreach($activity->addons as $addon)
                <div class="p-3 border rounded-3 d-flex align-items-center gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $addon->name }}</div>
                        <div class="text-muted small">
                            Rp {{ number_format($addon->price, 0, ',', '.') }} / {{ $addon->unit }}
                            &nbsp;·&nbsp; maks {{ $addon->max_qty }}
                        </div>
                    </div>
                    {{-- Qty controller --}}
                    <div class="d-flex align-items-center gap-2">
                        <button type="button"
                                class="btn btn-outline-secondary btn-sm px-2 py-1 lh-1"
                                @click="setAddon({{ $addon->id }}, -1)"
                                :disabled="(addonQty[{{ $addon->id }}] ?? 0) === 0">−</button>

                        <span class="fw-bold text-center" style="min-width:1.5rem"
                              x-text="addonQty[{{ $addon->id }}] ?? 0"></span>

                        <button type="button"
                                class="btn btn-outline-secondary btn-sm px-2 py-1 lh-1"
                                @click="setAddon({{ $addon->id }}, 1)"
                                :disabled="(addonQty[{{ $addon->id }}] ?? 0) >= {{ $addon->max_qty }}">+</button>
                    </div>
                    {{-- Subtotal --}}
                    <div class="text-end" style="min-width:110px">
                        <span class="fw-semibold text-primary"
                              x-show="(addonQty[{{ $addon->id }}] ?? 0) > 0" x-cloak
                              x-text="formatRp({{ $addon->price }} * (addonQty[{{ $addon->id }}] ?? 0))">
                        </span>
                        <span class="text-muted small"
                              x-show="(addonQty[{{ $addon->id }}] ?? 0) === 0">
                            + Rp {{ number_format($addon->price, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Addon subtotal --}}
            <div class="mt-4 p-3 bg-light rounded-3 d-flex justify-content-between align-items-center"
                 x-show="addonsTotal > 0" x-cloak>
                <span class="fw-semibold">Total Tambahan</span>
                <span class="fw-bold text-primary fs-5" x-text="formatRp(addonsTotal)"></span>
            </div>

        </div>
        @endif

        {{-- Apa yang Diharapkan --}}
        @if(!empty($activity->meta['what_to_expect']))
        <div class="tab-pane fade" id="tab-expect" role="tabpanel">
            @if(is_array($activity->meta['what_to_expect']))
                <ul class="ps-3 lh-lg">
                    @foreach($activity->meta['what_to_expect'] as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @else
                <div class="lh-lg">{!! nl2br(e($activity->meta['what_to_expect'])) !!}</div>
            @endif
        </div>
        @endif

        {{-- Hal-hal yang Perlu Diperhatikan --}}
        @if(!empty($activity->meta['what_to_bring']) || !empty($activity->meta['cancellation_policy']))
        <div class="tab-pane fade" id="tab-notes" role="tabpanel">
            @if(!empty($activity->meta['what_to_bring']))
            <div class="mb-4">
                <h6 class="fw-bold mb-2">Yang Perlu Dibawa</h6>
                @if(is_array($activity->meta['what_to_bring']))
                    <ul class="ps-3 lh-lg">
                        @foreach($activity->meta['what_to_bring'] as $item)
                        <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="lh-lg">{!! nl2br(e($activity->meta['what_to_bring'])) !!}</div>
                @endif
            </div>
            @endif
            @if(!empty($activity->meta['cancellation_policy']))
            <div>
                <h6 class="fw-bold mb-2">Kebijakan Pembatalan</h6>
                @if(is_array($activity->meta['cancellation_policy']))
                    <ul class="ps-3 lh-lg">
                        @foreach($activity->meta['cancellation_policy'] as $item)
                        <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="lh-lg">{!! nl2br(e($activity->meta['cancellation_policy'])) !!}</div>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- Jam Operasional --}}
        @if($activity->schedules->isNotEmpty())
        <div class="tab-pane fade" id="tab-hours" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle" style="max-width:420px">
                    <thead class="table-light">
                        <tr>
                            <th>Hari</th>
                            <th>Buka</th>
                            <th>Tutup</th>
                            <th>Kapasitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activity->schedules as $s)
                        <tr>
                            <td class="fw-medium">{{ $dayNames[$s->day_of_week] }}</td>
                            <td>{{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}</td>
                            <td>{{ $s->default_capacity }} pax</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Lokasi --}}
        @if(!empty($activity->meta['location']))
        <div class="tab-pane fade" id="tab-location" role="tabpanel">
            <p><i class="bi bi-geo-alt-fill text-primary me-2"></i>{{ $activity->meta['location'] }}</p>
        </div>
        @endif

    </div>

    {{-- ─── Periksa Ketersediaan + Pilihan Tiket ──────────────────────────── --}}
    <div>

        {{-- Periksa Ketersediaan --}}
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-calendar-check text-primary fs-5"></i>
            <h5 class="fw-bold mb-0 text-primary">Periksa Ketersediaan</h5>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-5">
            <button type="button" class="date-tab-btn"
                    :class="{ 'active': selectedDate === '{{ $today }}' && !showCal }"
                    @click="setDate('{{ $today }}')">
                Hari ini
            </button>
            <button type="button" class="date-tab-btn"
                    :class="{ 'active': selectedDate === '{{ $tomorrow }}' && !showCal }"
                    @click="setDate('{{ $tomorrow }}')">
                Besok
            </button>
            <button type="button" class="date-tab-btn"
                    :class="{ 'active': calButtonLabel !== null }"
                    @click="openCal()">
                <i class="bi bi-calendar3 me-1"></i>
                <span x-text="calButtonLabel ?? 'Pilih Tanggal'"></span>
            </button>
        </div>

        {{-- Pilihan Tiket --}}
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-ticket-perforated text-primary fs-5"></i>
            <h5 class="fw-bold mb-0 text-primary">Pilihan Tiket</h5>
        </div>

        @if($activity->slots->isEmpty())
        <div class="alert alert-warning small mb-4">
            <i class="bi bi-info-circle me-1"></i>Tidak ada tiket tersedia saat ini.
        </div>
        @else

        {{-- ── Collapsed card ───────────────────────────────────────── --}}
        <div class="ticket-card mb-4" x-show="!showBooking">
            <div class="ticket-info">
                <div class="ticket-name">{{ $activity->name }}</div>
                <div class="ticket-price mt-1">
                    Dari <strong x-text="formatRp(minSlotPrice)"></strong>
                    <span class="text-muted fw-normal"> / orang</span>
                </div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-calendar2-check me-1"></i>
                    {{ $activity->slots->count() }} slot tersedia
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm px-4 align-self-start"
                    @click="openBooking()">
                Pilih
            </button>
        </div>

        {{-- ── Expanded card ────────────────────────────────────────── --}}
        <div class="ticket-card mb-4" x-show="showBooking" x-cloak>

            {{-- Card header --}}
            <div class="d-flex justify-content-between align-items-start w-100 mb-3 pb-3"
                 style="border-bottom:1px solid #dee2e6">
                <div>
                    <div class="ticket-name">{{ $activity->name }}</div>
                    <div class="ticket-price mt-1">
                        Dari <strong x-text="formatRp(minSlotPrice)"></strong>
                        <span class="text-muted fw-normal"> / orang</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        @click="closeBooking()">Batal</button>
            </div>

            {{-- Two-column body --}}
            <div class="row g-4 w-100">

                {{-- LEFT: Detail & Termasuk --}}
                <div class="col-md-5">
                    <div>

                        {{-- Panel title --}}
                        <div class="detail-panel-title">Detail &amp; Termasuk</div>

                        {{-- Deskripsi --}}
                        @php $rawDesc = strip_tags($activity->description ?? ''); @endphp
                        @if($rawDesc)
                        <div class="detail-section">
                            <div class="detail-label">Deskripsi</div>
                            <div class="detail-body">{{ $rawDesc }}</div>
                        </div>
                        @endif

                        {{-- Termasuk --}}
                        @if(!empty($activity->meta['includes']))
                        <div class="detail-section">
                            <div class="detail-label">Termasuk</div>
                            <ul class="detail-list">
                                @foreach((array)$activity->meta['includes'] as $inc)
                                <li>{{ $inc }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        {{-- Cara Penggunaan --}}
                        @if(!empty($activity->meta['what_to_bring']))
                        <div class="detail-section">
                            <div class="detail-label">Cara Penggunaan</div>
                            @if(is_array($activity->meta['what_to_bring']))
                            <ul class="detail-list">
                                @foreach($activity->meta['what_to_bring'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                            @else
                            <div class="detail-body">{{ $activity->meta['what_to_bring'] }}</div>
                            @endif
                        </div>
                        @endif

                    </div>
                </div>

                {{-- RIGHT: Calendar + Slot + Pax --}}
                <div class="col-md-7">

                    {{-- ─ Date calendar ──────────────────────────────── --}}
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="small fw-semibold">Pilih Tanggal Kunjungan</span>
                        <div class="d-flex align-items-center gap-3">
                            <span class="d-flex align-items-center gap-1" style="font-size:.72rem;color:#6c757d">
                                <span style="color:#198754;font-size:.6rem">●</span> Tersedia
                                <span class="ms-1" style="color:#dc3545;font-size:.6rem">●</span> Terjual habis
                            </span>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-1 py-0 lh-1"
                                        style="font-size:.75rem" @click="prevMonth()">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm px-1 py-0 lh-1"
                                        style="font-size:.75rem" @click="nextMonth()">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted mb-2"
                         x-text="calMonthNames[calMonth] + ' ' + calYear"></div>

                    {{-- Day headers --}}
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center">
                        <template x-for="n in calDayNames" :key="n">
                            <div x-text="n"
                                 style="font-size:.68rem;font-weight:600;color:#6c757d;padding:2px 0"></div>
                        </template>
                    </div>

                    {{-- Day grid with dots --}}
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center;margin-bottom:.75rem">
                        <template x-for="(day, idx) in calDays" :key="idx">
                            <div style="display:flex;flex-direction:column;align-items:center;padding:2px">
                                <button type="button"
                                        x-show="day !== null && !calIsPast(day)"
                                        class="btn rounded-circle p-0"
                                        style="width:30px;height:30px;font-size:.8rem;line-height:1"
                                        :class="calIsSelected(day)
                                            ? 'btn-primary text-white fw-bold'
                                            : 'btn-link cal-day'"
                                        @click="selectCalDate(day)"
                                        x-text="day">
                                </button>
                                <span x-show="day !== null && !calIsPast(day) && calDateStatus(day) !== null"
                                      style="width:5px;height:5px;border-radius:50%;margin-top:1px;display:block"
                                      :style="calDateStatus(day) === 'available'
                                          ? 'background:#198754'
                                          : 'background:#dc3545'">
                                </span>
                            </div>
                        </template>
                    </div>

                    {{-- ─ Slot time picker ────────────────────────────── --}}
                    <div class="small fw-semibold mb-2">Pilih Slot Waktu</div>

                    <div x-show="slotsForDate.length === 0" class="text-muted small mb-3">
                        Silakan pilih tanggal.
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-4" x-show="slotsForDate.length > 0">
                        <template x-for="slot in slotsForDate" :key="slot.id">
                            <button type="button"
                                    class="text-start rounded-3 px-3 py-2"
                                    style="border:1.5px solid;min-width:130px;background:#fff;cursor:pointer"
                                    :style="slot.available < 1 ? 'opacity:.45;cursor:not-allowed' : ''"
                                    :class="selectedSlot?.id === slot.id
                                        ? 'border-primary'
                                        : 'border-secondary'"
                                    :disabled="slot.available < 1"
                                    @click="selectSlot(slot)">
                                <div class="fw-bold text-primary" style="font-size:.85rem"
                                     x-text="slot.time"></div>
                                <div class="text-muted" style="font-size:.72rem"
                                     x-text="slot.available + ' tersisa'"></div>
                            </button>
                        </template>
                    </div>

                    {{-- ─ Quantity + Checkout ─────────────────────────── --}}
                    <template x-if="selectedSlot">
                        <div>
                            <div class="small fw-semibold mb-3">Kuantitas</div>

                            {{-- Adult row --}}
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <div class="fw-semibold" style="font-size:.9rem">Adult</div>
                                    <div class="text-muted" style="font-size:.75rem">
                                        Rentang Usia ({{ $activity->min_age ?? 12 }} - 99)
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted me-1" style="font-size:.85rem"
                                          x-text="'IDR ' + selectedSlot.price.toLocaleString('id-ID')"></span>
                                    <button type="button"
                                            class="btn btn-primary d-flex align-items-center justify-content-center"
                                            style="width:36px;height:36px;padding:0;font-size:1.1rem;flex-shrink:0"
                                            :disabled="paxAdult <= 0"
                                            @click="paxAdult = Math.max(0, paxAdult - 1)">−</button>
                                    <span class="fw-bold text-center" style="min-width:1.5rem;font-size:1rem"
                                          x-text="paxAdult"></span>
                                    <button type="button"
                                            class="btn btn-primary d-flex align-items-center justify-content-center"
                                            style="width:36px;height:36px;padding:0;font-size:1.1rem;flex-shrink:0"
                                            :disabled="pax >= maxTotal"
                                            @click="paxAdult = pax < maxTotal ? paxAdult + 1 : paxAdult">+</button>
                                </div>
                            </div>

                            {{-- Child row --}}
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <div class="fw-semibold" style="font-size:.9rem">Child</div>
                                    <div class="text-muted" style="font-size:.75rem">
                                        Rentang Usia (3 - {{ ($activity->min_age ?? 12) - 1 }})
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted me-1" style="font-size:.85rem"
                                          x-text="'IDR ' + selectedSlot.price.toLocaleString('id-ID')"></span>
                                    <button type="button"
                                            class="btn btn-primary d-flex align-items-center justify-content-center"
                                            style="width:36px;height:36px;padding:0;font-size:1.1rem;flex-shrink:0"
                                            :disabled="paxChild <= 0"
                                            @click="paxChild = Math.max(0, paxChild - 1)">−</button>
                                    <span class="fw-bold text-center" style="min-width:1.5rem;font-size:1rem"
                                          x-text="paxChild"></span>
                                    <button type="button"
                                            class="btn btn-primary d-flex align-items-center justify-content-center"
                                            style="width:36px;height:36px;padding:0;font-size:1.1rem;flex-shrink:0"
                                            :disabled="pax >= maxTotal"
                                            @click="paxChild = pax < maxTotal ? paxChild + 1 : paxChild">+</button>
                                </div>
                            </div>

                            {{-- Addon total row --}}
                            <template x-if="addonsTotal > 0">
                                <div class="d-flex justify-content-between text-muted small mb-2">
                                    <span>Tambahan</span>
                                    <span x-text="formatRp(addonsTotal)"></span>
                                </div>
                            </template>

                            <form method="POST" action="{{ route('cart.add') }}">
                                @csrf
                                <input type="hidden" name="slot_id"     :value="selectedSlot.id">
                                <input type="hidden" name="pax_adult"   :value="paxAdult">
                                <input type="hidden" name="pax_child"   :value="paxChild">
                                <input type="hidden" name="addons_json" :value="addonsJson">
                                <button type="submit" class="btn btn-primary w-100 fw-semibold py-2 mt-1"
                                        :disabled="pax < 1">
                                    <i class="bi bi-bag-plus me-2"></i>
                                    Tambah ke Keranjang
                                    <template x-if="pax > 0">
                                        <span class="ms-2 small opacity-75"
                                              x-text="'(' + formatRp(grandTotal) + ')'"></span>
                                    </template>
                                </button>
                            </form>
                        </div>
                    </template>

                </div>{{-- /col-md-7 --}}
            </div>{{-- /row --}}

        </div>{{-- /expanded card --}}
        @endif

    </div>

    {{-- ─── Calendar Modal ─────────────────────────────────────────────────────── --}}
    {{-- x-show only toggles block/none; flex centering lives on the inner wrapper --}}
    <div x-show="showCal" x-cloak style="position:fixed;inset:0;z-index:1055">

        {{-- Backdrop --}}
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.45);"
             @click="showCal = false"></div>

        {{-- Flex centering wrapper --}}
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem">

        {{-- Dialog --}}
        <div class="bg-white rounded-3 shadow-lg p-4"
             style="width:100%;max-width:450px">

            {{-- Header: month select + year | Today < > × --}}
            <div class="d-flex align-items-center gap-2 mb-4">
                <select class="fw-bold fs-5 border-0 bg-transparent p-0 pe-1"
                        style="outline:none;cursor:pointer"
                        x-model.number="calMonth">
                    <template x-for="(name, i) in calMonthNames" :key="i">
                        <option :value="i" x-text="name"></option>
                    </template>
                </select>
                <span class="fw-bold fs-5" x-text="calYear"></span>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm px-3"
                            @click="goToday()">Today</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2"
                            @click="prevMonth()">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2"
                            @click="nextMonth()">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" class="btn-close ms-1"
                            @click="showCal = false" aria-label="Tutup"></button>
                </div>
            </div>

            {{-- Day name headers --}}
            <div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center">
                <template x-for="name in calDayNames" :key="name">
                    <div class="text-muted small fw-semibold py-1" x-text="name"></div>
                </template>
            </div>

            {{-- Day grid --}}
            <div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center">
                <template x-for="(day, idx) in calDays" :key="idx">
                    <div style="display:flex;align-items:center;justify-content:center;padding:3px">
                        <button type="button"
                                x-show="day !== null && !calIsPast(day)"
                                class="btn rounded-circle p-0"
                                style="width:38px;height:38px;font-size:.9rem"
                                :class="calIsSelected(day) || calIsToday(day)
                                    ? 'btn-primary text-white fw-bold'
                                    : 'btn-link cal-day'"
                                @click="selectCalDate(day)"
                                x-text="day">
                        </button>
                    </div>
                </template>
            </div>

        </div>{{-- /Dialog --}}
        </div>{{-- /flex centering wrapper --}}
    </div>{{-- /x-show overlay --}}

</div>
@endsection
