<?php

namespace App\Services\Job;

use Carbon\Carbon;

class JobBroadCastPolicyService
{
    public function __construct(
        private readonly int $progressThreshold = 5,
        private readonly int $maxSilenceSeconds = 3,
    ) {}

    public function shouldBroadcast(
        int $currentProgress,
        int $lastBroadcastProgress,
        ?Carbon $lastBroadcastAt,
        string $currentStatus,
        ?string $lastBroadcastStatus,
    ): bool {
        if ($lastBroadcastAt === null || $lastBroadcastStatus === null) {
            return true;
        }

        if ($currentStatus !== $lastBroadcastStatus) {
            return true;
        }

        $progressDelta = $currentProgress - $lastBroadcastProgress;
        if ($progressDelta >= $this->progressThreshold) {
            return true;
        }

        if ($lastBroadcastAt->diffInSeconds(Carbon::now()) >= $this->maxSilenceSeconds) {
            return true;
        }

        return false;
    }
}
