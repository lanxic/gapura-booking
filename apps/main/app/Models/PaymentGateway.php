<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'is_active', 'merchant_id', 'server_key', 'client_key',
        'environment', 'config', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config'    => 'array',
    ];

    protected $hidden = ['server_key', 'client_key', 'merchant_id'];

    public function setServerKeyAttribute(?string $value): void
    {
        $this->attributes['server_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getServerKeyAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setClientKeyAttribute(?string $value): void
    {
        $this->attributes['client_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getClientKeyAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setMerchantIdAttribute(?string $value): void
    {
        $this->attributes['merchant_id'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getMerchantIdAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public static function activeGateway(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
