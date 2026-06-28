<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAddon extends Model
{
    protected $fillable = [
        'product_id', 'name', 'price', 'unit', 'max_qty', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price'     => 'integer',
        'max_qty'   => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
