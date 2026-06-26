<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAddon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'activity_id', 'name', 'price', 'unit', 'max_qty', 'is_active',
    ];

    protected $casts = [
        'price'     => 'integer',
        'max_qty'   => 'integer',
        'is_active' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
