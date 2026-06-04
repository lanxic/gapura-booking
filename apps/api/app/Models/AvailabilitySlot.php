<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailabilitySlot extends Model
{
    protected $fillable = [
        'product_id', 'date', 'time_slot',
        'total_quota', 'booked_qty', 'is_blocked',
    ];

    protected $casts = [
        'date'        => 'date',
        'total_quota' => 'integer',
        'booked_qty'  => 'integer',
        'is_blocked'  => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
