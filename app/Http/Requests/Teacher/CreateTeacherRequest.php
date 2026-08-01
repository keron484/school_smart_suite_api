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
            'name' => 'required|String',
            'first_name' => 'required|string',
            "last_name" => 'required|string',
            'email' => 'required|email|string',
            'phone' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string',
            'gender_id' => 'required|string|exists:genders,id',
            'level_ids' => 'required|array|min:1',
            'level_ids.*' => 'required|uuid|exists:levels,id',
            'qualifications' => "required|array|min:1",
            'qualifications.*.qualification_id' => 'required|uuid|exists:qualifications,id',
            'qualifications.*.field_of_study' => 'required|string'
        ];
    }
}
