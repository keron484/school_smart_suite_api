<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdditionalFeeResource extends JsonResource
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
            'amount' => $this->amount ?? null,
            'status' => $this->status ?? null,
            'student_name' => $this->student->name ?? null,
            'username' => $this->student->username ?? null,
            'profile_picture' => $this->student->profile_picture ?? null,
            'specialty_name' => $this->specialty->specialty_name ??  null,
            'level_name' => $this->level->name ?? null,
            'level_number' => $this->level->level ?? null,
            'due_date' => $this->due_date ?? null,
            'category' => $this->feeCategory->title ?? null,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null
        ];
    }
}
