<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'label', 'percentage', 'min_amount', 'deadline_hours', 'is_active',
    ];

    protected $casts = [
        'percentage'     => 'integer',
        'min_amount'     => 'integer',
        'deadline_hours' => 'integer',
        'is_active'      => 'boolean',
    ];

    public static function active(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->get();
    }

    // FULL selalu aktif — tidak bisa dinonaktifkan
    public static function availableForAmount(int $totalAmount): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->where(fn ($q) => $q->where('code', 'FULL')->orWhere('min_amount', '<=', $totalAmount))
            ->get();
    }
}
