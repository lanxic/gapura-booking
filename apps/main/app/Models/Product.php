<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'type', 'category', 'description',
        'duration_minutes', 'min_pax', 'max_pax', 'level', 'min_age',
        'price_adult', 'price_child', 'status', 'is_featured', 'meta',
    ];

    protected $casts = [
        'meta'             => 'array',
        'is_featured'      => 'boolean',
        'duration_minutes' => 'integer',
        'min_pax'          => 'integer',
        'max_pax'          => 'integer',
        'min_age'          => 'integer',
        'price_adult'      => 'integer',
        'price_child'      => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ProductSchedule::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ProductSlot::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ProductAddon::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getCloudinaryThumbnailUrlAttribute(): ?string
    {
        $media = $this->relationLoaded('media') ? $this->media : collect();
        return $media->where('is_primary', true)->first()?->url
            ?? $media->first()?->url;
    }

    public function getShortDescriptionAttribute(): string
    {
        return Str::limit(strip_tags($this->description ?? ''), 120);
    }
}
