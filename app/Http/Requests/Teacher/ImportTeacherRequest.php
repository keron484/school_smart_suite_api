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
            'map' => [
                'required',
                'array',
            ],
            'map.email' => [
                'required',
                'string',
                'max:255',
            ],
            'map.full_names' => [
                'required',
                'string',
                'max:255',
            ],
            'map.first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'map.last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'map.phone' => [
                'required',
                'string',
                'max:255',
            ],
            'map.address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'map.gender' => [
                'nullable',
                'string',
                'max:255',
            ]
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
