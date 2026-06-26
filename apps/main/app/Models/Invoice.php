<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_code', 'customer_id', 'guest_name', 'guest_email', 'guest_phone',
        'checkout_slot_id', 'pax_count', 'items', 'subtotal', 'discount_amount',
        'promo_code_id', 'total_amount', 'payment_plan', 'due_now', 'due_later',
        'status', 'pdf_path', 'due_at', 'paid_at', 'gateway', 'gateway_order_id',
    ];

    protected $casts = [
        'items'           => 'array',
        'subtotal'        => 'integer',
        'discount_amount' => 'integer',
        'total_amount'    => 'integer',
        'due_now'         => 'integer',
        'due_later'       => 'integer',
        'pax_count'       => 'integer',
        'due_at'          => 'datetime',
        'paid_at'         => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ActivitySlot::class, 'checkout_slot_id');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function isExpired(): bool
    {
        return $this->due_at->isPast() && $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function canRetry(): bool
    {
        return in_array($this->status, ['pending', 'failed'])
            && ! $this->isExpired()
            && $this->paymentAttempts()->count() < 3;
    }
}
