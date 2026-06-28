<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description', 'image', 'start_date', 'end_date',
        'discount_type', 'discount_value', 'badge', 'is_active',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'discount_value' => 'integer',
        'is_active'      => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'offer_products');
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class);
    }

    public function scopeActive($query)
    {
        $today = now()->toDateString();
        return $query->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    public function calculateDiscount(int $amount): int
    {
        if ($this->discount_type === 'percent') {
            return (int) round($amount * $this->discount_value / 100);
        }
        return min($this->discount_value, $amount);
    }
}
