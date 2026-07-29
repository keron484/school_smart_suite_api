<?php

namespace App\Http\Requests\TeacherSpecialty;

use Illuminate\Foundation\Http\FormRequest;

class RemoveTeacherRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'specialty_id' => 'required|uuid|exists:specialties,id',
            'teacher_ids' => 'required|array|min:1',
            'teacher_ids.*' => 'required|uuid|exists:teachers,id'
        ];
    }
}
