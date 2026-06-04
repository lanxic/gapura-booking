<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'availability_slot_id',
        'qty_adult', 'qty_child', 'unit_price_adult', 'unit_price_child',
        'subtotal',
    ];

    protected $casts = [
        'qty_adult'         => 'integer',
        'qty_child'         => 'integer',
        'unit_price_adult'  => 'integer',
        'unit_price_child'  => 'integer',
        'subtotal'          => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function availabilitySlot()
    {
        return $this->belongsTo(AvailabilitySlot::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
