<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivitySchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'activity_id', 'day_of_week', 'start_time', 'end_time',
        'default_capacity', 'is_active',
    ];

    protected $casts = [
        'day_of_week'      => 'integer',
        'default_capacity' => 'integer',
        'is_active'        => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ActivitySlot::class, 'schedule_id');
    }
}
