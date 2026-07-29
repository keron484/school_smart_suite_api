<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\GetJobRequest;
use App\Services\ApiResponseService;
use App\Services\Job\JobService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    protected JobService $jobService;
    public function __construct(JobService $jobService)
    {
        $this->jobService = $jobService;
    }

    public function getJobs(GetJobRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $jobs = $this->jobService->getJobs($request->validated(), $currentSchool, $this->resolveUser());
        return ApiResponseService::success("Jobs Fetched Successfully", $jobs, null, 200);
    }

    public function getJobDetails(Request $request, string $jobId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $job = $this->jobService->getJobDetails($jobId, $currentSchool);
        return ApiResponseService::success("Job Details Fetched Successfully", $job, null, 200);
    }

    public function deleteJob(Request $request, string $jobId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $this->jobService->deleteJob($jobId, $currentSchool, $this->resolveUser());
        return ApiResponseService::success("Job Deleted Successfully", null, null, 200);
    }

    public function getJobErrors(Request $request, string $jobId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $jobErrors = $this->jobService->getJobErrors($jobId, $currentSchool);
        return ApiResponseService::success("Job Errors Fetched Successfully", $jobErrors, null, 200);
    }

    protected function resolveUser()
    {
        foreach (['student', 'teacher', 'schooladmin'] as $guard) {
            $user = request()->user($guard);
            if ($user !== null) {
                return $user;
            }
        }
        return null;
    }
}
