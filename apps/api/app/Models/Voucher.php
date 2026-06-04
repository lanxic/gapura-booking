<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'type', 'discount_percent', 'discount_amount',
        'max_discount', 'min_order', 'max_uses', 'used_count',
        'valid_from', 'valid_until', 'is_active',
    ];

    protected $casts = [
        'discount_percent' => 'integer',
        'discount_amount'  => 'integer',
        'max_discount'     => 'integer',
        'min_order'        => 'integer',
        'max_uses'         => 'integer',
        'used_count'       => 'integer',
        'valid_from'       => 'date',
        'valid_until'      => 'date',
        'is_active'        => 'boolean',
    ];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_voucher')
            ->withPivot('discount_amount', 'applied_at');
    }
}
