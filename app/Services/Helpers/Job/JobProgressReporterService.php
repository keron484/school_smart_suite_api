<?php

namespace App\Services\Helpers\Job;

use App\Events\Job\JobEvent;
use App\Models\Job\SystemJob;
use App\Services\Job\JobBroadCastPolicyService;
use App\Services\Helpers\Job\JobHelperService;
use Carbon\Carbon;

class JobProgressReporterService
{
    private int $lastBroadcastProgress = 0;
    private ?string $lastBroadcastStatus = null;
    private ?Carbon $lastBroadcastAt = null;

    public function __construct(
        private readonly mixed $authAdmin,
        private readonly mixed $currentSchool,
    ) {}

    public function report(
        SystemJob $systemJob,
        int $processed,
        int $total,
        string $message,
        string $status = 'processing',
        int $minProgress = 5,
        int $maxProgress = 99,
        float $scale = 0.95
    ): void {
        $jobHelperService = app(JobHelperService::class);
        $progress = $total > 0
            ? (int) min($maxProgress, floor(($processed / $total) * ($maxProgress - $minProgress + 1) * $scale / 0.95) + $minProgress)
            : $minProgress;

        $progress = $total > 0
            ? (int) min(99, floor(($processed / $total) * 95) + 5)
            : 5;

        $jobHelperService->updateJobProgress($systemJob, $status, $message, $progress);
        $this->maybeBroadcast($systemJob, $progress, $status);
    }

    public function maybeBroadcast(
        SystemJob $systemJob,
        int $progress,
        string $status
    ): void {
        $policy = app(JobBroadCastPolicyService::class);
        if (!$policy->shouldBroadcast(
            $progress,
            $this->lastBroadcastProgress,
            $this->lastBroadcastAt,
            $status,
            $this->lastBroadcastStatus
        )) {
            return;
        }

        event(new JobEvent(
            $this->authAdmin,
            $this->currentSchool,
            [
                'job_id'   => $systemJob->id,
                'status'   => $status,
                'stage'    => $systemJob->stage,
                'progress' => $progress,
                'result'   => $systemJob->result ?? null,
            ]
        ));

        $this->lastBroadcastProgress = $progress;
        $this->lastBroadcastStatus   = $status;
        $this->lastBroadcastAt       = now();
    }

    public function reset(): void
    {
        $this->lastBroadcastProgress = 0;
        $this->lastBroadcastStatus   = null;
        $this->lastBroadcastAt       = null;
    }
}
