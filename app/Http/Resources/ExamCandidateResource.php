<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamCandidateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_student_evaluated' => $this->exam?->examScore?->isNotEmpty() ?? false,
            'student_name' => $this->student?->name,
            'level_number' => $this->exam?->schoolYear?->specialty?->level?->level,
            'level_name' => $this->exam?->schoolYear?->specialty?->level?->name,
            'specialty_name' => $this->exam?->schoolYear?->specialty?->specialty_name,
            'semester' => $this->exam?->examType?->semesters?->name,
            'exam_name' => $this->exam?->examType?->exam_name,
            'exam_type' => $this->exam?->examType?->type,
            'academic_year' => $this->exam?->schoolYear?->systemAcademicYear?->name,
        ];
    }
}
