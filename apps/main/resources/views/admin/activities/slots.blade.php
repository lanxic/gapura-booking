@extends('layouts.admin')

@section('title', 'Slot — ' . $activity->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.activities.index') }}">Aktivitas</a></li>
    <li class="breadcrumb-item active">Slot</li>
@endsection

@section('content')

<div class="row g-4">

    {{-- Generate Slots --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Generate Slot</h6>
                <form method="POST" action="{{ route('admin.activities.generate-slots', $activity->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Hari</label>
                        <div class="d-flex flex-wrap gap-2">
                            @php $days = ['Min','Sen','Sel','Rab','Kam','Jum','Sab']; @endphp
                            @foreach($days as $i => $day)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="{{ $i }}" id="day{{ $i }}">
                                <label class="form-check-label small" for="day{{ $i }}">{{ $day }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jam Mulai</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jam Selesai</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Maks Pax per Slot</label>
                        <input type="number" name="capacity" class="form-control" value="{{ $activity->max_pax }}" min="1" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Harga per Slot (Rp)</label>
                        <input type="number" name="price" class="form-control" value="{{ $activity->base_price }}" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Generate</button>
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
                        <th>Tersedia</th>
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
                        <td>{{ $slot->available_pax }}</td>
                        <td>
                            <span class="badge {{ $slot->status === 'available' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($slot->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada slot.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $slots->links('pagination::bootstrap-5') }}</div>
    </div>

</div>

@endsection
