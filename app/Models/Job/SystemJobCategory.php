<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemJobCategory extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'status'
    ];

    public $incrementing = false;
    public $table = 'system_job_categories';
    public $keyType = 'string';

    public function systemJob(): HasMany
    {
        return $this->hasMany(SystemJob::class);
    }
}
