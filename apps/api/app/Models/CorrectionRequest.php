<?php

namespace App\Models;

use App\Enums\CorrectionStatus;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'requested_by', 'target_type', 'target_id', 'reason',
        'old_value', 'requested_value', 'status',
        'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'status'          => CorrectionStatus::class,
        'old_value'       => 'array',
        'requested_value' => 'array',
        'reviewed_at'     => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
