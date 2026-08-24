<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationFeeResource extends JsonResource
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
            'status' => $this->status ?? null,
            'amount' => $this->amount ?? null,
            'title' => $this->title ?? null,
            'student_name' => $this->student->name ?? null,
            'level_name' => $this->level->name ?? null,
            'level_number' => $this->level->level ?? null,
            'specialty_name' => $this->specialty->specialty_name ?? null,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null
        ];
    }
}
