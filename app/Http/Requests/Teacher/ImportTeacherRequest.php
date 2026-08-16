<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class ImportTeacherRequest extends FormRequest
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
            'mapping.repeatableGroups.qualifications' => [
                'required',
                'array',
                'min:1',
                'max:5',
            ],

            'mapping.repeatableGroups.allowed_levels' => [
                'required',
                'array',
                'min:1',
                'max:5',
            ],

            /*
             * Qualification instances
             */
            'mapping.repeatableGroups.qualifications.*' => [
                'required',
                'array',
            ],

            'mapping.repeatableGroups.qualifications.*.qualification' => [
                'required',
                'string',
            ],

            'mapping.repeatableGroups.qualifications.*.field_of_study' => [
                'required',
                'string',
            ],

            'mapping.repeatableGroups.qualifications.*.institution' => [
                'required',
                'string',
            ],

            'mapping.repeatableGroups.qualifications.*.year' => [
                'required',
                'string',
            ],

            /*
             * Allowed level instances
             */
            'mapping.repeatableGroups.allowed_levels.*' => [
                'required',
                'array',
            ],

            'mapping.repeatableGroups.allowed_levels.*.allowed_level' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a file.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'The file must be a CSV, XLSX, or XLS file.',
            'file.max' => 'The file size must not exceed 10MB.',
            'map.required' => 'Column mapping is required.',
            'map.array' => 'Column mapping must be a valid object.',
        ];
    }
}
