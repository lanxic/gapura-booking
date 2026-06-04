<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description',
        'location', 'opening_hours', 'meeting_point',
        'instant_confirmation',
        'highlights', 'usage_instructions', 'cancellation_policy', 'terms_conditions',
        'cloudinary_image_id', 'cloudinary_image_url',
        'cloudinary_thumbnail_id', 'cloudinary_thumbnail_url',
        'cloudinary_gallery_ids', 'cloudinary_gallery_urls',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'instant_confirmation'    => 'boolean',
        'highlights'              => 'array',
        'cloudinary_gallery_ids'  => 'array',
        'cloudinary_gallery_urls' => 'array',
    ];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function addons()
    {
        return $this->belongsToMany(Addon::class, 'product_addon')
            ->withPivot('is_active', 'sort_order')
            ->withTimestamps();
    }

    public function availabilitySlots()
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    public function pricingRules()
    {
        return $this->hasMany(PricingRule::class);
    }
}
