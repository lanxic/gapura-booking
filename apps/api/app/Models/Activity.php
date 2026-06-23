<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'category', 'description', 'duration_minutes',
        'min_pax', 'max_pax', 'level', 'min_age', 'base_price', 'status', 'meta',
    ];

    protected $casts = [
        'meta'             => 'array',
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
