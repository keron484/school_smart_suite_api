<?php

namespace App\Http\Requests\Specialty;

use Illuminate\Foundation\Http\FormRequest;

class ImportSpecialtyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:10240',
            ],
            'mapping' => [
                'required',
                'array',
            ],

            'mapping.standardFields' => [
                'required',
                'array',
            ],

            'mapping.standardFields.specialty_name' => [
                'required',
                'string',
            ],
            'mapping.standardFields.department_name' => [
                'required',
                'string',
            ],
            'mapping.standardFields.level' => [
                'required',
                'string',
            ],
            'mapping.standardFields.registration_fee' => [
                'required',
                'string',
            ],
            'mapping.standardFields.school_fee' => [
                'required',
                'string',
            ],
            'mapping.standardFields.description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
