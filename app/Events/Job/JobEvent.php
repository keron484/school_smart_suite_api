<?php

namespace App\Events\Job;

// use App\Models\Schooladmin;
// use App\Models\Schoolbranches;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly mixed $actor,
        public readonly mixed $currentSchool,
        public readonly mixed $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                "schoolBranch.{$this->currentSchool->id}" .
                ".schoolAdmin.{$this->actor->id}" .
                ".jobs"
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'job.update';
    }

    public function broadcastWith(): array
    {
        return [
            'payload'   => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
