<?php

namespace App\Http\Requests\SchoolAdmin;

use Illuminate\Foundation\Http\FormRequest;

class CreateSchoolAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => "required|string",
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|string|max:150',
            'phone' => 'required|nullable|string|max:20',
            'address' => 'required|nullable|string|max:200',
            'gender_id' => 'required|string|exists:genders,id',
        ];
    }
}
