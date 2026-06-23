<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCode extends Model
{
    protected $fillable = [
        'code', 'offer_id', 'discount_type', 'discount_value',
        'min_amount', 'max_uses', 'used_count', 'is_single_use', 'expired_at',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'min_amount'     => 'integer',
        'max_uses'       => 'integer',
        'used_count'     => 'integer',
        'is_single_use'  => 'boolean',
        'expired_at'     => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function isValid(int $amount): bool
    {
        return $this->used_count < $this->max_uses
            && $this->expired_at->isFuture()
            && $amount >= $this->min_amount;
    }

    public function calculateDiscount(int $amount): int
    {
        if ($this->discount_type === 'percent') {
            return (int) round($amount * $this->discount_value / 100);
        }
        return min($this->discount_value, $amount);
    }
}
