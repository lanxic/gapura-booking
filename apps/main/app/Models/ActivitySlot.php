<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivitySlot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'activity_id', 'schedule_id', 'date', 'start_time', 'end_time',
        'capacity', 'booked_count', 'price', 'status',
    ];

    protected $casts = [
        'date'         => 'date',
        'start_time'   => 'datetime',
        'end_time'     => 'datetime',
        'capacity'     => 'integer',
        'booked_count' => 'integer',
        'price'        => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ActivitySchedule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'slot_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'checkout_slot_id');
    }

    public function getRemainingCapacityAttribute(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }

    public function getAvailablePaxAttribute(): int
    {
        return $this->remaining_capacity;
    }

    public function isAvailableFor(int $pax): bool
    {
        return $this->status === 'available' && $this->remaining_capacity >= $pax;
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}
