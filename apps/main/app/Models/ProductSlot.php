<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSlot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'product_id', 'schedule_id', 'date', 'start_time', 'end_time',
        'capacity', 'booked_count', 'price_adult', 'price_child', 'status',
    ];

    protected $casts = [
        'date'         => 'date',
        'start_time'   => 'datetime',
        'end_time'     => 'datetime',
        'capacity'     => 'integer',
        'booked_count' => 'integer',
        'price_adult'  => 'integer',
        'price_child'  => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ProductSchedule::class);
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
