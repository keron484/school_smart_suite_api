<?php

namespace App\Services\Helpers\Job;
use App\Models\Job\SystemJob;
use Carbon\Carbon;
class JobHelperService
{
    public function updateJobProgress(SystemJob $systemJob, string $status, string $stage, int $progress): void
    {
        $systemJob->update([
            'status'     => $status,
            'stage'      => $stage,
            'progress'   => $progress,
            'updated_at' => Carbon::now(),
        ]);
    }

    public function failJob(SystemJob $systemJob, string $message, int|string $code): void
    {
        $systemJob->update([
            'status'        => 'failed',
            'stage'         => 'Failed',
            'progress'      => 0,
            'error_code'    => (string) $code,
            'error_message' => $message,
            'updated_at'    => Carbon::now(),
            'finished_at'   => Carbon::now(),
        ]);

        $systemJob->systemJobEvent()->create([
            'event_type' => 'error',
            'message'    => $message,
        ]);
    }

}
