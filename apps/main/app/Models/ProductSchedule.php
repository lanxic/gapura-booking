<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSchedule extends Model
{
    protected $fillable = [
        'product_id', 'day_of_week', 'start_time', 'end_time', 'default_capacity', 'is_active',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'day_of_week'      => 'integer',
        'default_capacity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ProductSlot::class, 'schedule_id');
    }
}
