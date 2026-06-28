<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'booking_code', 'invoice_id', 'slot_id', 'customer_id',
        'guest_name', 'guest_email', 'guest_phone', 'pax_count',
        'status', 'notes', 'total_amount', 'paid_amount', 'payment_status',
        'qr_code_token', 'qr_code_path', 'confirmed_at',
    ];

    protected $casts = [
        'pax_count'    => 'integer',
        'total_amount' => 'integer',
        'paid_amount'  => 'integer',
        'confirmed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ProductSlot::class, 'slot_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(BookingParticipant::class);
    }
}
