<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    /** Return all key→value pairs for a group as an associative array. */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    /** Upsert multiple key→value pairs for a group. */
    public static function setGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            static::updateOrCreate(
                ['group' => $group, 'key'   => $key],
                ['value' => $value]
            );
        }
    }

    /** Cast raw string values to appropriate PHP types. */
    public static function cast(mixed $value): mixed
    {
        if ($value === 'true')  return true;
        if ($value === 'false') return false;
        if ($value === 'null' || $value === null) return null;

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /** Get group as properly cast key→value map. */
    public static function getCastGroup(string $group): array
    {
        return array_map(
            fn($v) => static::cast($v),
            static::getGroup($group)
        );
    }

    /** Store value — scalars as string, arrays/objects as JSON. */
    public static function encodeValue(mixed $value): string
    {
        if (is_bool($value))  return $value ? 'true' : 'false';
        if (is_null($value))  return 'null';
        if (is_array($value) || is_object($value)) return json_encode($value);
        return (string) $value;
    }

    /** Upsert with proper encoding. */
    public static function setGroupEncoded(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            static::updateOrCreate(
                ['group' => $group, 'key'   => $key],
                ['value' => static::encodeValue($value)]
            );
        }
    }
}
