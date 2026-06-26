<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    protected $fillable = ['group_name', 'key_name', 'value', 'is_encrypted'];

    protected $casts = ['is_encrypted' => 'boolean'];

    private static int $cacheTtl = 600; // 10 menit (PRD Section 4.6.8)

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $cacheKey = "sys_setting:{$group}:{$key}";

        return Cache::remember($cacheKey, self::$cacheTtl, function () use ($group, $key, $default) {
            $row = static::where('group_name', $group)->where('key_name', $key)->first();
            if (! $row) return $default;

            $value = $row->value;
            if ($row->is_encrypted && $value) {
                try { $value = Crypt::decryptString($value); } catch (\Throwable) {}
            }
            return $value;
        });
    }

    public static function set(string $group, string $key, mixed $value, bool $encrypted = false): void
    {
        $stored = ($encrypted && $value) ? Crypt::encryptString((string) $value) : $value;

        static::updateOrCreate(
            ['group_name' => $group, 'key_name' => $key],
            ['value' => $stored, 'is_encrypted' => $encrypted]
        );

        Cache::forget("sys_setting:{$group}:{$key}");
    }

    public static function getGroup(string $group): array
    {
        return static::where('group_name', $group)->get()
            ->mapWithKeys(function ($row) {
                $value = $row->value;
                if ($row->is_encrypted && $value) {
                    try { $value = Crypt::decryptString($value); } catch (\Throwable) {}
                }
                return [$row->key_name => $value];
            })->all();
    }
}
