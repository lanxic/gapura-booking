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
        'capacity', 'spare_capacity', 'booked_count', 'price_adult', 'price_child', 'status',
    ];

    protected $casts = [
        'date'           => 'date',
        'start_time'     => 'datetime',
        'end_time'       => 'datetime',
        'capacity'       => 'integer',
        'spare_capacity' => 'integer',
        'booked_count'   => 'integer',
        'price_adult'    => 'integer',
        'price_child'    => 'integer',
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

    /** Kapasitas total termasuk spare (hard limit sesungguhnya). */
    public function getTotalCapacityAttribute(): int
    {
        return $this->capacity + $this->spare_capacity;
    }

    /** Sisa pax yang masih bisa dipesan (berdasarkan total_capacity). */
    public function getRemainingCapacityAttribute(): int
    {
        return max(0, $this->total_capacity - $this->booked_count);
    }

    public function getAvailablePaxAttribute(): int
    {
        return $this->remaining_capacity;
    }

    public function getPriceAttribute(): int
    {
        return $this->price_adult ?? 0;
    }

    public function isAvailableFor(int $pax): bool
    {
        return $this->status === 'available' && $this->remaining_capacity >= $pax;
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Sync status berdasarkan booked_count vs capacity.
     * Hanya auto-ubah jika status bukan 'blocked' (manual admin block).
     * Jika penuh → 'full'; jika ada sisa → 'available'.
     */
    public function syncStatus(): void
    {
        if ($this->status === 'blocked') {
            return;
        }

        $target = $this->booked_count >= $this->total_capacity ? 'full' : 'available';

        if ($this->status !== $target) {
            $this->update(['status' => $target]);
        }
    }
}
