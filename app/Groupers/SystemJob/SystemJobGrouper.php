<?php

namespace App\Groupers\SystemJob;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SystemJobGrouper
{
    /**
     * Group the jobs.
     *
     * Accepts either a Query Builder or a Collection.
     * If a Builder is passed, it will execute ->get() internally.
     *
     * @param  Builder|Collection|array  $jobs
     * @param  string|null               $groupBy
     * @return array
     */
    public function group(Builder|Collection|array $jobs, ?string $groupBy = null): array
    {
        if ($jobs instanceof Builder) {
            $jobs = $jobs->get();
        }

        $jobs = collect($jobs);

        if ($groupBy === null || $groupBy === '') {
            return $jobs->values()->all();
        }

        return match (strtolower($groupBy)) {
            'status'   => $this->groupByStatus($jobs),
            'stage'    => $this->groupByStage($jobs),
            'category' => $this->groupByCategory($jobs),
            default    => $jobs->values()->all(),
        };
    }

    protected function groupByCategory(Collection $jobs): array
    {
        return $jobs
            ->groupBy(function ($job) {
                return $job->category?->name
                    ?? $job->category?->title
                    ?? (string) ($job->category_id ?? 'Uncategorized');
            })
            ->map(function (Collection $group, string $categoryName) {
                return [
                    'group' => $categoryName,
                    'jobs'     => $group->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function groupByStatus(Collection $jobs): array
    {
        return $jobs
            ->groupBy(fn ($job) => $job->status ?? 'unknown')
            ->map(function (Collection $group, string $status) {
                return [
                    'group' => $status,
                    'jobs'   => $group->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function groupByStage(Collection $jobs): array
    {
        return $jobs
            ->groupBy(fn ($job) => $job->stage ?? 'unknown')
            ->map(function (Collection $group, string $stage) {
                return [
                    'group' => $stage,
                    'jobs'  => $group->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
