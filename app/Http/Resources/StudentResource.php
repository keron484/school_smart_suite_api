<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'student_name' => $this->name ?? null,
            'student_first_name' => $this->first_name ?? null,
            'student_last_name' => $this->last_name ?? null,
            'student_DOB' => $this->DOB ?? null,
            'student_gender' => $this->gender->name ?? null,
            'student_phone_one' => $this->phone_one ?? null,
            'student_phone_two' => $this->phone_two ?? null,
            'student_religion' => $this->religion ?? null,
            'student_email' => $this->email ?? null,
            'student_profile_picture' => $this->profile_picture ?? null,
            'guardian_name' => $this->guardian->name ?? null,
            'specialty_name' => $this->specialty->specialty_name ?? null,
            'level_name' => $this->level->name ?? null,
            'level_number' => $this->level->level ?? null,
            'status' => $this->status ?? null,
            'batch_title' => $this->studentBatch->name ?? null
        ];
    }
}
