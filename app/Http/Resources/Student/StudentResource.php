<?php

namespace App\Http\Resources\Student;

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
            'name' => $this->name ?? null,
            'username' => $this->username ?? null,
            'first_name' => $this->first_name ?? null,
            'last_name' => $this->last_name ?? null,
            'dob' => $this->DOB ?? null,
            'gender' => $this->gender->name ?? null,
            'phone' => $this->phone ?? null,
            'email' => $this->email ?? null,
            'status' => $this->status ?? null,
            'profile_picture' => $this->profile_picture ?? null,
            'guardian_name' => $this->guardian->name ?? null,
            'relationship' => $this->relationship->name ?? null,
            'specialty_name' => $this->specialty->specialty_name ?? null,
            'department' => $this->specialty->department->department_name ?? null,
            'level_name' => $this->specialty->level->name ?? null,
            'level_number' => $this->specialty->level->level ?? null,
            'batch_title' => $this->studentBatch->name ?? null,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null
        ];
    }
}
