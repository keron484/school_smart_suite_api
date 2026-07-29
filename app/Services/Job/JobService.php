<?php

namespace App\Services\Job;

use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobError;
use App\Models\Job\SystemJobEvent;
use App\Models\Job\SystemJobDetail;

class JobService
{
    public function getJobDetails(string $jobId, object $currentSchool)
    {
        $systemJob = SystemJob::where("school_branch_id", $currentSchool->id)
            ->where("id", $jobId)
            ->with(['initiatedBy', 'category'])
            ->first();
        return $systemJob;
    }

    public function getJobs(array $filter, object $currentSchool, object $authUser)
    {
        $filterCategory = $filter['category'];
        $systemJobs = SystemJob::where("school_branch_id", $currentSchool->id)
            ->where("initiated_by_id", $authUser->id)
            ->with(['category', function ($query) use ($filterCategory) {
                $query->where("name", $filterCategory);
            }]);
        return $systemJobs;
    }

    public function getJobErrors(string $jobId, object $currentSchool)
    {
        return SystemJobError::where("job_id", $jobId)->where("school_branch_id", $currentSchool->id)->get();
    }

    public function deleteJob(string $jobId, object $currentSchool, object $authUser)
    {
        $job = SystemJob::where("school_branch_id", $currentSchool->id)
            ->where("initiated_by_id", $authUser->id)
            ->where("id", $jobId)->first();

        $jobEvents = SystemJobEvent::where("school_branch_id", $currentSchool->id)
            ->where("job_id", $jobId)
            ->get();
        $jobDetails = SystemJobDetail::where("school_branch_id", $currentSchool->id)
            ->where("job_id", $jobId)
            ->first();
    }
}
