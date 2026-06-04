<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
        'payment_type', 'dp_percent', 'dp_amount', 'remaining_amount',
        'status', 'subtotal', 'discount', 'total', 'notes', 'expires_at',
    ];

    protected $casts = [
        'status'           => OrderStatus::class,
        'dp_percent'       => 'integer',
        'dp_amount'        => 'integer',
        'remaining_amount' => 'integer',
        'subtotal'         => 'integer',
        'discount'         => 'integer',
        'total'            => 'integer',
        'expires_at'       => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets()
    {
        return $this->hasManyThrough(Ticket::class, OrderItem::class);
    }

    public function addons()
    {
        return $this->belongsToMany(Addon::class, 'order_addon_items')
            ->withPivot('qty', 'unit_price');
    }

    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'order_voucher')
            ->withPivot('discount_amount', 'applied_at');
    }
}
