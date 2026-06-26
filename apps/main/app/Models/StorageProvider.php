<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class StorageProvider extends Model
{
    protected $fillable = [
        'name', 'is_active', 'config', 'max_file_size', 'allowed_formats', 'folder_prefix',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'max_file_size'   => 'integer',
        'allowed_formats' => 'array',
    ];

    public function setConfigAttribute(?array $value): void
    {
        $this->attributes['config'] = $value ? Crypt::encryptString(json_encode($value)) : null;
    }

    public function getConfigAttribute(?string $value): ?array
    {
        if (! $value) return null;
        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function activeProvider(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
