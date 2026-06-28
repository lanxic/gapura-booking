<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attempt_code', 'invoice_id', 'gateway', 'gateway_tx_id',
        'payment_method', 'gateway_env', 'amount', 'gateway_fee',
        'status', 'raw_request', 'raw_response', 'attempted_at', 'settled_at',
    ];

    protected $casts = [
        'amount'       => 'integer',
        'gateway_fee'  => 'integer',
        'raw_request'  => 'array',
        'raw_response' => 'array',
        'attempted_at' => 'datetime',
        'settled_at'   => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
