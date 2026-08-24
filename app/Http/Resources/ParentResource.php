<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParentResource extends JsonResource
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
            'guardian_name'=> $this->name ?? null,
            'address' => $this->address ?? null,
            'phone' => $this->phone ?? null,
            'total_students' => $this->student->count() ?? 0,
            'contact_method' => $this->preferred_contact_method,
            'language' => $this->preferred_language,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null
        ];
    }
}
