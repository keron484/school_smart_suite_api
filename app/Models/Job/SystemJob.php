<?php

namespace App\Models\Job;

use App\Traits\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemJob extends Model
{
    use GeneratesUuid;

    protected $casts = [
        'payload' => 'json',
        'result' => 'json',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

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
        'progress',
        'started_at'
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
