<?php

namespace App\Models\Job;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Builder;

class SystemJobError extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'system_job_errors';
    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'job_id',
        'school_branch_id',
        'code',
        'message',
        'stage',
        'data',
    ];

    public function getTable()
    {
        return 'system_job_errors';
    }

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function qualifyColumn($column)
    {
        return $column;
    }

    public function scopeForJob(object $query, string $jobId)
    {
        return $query->where('job_id', $jobId);
    }
    public function scopeForSchoolBranch(object $query, string $schoolBranchId)
    {
        return $query->where('school_branch_id', $schoolBranchId);
    }
}
