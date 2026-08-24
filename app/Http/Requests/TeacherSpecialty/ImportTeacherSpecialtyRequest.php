<?php

namespace App\Http\Requests\TeacherSpecialty;

use Illuminate\Foundation\Http\FormRequest;

class ImportTeacherSpecialtyRequest extends FormRequest
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

            'mapping.standardFields.specialty' => [
                'required',
                'string',
            ],
            'mapping.standardFields.level' => [
                'required',
                'string',
            ],
            'mapping.standardFields.teacher' => [
                'required',
                'string',
            ]
        ];
    }
}
