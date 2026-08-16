<?php

namespace App\Http\Resources\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemJobGroupResource extends JsonResource
{
    protected string $completedStatus = 'completed';
    protected string $failedStatus = 'failed';
    protected string $inProgressStatus = 'processing';
    protected string $completedWithIssuesStatus = 'completed_with_issues';
    protected string $queued = 'queued';
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'group' => $this->resolveGroup($this['group']),
            'jobs' => SystemJobResource::collection($this['jobs']),
        ];
    }

    public function resolveGroup(string $group): mixed
    {
        $map = [
            $this->completedWithIssuesStatus => [
                "name" => 'Completed with Issues',
                "key" => 'completed_with_issues',
            ],
            $this->completedStatus => [
                "name" => 'Completed',
                "key" => 'completed',
            ],
            $this->failedStatus => [
                "name" => 'Failed',
                "key" => 'failed',
            ],
            $this->inProgressStatus => [
                "name" => 'Processing',
                "key" => 'processing',
            ],
            $this->queued => [
                "name" => 'Queued',
                "key" => 'queued',
            ],
        ];
        return $map[$group] ?? $group;
    }
}
