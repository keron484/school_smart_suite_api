<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class CreateTeacherRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:50',
            'first_name' => 'required|string|max:50',
            "last_name" => 'required|string|max:50',
            'email' => 'required|email|string|max:100',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:200',
            'gender_id' => 'required|string|exists:genders,id',
            'allowed_level_ids' => 'required|array|min:1',
            'allowed_level_ids.*' => 'required|uuid|exists:levels,id',
            'qualifications' => "required|array|min:1",
            'qualifications.*.qualification_id' => 'required|uuid|exists:qualifications,id',
            'qualifications.*.field_of_study' => 'required|string|max:200',
            'qualifications.*.institution' => 'required|string|max:150',
            'qualifications.*.year' => 'required|string|max:50'
        ];
    }
}
