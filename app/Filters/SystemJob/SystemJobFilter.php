<?php

namespace App\Filters\SystemJob;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
class SystemJobFilter
{
    public function apply(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter => $value) {

            if ($this->shouldSkip($value)) {
                continue;
            }

            $method = Str::camel($filter);

            if (method_exists($this, $method)) {
                $this->{$method}($query, $value);
            }
        }

        return $query;
    }

    protected function shouldSkip(mixed $value): bool
    {
        return $value === null
            || $value === ''
            || $value === [];
    }

    protected function category(Builder $query, mixed $category): void
    {
        if (is_numeric($category)) {
            $query->where('category_id', $category);

            return;
        }

        $query->whereHas('category', function (Builder $builder) use ($category) {
            $builder->where('name', 'LIKE', "%{$category}%");
        });
    }

    protected function status(Builder $query, mixed $status): void
    {
        if (is_array($status)) {
            $query->whereIn('status', $status);

            return;
        }

        $query->where('status', $status);
    }

    protected function stage(Builder $query, mixed $stage): void
    {
        if (is_array($stage)) {
            $query->whereIn('stage', $stage);

            return;
        }

        $query->where('stage', $stage);
    }
}
