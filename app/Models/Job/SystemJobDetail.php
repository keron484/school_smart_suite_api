<?php

namespace App\Models\Job;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Builder;

class SystemJobDetail extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'system_job_details';
    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'job_id',
        'school_branch_id',
        'input',
        'summary',
        'result',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'input' => 'array',
            'summary' => 'array',
            'result' => 'array',
            'metadata' => 'array',
        ];
    }


    public function getTable()
    {
        return 'system_job_details';
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
