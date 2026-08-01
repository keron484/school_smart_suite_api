<?php

namespace App\Models\Job;

use App\Traits\GeneratesUuid;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemJob extends Model
{
    use HasUuids;

    protected $fillable = [
        'type',
        'context_type',
        'context_id',
        'initiated_by_type',
        'initiated_by_id',
        'category_id',
        'queue',
        'status',
        'stage',
        'total_items',
        'processed_items',
        'successful_items',
        'failed_items',
        'started_at',
        'finished_at'
    ];

    protected $casts = [
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'successful_items' => 'integer',
        'failed_items' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public $incrementing = false;
    public $table = "system_jobs";
    public  $keyType = 'string';

    public function category(): BelongsTo
    {
        return $this->belongsTo(SystemJobCategory::class, 'category_id');
    }

    public function initiatedBy()
    {
        return $this->morphTo();
    }
    public function systemJobEvent(): HasMany
    {
        return $this->hasMany(SystemJobEvent::class);
    }
}
