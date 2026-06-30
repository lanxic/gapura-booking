@extends('layouts.tenant-storefront')

@section('title', $product->name)

@push('head')
<style>
.detail-panel-title{font-weight:700;font-size:.92rem;color:#212529;padding-bottom:.5rem;margin-bottom:.8rem;border-bottom:1.5px solid #dee2e6}
.detail-section{margin-bottom:.85rem}
.detail-label{font-size:.8rem;font-weight:600;color:#212529;margin-bottom:.3rem}
.detail-body{font-size:.82rem;line-height:1.6;color:#495057}
.detail-list{margin:0;padding-left:1.2rem;font-size:.82rem;line-height:1.6;color:#495057}
.detail-list li+li{margin-top:.25rem}

/* ── Calendar cells ── */
.cal-cell{display:flex;flex-direction:column;align-items:center;padding:2px 0}
.cal-btn{
    width:36px;height:36px;min-width:36px;flex-shrink:0;
    border:none;border-radius:50%;padding:0;
    display:flex;align-items:center;justify-content:center;
    font-size:.88rem;font-weight:600;cursor:pointer;
    transition:background .12s,color .12s;
    background:transparent;color:#374151
}
.cal-btn:disabled{color:#d1d5db;cursor:default;pointer-events:none}
.cal-btn.is-today{background:#f0fdf4;color:#166534;box-shadow:inset 0 0 0 1.5px #16a34a}
.cal-btn.is-selected{background:#166534;color:#fff;box-shadow:0 2px 8px rgba(22,101,52,.25)}
.cal-btn:not(:disabled):not(.is-selected):hover{background:#f3f4f6}
.cal-dot{width:5px;height:5px;border-radius:50%;margin-top:2px;flex-shrink:0}
</style>
@endpush

@section('content')

@php
    $today    = now()->toDateString();
    $tomorrow = now()->addDay()->toDateString();
    $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    $firstAvailableSlot = $product->slots->where('status', 'available')->first();
    $calInitYear  = $firstAvailableSlot ? $firstAvailableSlot->date->year      : now()->year;
    $calInitMonth = $firstAvailableSlot ? $firstAvailableSlot->date->month - 1 : now()->month - 1;

    // Hanya kirim slot available ke widget booking (untuk picker & kapasitas)
    $slotsJson = $product->slots
        ->where('status', 'available')
        ->map(fn($s) => [
            'id'          => $s->id,
            'date'        => $s->date->format('Y-m-d'),
            'time'        => $s->start_time->format('H:i') . ' – ' . $s->end_time->format('H:i'),
            'available'   => $s->remaining_capacity,
            'price_adult' => (int) $s->price_adult,
            'price_child' => (int) ($s->price_child ?? $s->price_adult),
        ])->values()->toJson();

    // Map tanggal → status untuk dot kalender (available | full)
    $slotDateMapJson = $product->slots
        ->groupBy(fn($s) => $s->date->format('Y-m-d'))
        ->map(fn($daySlots) => $daySlots->contains(fn($s) => $s->remaining_capacity > 0) ? 'available' : 'full')
        ->toJson();

    $addonsJson = $product->addons->map(fn($a) => [
        'id'      => $a->id,
        'name'    => $a->name,
        'price'   => $a->price,
        'unit'    => $a->unit,
        'max_qty' => $a->max_qty,
    ])->toJson();
@endphp

<div class="container py-3" style="max-width:860px">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item">
                <a href="{{ route('tenant.home') }}" class="text-primary text-decoration-none">Beranda</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('tenant.products.index') }}" class="text-primary text-decoration-none">Produk</a>
            </li>
            <li class="breadcrumb-item active text-muted">{{ $product->name }}</li>
        </ol>
    </nav>
</div>

{{-- Gallery --}}
<div class="container" style="max-width:860px">
    @if($product->media->isNotEmpty())
    <div x-data="{
            items: {{ $product->media->values()->toJson() }},
            cur: 0,
            go(i)  { this.cur = i },
            next() { this.cur = (this.cur + 1) % this.items.length },
            prev() { this.cur = (this.cur - 1 + this.items.length) % this.items.length },
         }" class="mb-4">
        <div class="gallery-main position-relative rounded-3 overflow-hidden mb-2">
            <img :src="items[cur].url" alt="{{ $product->name }}"
                 class="w-100 d-block" style="height:420px;object-fit:cover">
            @if($product->media->count() > 1)
                <button class="gallery-btn gallery-btn-prev" @click="prev()" type="button"><i class="bi bi-chevron-left"></i></button>
                <button class="gallery-btn gallery-btn-next" @click="next()" type="button"><i class="bi bi-chevron-right"></i></button>
            @endif
        </div>
        @if($product->media->count() > 1)
        <div class="d-flex gap-2 pb-1" style="overflow-x:auto">
            @foreach($product->media as $idx => $m)
            <img src="{{ $m->url }}" alt=""
                 class="gallery-thumb rounded-2"
                 :class="{ 'active': cur === {{ $idx }} }"
                 @click="go({{ $idx }})"
                 style="width:90px;height:62px;object-fit:cover;cursor:pointer">
            @endforeach
        </div>
        @endif
    </div>
    @else
    <div class="mb-4 rounded-3 bg-light d-flex align-items-center justify-content-center" style="height:300px">
        <i class="bi bi-image text-muted" style="font-size:3rem"></i>
    </div>
    @endif
</div>

{{-- Main Content --}}
<div class="container py-4" style="max-width:860px"
     x-data="{
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
         allSlots:     {{ $slotsJson }},
         selectedDate: '{{ $today }}',
         selectedSlot: null,
         paxAdult:     0,
         paxChild:     0,
         minPax:       {{ $product->min_pax ?? 1 }},
         maxPax:       {{ $product->max_pax }},
         get pax()         { return this.paxAdult + this.paxChild; },
         get maxTotal()    { return this.selectedSlot ? Math.min(this.selectedSlot.available, this.maxPax) : this.maxPax; },
         get slotsForDate() { return this.allSlots.filter(s => s.date === this.selectedDate); },
         get ticketTotal()  { return this.selectedSlot ? this.selectedSlot.price_adult * this.paxAdult + (this.selectedSlot.price_child ?? this.selectedSlot.price_adult) * this.paxChild : 0; },
         get grandTotal()   { return this.ticketTotal + this.addonsTotal; },
         setDate(d) {
             this.selectedDate = d; this.selectedSlot = null; this.showCal = false;
             const p = d.split('-'); this.calYear = parseInt(p[0]); this.calMonth = parseInt(p[1]) - 1;
         },
         selectSlot(slot) {
             if (slot.available < 1) return;
             this.selectedSlot = slot; this.paxAdult = 0; this.paxChild = 0;
         },
         formatRp(n) { return 'Rp ' + n.toLocaleString('id-ID'); },
         showCal:      false,
         calYear:      {{ $calInitYear }},
         calMonth:     {{ $calInitMonth }},
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
             return d ? this.calYear + '-' + String(this.calMonth+1).padStart(2,'0') + '-' + String(d).padStart(2,'0') : '';
         },
         calIsPast(d)     { return d && this.calDateStr(d) < '{{ $today }}'; },
         calIsToday(d)    { return d && this.calDateStr(d) === '{{ $today }}'; },
         calIsSelected(d) { return d && this.calDateStr(d) === this.selectedDate; },
         prevMonth() { if(this.calMonth===0){this.calMonth=11;this.calYear--;}else this.calMonth--; },
         nextMonth() { if(this.calMonth===11){this.calMonth=0;this.calYear++;}else this.calMonth++; },
         goToday()   { this.calYear=new Date().getFullYear(); this.calMonth=new Date().getMonth(); },
         openCal() {
             const parts=this.selectedDate.split('-');
             this.calYear=parseInt(parts[0]); this.calMonth=parseInt(parts[1])-1; this.showCal=true;
         },
         selectCalDate(d) { if(!d||this.calIsPast(d)) return; this.setDate(this.calDateStr(d)); },
         get calButtonLabel() {
             if(this.selectedDate!=='{{ $today }}'&&this.selectedDate!=='{{ $tomorrow }}'){
                 const dt=new Date(this.selectedDate+'T00:00:00');
                 return dt.getDate()+' '+this.calMonthNames[dt.getMonth()]+' '+dt.getFullYear();
             }
             return null;
         },
         showBooking: false,
         openBooking() { const p=this.selectedDate.split('-'); this.calYear=parseInt(p[0]); this.calMonth=parseInt(p[1])-1; this.showBooking=true; },
         closeBooking() { this.showBooking=false; this.selectedSlot=null; },
         slotDateMap:  {{ $slotDateMapJson }},
         calDateStatus(d) { return d?(this.slotDateMap[this.calDateStr(d)]??null):null; },
         get minSlotPrice() { return this.allSlots.length?Math.min(...this.allSlots.map(s=>s.price_adult)):0; },
         get slotsAvailForDate() { return this.slotsForDate.filter(s=>s.available>0).length; },
         get totalPaxForDate() { return this.slotsForDate.reduce((sum,s)=>sum+(s.available>0?s.available:0),0); },
         get minPriceForDate() {
             const s=this.slotsForDate.filter(s=>s.available>0);
             return s.length?Math.min(...s.map(s=>s.price_adult)):this.minSlotPrice;
         },
     }">

    @if($product->category)
    <p class="text-muted small mb-1">{{ ucfirst($product->category) }}</p>
    @endif

    <h1 class="fw-bold text-primary mb-3" style="font-size:1.8rem">{{ $product->name }}</h1>

    {{-- Share --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="https://wa.me/?text={{ urlencode($product->name . ' ' . request()->url()) }}"
           target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-whatsapp"></i> WhatsApp
        </a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"
           target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-facebook"></i> Facebook
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="navigator.share?.({ title:'{{ addslashes($product->name) }}', url:'{{ request()->url() }}' })">
            <i class="bi bi-share"></i> Bagikan
        </button>
    </div>

    {{-- Tabs --}}
    <ul class="nav activity-tabs mb-4" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-desc" type="button">Deskripsi</button>
        </li>
        @if(!empty($product->meta['what_to_expect']))
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-expect" type="button">Apa yang Diharapkan</button>
        </li>
        @endif
        @if(!empty($product->meta['what_to_bring']) || !empty($product->meta['cancellation_policy']))
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-notes" type="button">Hal-hal Penting</button>
        </li>
        @endif
        @if($product->addons->isNotEmpty())
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-addons" type="button">
                Tambahan
                <span class="badge bg-primary ms-1" x-show="addonCount > 0" x-text="addonCount" x-cloak></span>
            </button>
        </li>
        @endif
        @if($product->schedules->isNotEmpty())
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-hours" type="button">Jam Operasional</button>
        </li>
        @endif
    </ul>

    {{-- Tab Panes --}}
    <div class="tab-content mb-5">

        <div class="tab-pane fade show active" id="tab-desc" role="tabpanel">
            @if(!empty($product->meta['highlights']))
            <div class="mb-4">
                <h6 class="fw-bold text-primary pb-1 mb-3" style="border-bottom:2px solid var(--safari-green);display:inline-block;padding-bottom:.25rem">Sorotan</h6>
                <ul class="ps-3 mb-0">
                    @foreach((array)$product->meta['highlights'] as $h)
                    <li class="mb-1">{{ $h }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <div class="mb-4 p-3 bg-light rounded-3 small text-muted d-flex flex-wrap gap-3">
                @if($product->duration_minutes)
                <span><i class="bi bi-clock me-1 text-primary"></i>Durasi: <strong>{{ $product->duration_minutes }} menit</strong></span>
                @endif
                @if($product->min_pax && $product->max_pax)
                <span><i class="bi bi-people me-1 text-primary"></i>Peserta: <strong>{{ $product->min_pax }}–{{ $product->max_pax }} orang</strong></span>
                @endif
                @if($product->min_age)
                <span><i class="bi bi-person me-1 text-primary"></i>Usia min: <strong>{{ $product->min_age }} tahun</strong></span>
                @endif
                @if($product->level)
                <span><i class="bi bi-bar-chart me-1 text-primary"></i>Level: <strong>{{ ucfirst($product->level) }}</strong></span>
                @endif
            </div>
            @if($product->description)
            <div class="lh-lg">{!! nl2br(e((string) $product->description)) !!}</div>
            @endif
        </div>

        @if($product->addons->isNotEmpty())
        <div class="tab-pane fade" id="tab-addons" role="tabpanel">
            <p class="text-muted small mb-4">Pilih layanan tambahan. Harga akan ditambahkan ke total pesanan.</p>
            <div class="d-flex flex-column gap-3">
                @foreach($product->addons as $addon)
                <div class="p-3 border rounded-3 d-flex align-items-center gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $addon->name }}</div>
                        <div class="text-muted small">Rp {{ number_format($addon->price, 0, ',', '.') }} / {{ $addon->unit }} &nbsp;·&nbsp; maks {{ $addon->max_qty }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 lh-1"
                                @click="setAddon({{ $addon->id }}, -1)"
                                :disabled="(addonQty[{{ $addon->id }}] ?? 0) === 0">−</button>
                        <span class="fw-bold text-center" style="min-width:1.5rem" x-text="addonQty[{{ $addon->id }}] ?? 0"></span>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 lh-1"
                                @click="setAddon({{ $addon->id }}, 1)"
                                :disabled="(addonQty[{{ $addon->id }}] ?? 0) >= {{ $addon->max_qty }}">+</button>
                    </div>
                    <div class="text-end" style="min-width:110px">
                        <span class="fw-semibold text-primary" x-show="(addonQty[{{ $addon->id }}] ?? 0) > 0" x-cloak
                              x-text="formatRp({{ $addon->price }} * (addonQty[{{ $addon->id }}] ?? 0))"></span>
                        <span class="text-muted small" x-show="(addonQty[{{ $addon->id }}] ?? 0) === 0">
                            + Rp {{ number_format($addon->price, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 p-3 bg-light rounded-3 d-flex justify-content-between align-items-center"
                 x-show="addonsTotal > 0" x-cloak>
                <span class="fw-semibold">Total Tambahan</span>
                <span class="fw-bold text-primary fs-5" x-text="formatRp(addonsTotal)"></span>
            </div>
        </div>
        @endif

        @if(!empty($product->meta['what_to_expect']))
        <div class="tab-pane fade" id="tab-expect" role="tabpanel">
            @if(is_array($product->meta['what_to_expect']))
                <ul class="ps-3 lh-lg">@foreach($product->meta['what_to_expect'] as $item)<li>{{ $item }}</li>@endforeach</ul>
            @else
                <div class="lh-lg">{!! nl2br(e($product->meta['what_to_expect'])) !!}</div>
            @endif
        </div>
        @endif

        @if(!empty($product->meta['what_to_bring']) || !empty($product->meta['cancellation_policy']))
        <div class="tab-pane fade" id="tab-notes" role="tabpanel">
            @if(!empty($product->meta['what_to_bring']))
            <div class="mb-4">
                <h6 class="fw-bold mb-2">Yang Perlu Dibawa</h6>
                @if(is_array($product->meta['what_to_bring']))
                    <ul class="ps-3 lh-lg">@foreach($product->meta['what_to_bring'] as $i)<li>{{ $i }}</li>@endforeach</ul>
                @else
                    <div class="lh-lg">{!! nl2br(e($product->meta['what_to_bring'])) !!}</div>
                @endif
            </div>
            @endif
            @if(!empty($product->meta['cancellation_policy']))
            <div>
                <h6 class="fw-bold mb-2">Kebijakan Pembatalan</h6>
                @if(is_array($product->meta['cancellation_policy']))
                    <ul class="ps-3 lh-lg">@foreach($product->meta['cancellation_policy'] as $i)<li>{{ $i }}</li>@endforeach</ul>
                @else
                    <div class="lh-lg">{!! nl2br(e($product->meta['cancellation_policy'])) !!}</div>
                @endif
            </div>
            @endif
        </div>
        @endif

        @if($product->schedules->isNotEmpty())
        <div class="tab-pane fade" id="tab-hours" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle" style="max-width:420px">
                    <thead class="table-light"><tr><th>Hari</th><th>Buka</th><th>Tutup</th><th>Kapasitas</th></tr></thead>
                    <tbody>
                        @foreach($product->schedules as $s)
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

    </div>

    {{-- Periksa Ketersediaan --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-calendar-check text-primary fs-5"></i>
        <h5 class="fw-bold mb-0 text-primary">Periksa Ketersediaan</h5>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-5">
        <button type="button" class="date-tab-btn"
                :class="{ 'active': selectedDate === '{{ $today }}' && !showCal }"
                @click="setDate('{{ $today }}')">Hari ini</button>
        <button type="button" class="date-tab-btn"
                :class="{ 'active': selectedDate === '{{ $tomorrow }}' && !showCal }"
                @click="setDate('{{ $tomorrow }}')">Besok</button>
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

    @if($product->slots->isEmpty())
    <div class="alert alert-warning small mb-4">
        <i class="bi bi-info-circle me-1"></i>Tidak ada tiket tersedia saat ini.
    </div>
    @else

    {{-- Collapsed --}}
    <div class="ticket-card mb-4" x-show="!showBooking">
        <div class="ticket-info">
            <div class="ticket-name">{{ $product->name }}</div>
            <div class="ticket-price mt-1">
                Dari <strong x-text="formatRp(slotsAvailForDate > 0 ? minPriceForDate : minSlotPrice)"></strong>
                <span class="text-muted fw-normal"> / orang</span>
            </div>
            {{-- Slot availability for selected date --}}
            <template x-if="slotsAvailForDate > 0">
                <div class="text-success small mt-1 fw-medium">
                    <i class="bi bi-calendar2-check me-1"></i>
                    <span x-text="totalPaxForDate + ' pax tersedia'"></span>
                </div>
            </template>
            <template x-if="slotsForDate.length > 0 && slotsAvailForDate === 0">
                <div class="text-danger small mt-1 fw-medium">
                    <i class="bi bi-calendar-x me-1"></i>Slot penuh untuk tanggal ini
                </div>
            </template>
            <template x-if="slotsForDate.length === 0">
                <div class="text-muted small mt-1">
                    <i class="bi bi-calendar-minus me-1"></i>Tidak ada slot untuk tanggal ini
                </div>
            </template>
        </div>
        <button type="button" class="btn btn-primary btn-sm px-4 align-self-start"
                :disabled="slotsAvailForDate === 0 && slotsForDate.length > 0"
                @click="openBooking()">Pilih</button>
    </div>

    {{-- Expanded --}}
    <div class="ticket-card mb-4" x-show="showBooking" x-cloak>
        <div class="d-flex justify-content-between align-items-start w-100 mb-3 pb-3" style="border-bottom:1px solid #dee2e6">
            <div>
                <div class="ticket-name">{{ $product->name }}</div>
                <div class="ticket-price mt-1">
                    Dari <strong x-text="formatRp(slotsAvailForDate > 0 ? minPriceForDate : minSlotPrice)"></strong>
                    <span class="text-muted fw-normal"> / orang</span>
                </div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="closeBooking()">Batal</button>
        </div>

        <div class="row g-4 w-100">
            {{-- Detail --}}
            <div class="col-md-5">
                <div class="detail-panel-title">Detail &amp; Termasuk</div>
                @php $rawDesc = strip_tags($product->description ?? ''); @endphp
                @if($rawDesc)
                <div class="detail-section">
                    <div class="detail-label">Deskripsi</div>
                    <div class="detail-body">{{ Str::limit($rawDesc, 200) }}</div>
                </div>
                @endif
                @if(!empty($product->meta['includes']))
                <div class="detail-section">
                    <div class="detail-label">Termasuk</div>
                    <ul class="detail-list">
                        @foreach((array)$product->meta['includes'] as $inc)<li>{{ $inc }}</li>@endforeach
                    </ul>
                </div>
                @endif
                @if(!empty($product->meta['what_to_bring']))
                <div class="detail-section">
                    <div class="detail-label">Yang Perlu Dibawa</div>
                    @if(is_array($product->meta['what_to_bring']))
                        <ul class="detail-list">@foreach($product->meta['what_to_bring'] as $i)<li>{{ $i }}</li>@endforeach</ul>
                    @else
                        <div class="detail-body">{{ $product->meta['what_to_bring'] }}</div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Calendar + Slot + Pax --}}
            <div class="col-md-7">

                {{-- Calendar header --}}
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div style="font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af">Pilih Tanggal Kunjungan</div>
                        <div class="fw-bold" style="font-size:1.1rem;color:#111827" x-text="calMonthNames[calMonth] + ' ' + calYear"></div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3" style="font-size:.75rem;color:#6c757d">
                            <span class="d-flex align-items-center gap-1">
                                <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block"></span>Tersedia
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <span style="width:8px;height:8px;border-radius:50%;background:#dc2626;display:inline-block"></span>Tidak Tersedia
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center"
                                    @click="prevMonth()"><i class="bi bi-chevron-left" style="font-size:.8rem"></i></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center"
                                    @click="nextMonth()"><i class="bi bi-chevron-right" style="font-size:.8rem"></i></button>
                        </div>
                    </div>
                </div>

                {{-- Day names --}}
                <div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center;margin-bottom:.35rem">
                    <template x-for="n in calDayNames" :key="n">
                        <div x-text="n" style="font-size:.72rem;font-weight:700;color:#9ca3af;padding:4px 0;letter-spacing:.03em"></div>
                    </template>
                </div>

                {{-- Day grid --}}
                <div style="display:grid;grid-template-columns:repeat(7,1fr);margin-bottom:1.25rem">
                    <template x-for="(day, idx) in calDays" :key="idx">
                        <div class="cal-cell">
                            {{-- Empty filler for null days --}}
                            <template x-if="day === null">
                                <div style="width:36px;height:36px"></div>
                            </template>
                            {{-- Clickable day button --}}
                            <template x-if="day !== null">
                                <button type="button"
                                        class="cal-btn"
                                        :class="{
                                            'is-selected': !calIsPast(day) && calIsSelected(day),
                                            'is-today':    !calIsPast(day) && !calIsSelected(day) && calIsToday(day)
                                        }"
                                        :disabled="calIsPast(day)"
                                        @click="selectCalDate(day)"
                                        x-text="day"></button>
                            </template>
                            {{-- Availability dot --}}
                            <div class="cal-dot"
                                 x-show="day !== null && !calIsPast(day) && calDateStatus(day) !== null"
                                 :style="calDateStatus(day)==='available'?'background:#16a34a':'background:#dc2626'"></div>
                        </div>
                    </template>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="small fw-semibold">Pilih Slot Waktu</div>
                    <div class="d-flex align-items-center gap-2" style="font-size:.68rem;color:#6c757d">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#198754;flex-shrink:0"></span>Tersedia
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#dc3545;flex-shrink:0;margin-left:4px"></span>Tidak Tersedia
                    </div>
                </div>
                <div x-show="slotsForDate.length === 0" class="text-muted small mb-3">Silakan pilih tanggal.</div>
                <div class="d-flex flex-wrap gap-2 mb-4" x-show="slotsForDate.length > 0">
                    <template x-for="slot in slotsForDate" :key="slot.id">
                        <button type="button"
                                class="text-start rounded-3 px-3 py-2"
                                style="border:1.5px solid;min-width:130px;background:#fff;cursor:pointer;transition:border-color .15s"
                                :style="slot.available < 1 ? 'cursor:not-allowed;background:#fef2f2' : ''"
                                :class="selectedSlot?.id === slot.id ? 'border-primary' : (slot.available < 1 ? 'border-danger' : 'border-secondary')"
                                :disabled="slot.available < 1"
                                @click="selectSlot(slot)">
                            <div class="fw-bold" style="font-size:.85rem"
                                 :class="slot.available < 1 ? 'text-danger' : 'text-primary'"
                                 x-text="slot.time"></div>
                            <div style="font-size:.7rem;margin-top:2px;display:flex;align-items:center;gap:3px">
                                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;flex-shrink:0"
                                      :style="slot.available < 1 ? 'background:#dc3545' : 'background:#198754'"></span>
                                <span :class="slot.available < 1 ? 'text-danger fw-semibold' : 'text-success'"
                                      x-text="slot.available < 1 ? 'Penuh' : slot.available + ' tersisa'"></span>
                            </div>
                        </button>
                    </template>
                </div>

                <template x-if="selectedSlot">
                    <div>
                        <div class="small fw-semibold mb-3">Kuantitas</div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem">Adult</div>
                                <div class="text-muted" style="font-size:.75rem">Rentang Usia ({{ $product->min_age ?? 12 }} - 99)</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted me-1" style="font-size:.85rem" x-text="'Rp ' + selectedSlot.price_adult.toLocaleString('id-ID')"></span>
                                <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center"
                                        style="width:36px;height:36px;padding:0;font-size:1.1rem"
                                        :disabled="paxAdult <= 0" @click="paxAdult = Math.max(0, paxAdult - 1)">−</button>
                                <span class="fw-bold text-center" style="min-width:1.5rem;font-size:1rem" x-text="paxAdult"></span>
                                <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center"
                                        style="width:36px;height:36px;padding:0;font-size:1.1rem"
                                        :disabled="pax >= maxTotal" @click="paxAdult = pax < maxTotal ? paxAdult + 1 : paxAdult">+</button>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem">Child</div>
                                <div class="text-muted" style="font-size:.75rem">Rentang Usia (3 - {{ ($product->min_age ?? 12) - 1 }})</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted me-1" style="font-size:.85rem" x-text="'Rp ' + (selectedSlot.price_child ?? selectedSlot.price_adult).toLocaleString('id-ID')"></span>
                                <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center"
                                        style="width:36px;height:36px;padding:0;font-size:1.1rem"
                                        :disabled="paxChild <= 0" @click="paxChild = Math.max(0, paxChild - 1)">−</button>
                                <span class="fw-bold text-center" style="min-width:1.5rem;font-size:1rem" x-text="paxChild"></span>
                                <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center"
                                        style="width:36px;height:36px;padding:0;font-size:1.1rem"
                                        :disabled="pax >= maxTotal" @click="paxChild = pax < maxTotal ? paxChild + 1 : paxChild">+</button>
                            </div>
                        </div>
                        <template x-if="addonsTotal > 0">
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>Tambahan</span>
                                <span x-text="formatRp(addonsTotal)"></span>
                            </div>
                        </template>
                        <form method="POST" action="{{ route('tenant.cart.add') }}">
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
                                    <span class="ms-2 small opacity-75" x-text="'(' + formatRp(grandTotal) + ')'"></span>
                                </template>
                            </button>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </div>
    @endif

    {{-- Calendar modal --}}
    <div x-show="showCal" x-cloak style="position:fixed;inset:0;z-index:1055">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.45)" @click="showCal=false"></div>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem">
            <div class="bg-white rounded-3 shadow-lg p-4" style="width:100%;max-width:450px">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <select class="fw-bold fs-5 border-0 bg-transparent p-0 pe-1" style="outline:none;cursor:pointer" x-model.number="calMonth">
                        <template x-for="(name, i) in calMonthNames" :key="i">
                            <option :value="i" x-text="name"></option>
                        </template>
                    </select>
                    <span class="fw-bold fs-5" x-text="calYear"></span>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm px-3" @click="goToday()">Today</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2" @click="prevMonth()"><i class="bi bi-chevron-left"></i></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2" @click="nextMonth()"><i class="bi bi-chevron-right"></i></button>
                        <button type="button" class="btn-close ms-1" @click="showCal=false"></button>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center">
                    <template x-for="name in calDayNames" :key="name">
                        <div class="text-muted small fw-semibold py-1" x-text="name"></div>
                    </template>
                </div>
                <div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center">
                    <template x-for="(day, idx) in calDays" :key="idx">
                        <div style="display:flex;align-items:center;justify-content:center;padding:3px">
                            <button type="button"
                                    x-show="day !== null && !calIsPast(day)"
                                    class="btn rounded-circle p-0"
                                    style="width:38px;height:38px;font-size:.9rem"
                                    :class="calIsSelected(day)||calIsToday(day) ? 'btn-primary text-white fw-bold' : 'btn-link cal-day'"
                                    @click="selectCalDate(day)"
                                    x-text="day"></button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
