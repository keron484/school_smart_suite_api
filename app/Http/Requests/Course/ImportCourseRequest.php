<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class ImportCourseRequest extends FormRequest
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

            'mapping.standardFields.course_title' => [
                'required',
                'string',
            ],
            'mapping.standardFields.course_code' => [
                'required',
                'string',
            ],
            'mapping.standardFields.description' => [
                'required',
                'string',
            ],
            'mapping.standardFields.course_credit' => [
                'required',
                'string',
            ],
            'mapping.standardFields.specialty' => [
                'required',
                'string',
            ],
            'mapping.standardFields.semester' => [
                'required',
                'string',
            ],
            'mapping.repeatableGroups.course_types.*' => [
                'required',
                'array',
            ],
            'mapping.repeatableGroups.course_types.*.course_type' => [
                'required',
                'string',
            ],
        ];
    }
}
