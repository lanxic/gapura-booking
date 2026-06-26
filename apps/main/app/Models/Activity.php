<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'category', 'description', 'duration_minutes',
        'min_pax', 'max_pax', 'level', 'min_age', 'base_price', 'status', 'is_featured', 'meta',
    ];

    protected $casts = [
        'meta'             => 'array',
        'is_featured'      => 'boolean',
        'duration_minutes' => 'integer',
        'min_pax'          => 'integer',
        'max_pax'          => 'integer',
        'min_age'          => 'integer',
        'base_price'       => 'integer',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(ActivitySchedule::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ActivitySlot::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ActivityAddon::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ActivityMedia::class)->orderBy('sort_order');
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
