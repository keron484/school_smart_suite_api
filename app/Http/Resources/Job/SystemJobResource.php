<?php

namespace App\Http\Resources\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "title" => $this->getTitle($this->type),
            "module" => $this->getModule($this->type),
            "type" => $this->getType($this->type),
            "status" => $this->status,
            "stage" => $this->stage,
            "total_items" => $this->total_items ?? 0,
            "processed_items" => $this->processed_items ?? 0,
            "successful_items" => $this->successful_items ?? 0,
            "failed_items" => $this->failed_items ?? 0,
            "remaining_items" => $this->getRemainingItems(),
            "progress_percentage" => $this->getProgressPercentage(),
            "started_at" => $this->started_at,
            "finished_at" => $this->finished_at,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "category_id" => $this->category?->id,
            "category_name" => $this->category?->name,
        ];
    }

    private function getTitle(string $type): string
    {
        $map = [
            "teacher_import" => "Teacher Import",
            "student_import" => "Student Import",
            "assign_teacher_specialties" => "Teacher Specialties Assignment",
        ];

        return $map[$type] ?? $type;
    }

    private function getModule(string $type): string
    {
        $map = [
            "teacher_import" => "Teacher",
            "student_import" => "Student",
            "assign_teacher_specialties" => "Teacher Specialties",
        ];

        return $map[$type] ?? $type;
    }

    private function getType(string $type): string
    {
        $map = [
            "teacher_import" => "Import",
            "student_import" => "Import",
            "assign_teacher_specialties" => "Bulk Assignment",
        ];

        return $map[$type] ?? $type;
    }

    private function getProgressPercentage(): int
    {
        if (($this->total_items ?? 0) <= 0) {
            return 0;
        }

        return (int) round(
            (($this->processed_items ?? 0) / $this->total_items) * 100
        );
    }

    private function getRemainingItems(): int
    {
        return max(
            0,
            ($this->total_items ?? 0) - ($this->processed_items ?? 0)
        );
    }

}
