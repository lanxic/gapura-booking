<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_number',   // dikirim ke Midtrans sebagai order_id — BERBEDA dari booking_code
        'gateway',
        'snap_token',
        'ref_id',
        'payment_type',
        'amount',
        'status',
        'paid_at',
        'payload',
        'collected_by',
    ];

    protected $casts = [
        'status'  => PaymentStatus::class,
        'amount'  => 'integer',
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
