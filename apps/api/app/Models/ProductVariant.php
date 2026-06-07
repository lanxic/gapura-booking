<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'label', 'description', 'price_adult', 'price_child',
        'min_qty', 'max_qty', 'is_active', 'is_deleted',
        'adult_min_age', 'adult_max_age', 'child_min_age', 'child_max_age',
    ];

    protected $casts = [
        'price_adult'   => 'integer',
        'price_child'   => 'integer',
        'min_qty'       => 'integer',
        'max_qty'       => 'integer',
        'is_active'     => 'boolean',
        'is_deleted'    => 'boolean',
        'adult_min_age' => 'integer',
        'adult_max_age' => 'integer',
        'child_min_age' => 'integer',
        'child_max_age' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }
}
