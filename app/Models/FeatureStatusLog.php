<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'system_feature_id',
        'old_status',
        'new_status',
        'reason',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(SystemFeature::class, 'system_feature_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
