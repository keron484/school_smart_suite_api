<?php

namespace App\Http\Requests\SchoolAdmin;

use Illuminate\Foundation\Http\FormRequest;

class ImportSchoolAdminRequest extends FormRequest
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

            'mapping.repeatableGroups' => [
                'required',
                'array',
            ],
            'mapping.standardFields.email' => [
                'nullable',
                'string',
            ],

            'mapping.standardFields.full_names' => [
                'nullable',
                'string',
            ],

            'mapping.standardFields.first_name' => [
                'nullable',
                'string',
            ],

            'mapping.standardFields.last_name' => [
                'nullable',
                'string',
            ],

            'mapping.standardFields.phone' => [
                'nullable',
                'string',
            ],

            'mapping.standardFields.gender' => [
                'nullable',
                'string',
            ],

            'mapping.standardFields.address' => [
                'nullable',
                'string',
            ],
        ];
    }
}
