<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TuitionFeeResource extends JsonResource
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
            'amount_paid' => $this->amount_paid ?? null,
            'amount_left' => $this->amount_left ?? null,
            'tution_fee_total' => $this->tution_fee_total ?? null,
            'status' => $this->status ?? null,
            'name' => $this->student->name ?? null,
            'username' => $this->student->username ?? null,
            'first_name' => $this->student->first_name ?? null,
            'last_name' => $this->student->last_name ?? null,
            'profile_picture' => $this->student->profile_picture ?? null,
            'specialty_name' => $this->student->specialty->specialty_name ?? null,
            'department' => $this->student->specialty->department->department_name ?? null,
            'level_name' => $this->student->specialty->level->name ?? null,
            'level_number' => $this->student->specialty->level->level ?? null,
        ];
    }
}
