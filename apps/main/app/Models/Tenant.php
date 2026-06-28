<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'domain', 'logo_url', 'invoice_prefix', 'is_active', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
