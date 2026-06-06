<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_purchase', 'quota',
        'used_count', 'valid_from', 'valid_until', 'is_active',
    ];

    protected $casts = [
        'value'        => 'integer',
        'min_purchase' => 'integer',
        'quota'        => 'integer',
        'used_count'   => 'integer',
        'valid_from'   => 'date',
        'valid_until'  => 'date',
        'is_active'    => 'boolean',
    ];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_voucher')
            ->withPivot('discount_amount', 'applied_at');
    }
}
