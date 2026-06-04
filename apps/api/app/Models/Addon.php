<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $fillable = [
        'name', 'description', 'price', 'max_qty', 'is_active',
    ];

    protected $casts = [
        'price'     => 'integer',
        'max_qty'   => 'integer',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_addon')
            ->withPivot('is_active', 'sort_order')
            ->withTimestamps();
    }
}
