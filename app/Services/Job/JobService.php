<?php

namespace App\Services\Job;

use App\Groupers\SystemJob\SystemJobGrouper;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobError;
use App\Models\Job\SystemJobEvent;
use App\Models\Job\SystemJobDetail;
use App\Exceptions\AppException;
use App\Filters\SystemJob\SystemJobFilter;
use App\Http\Resources\Job\SystemJobGroupResource;
use App\Http\Resources\Job\SystemJobResource;
use Illuminate\Support\Facades\DB;

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

    public function getJobs(array $filters, object $currentSchool, object $authUser)
    {
        $query = SystemJob::query()
            ->where('context_id', $currentSchool->id)
            ->where('initiated_by_id', $authUser->id)
            ->where('initiated_by_type', get_class($authUser))
            ->with('category');

        app(SystemJobFilter::class)->apply($query, $filters);
        if ($filters['group_by']) {
            return SystemJobGroupResource::collection(
                app(SystemJobGrouper::class)->group(
                    $query,
                    $filters['group_by']
                )
            );
        }
        return SystemJobResource::collection(app(SystemJobGrouper::class)->group(
            $query,
            $filters['group_by'] ?? null
        ));
    }

    public function getJobErrors(string $jobId, object $currentSchool)
    {
        return SystemJobError::where("job_id", $jobId)->where("school_branch_id", $currentSchool->id)->get();
    }

    public function deleteJob(string $jobId, object $currentSchool, object $authUser)
    {
        return DB::transaction(function () use ($jobId, $currentSchool, $authUser) {

            $job = SystemJob::where("school_branch_id", $currentSchool->id)
                ->where("initiated_by_id", $authUser->id)
                ->where("id", $jobId)
                ->first();

            if (!$job) {
                throw new AppException(
                    "Job not found or you do not have permission to delete it.",
                    404,
                    "Job Not Found",
                    "The job you are trying to delete does not exist or does not belong to you.",
                    '/jobs'
                );
            }

            // Delete all related job events
            SystemJobEvent::where("school_branch_id", $currentSchool->id)
                ->where("job_id", $jobId)
                ->delete();

            // Delete related job details
            SystemJobDetail::where("school_branch_id", $currentSchool->id)
                ->where("job_id", $jobId)
                ->delete();

            // Delete the job itself
            $job->delete();

            return $job;
        });
    }
}
