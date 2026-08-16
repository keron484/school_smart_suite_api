<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecialtyResource extends JsonResource
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
            'specialty_name' => $this->specialty_name,
            'registration_fee' =>  $this->registration_fee,
            'tuition_fee' =>  $this->school_fee,
            'total' => $this->school_fee + $this->registration_fee,
            'level_name' => $this->level->name ?? null,
            'level_id' => $this->level->id,
            'level_number' => $this->level->level ?? null,
            'department' => $this->department->department_name,
            'status' => $this->status,
            'description' => $this->description,
            'num_assigned_hall' => $this->num_assigned_hall,
            'hall_assignment_status' => $this->hall_assignment_status,
            'updated_at' => $this->updated_at->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
