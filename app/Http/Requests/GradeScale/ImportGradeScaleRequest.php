<?php

namespace App\Http\Requests\GradeScale;

use Illuminate\Foundation\Http\FormRequest;

class ImportGradeScaleRequest extends FormRequest
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
            'mapping.standardFields.grade_title' => [
                'nullable',
                'string',
            ],
            'mapping.standardFields.exam_type' => [
                'nullable',
                'string',
            ],
            'mapping.standardFields.grade_max_score' => [
                'nullable',
                'string',
            ],
            'mapping.standardFields.max_score' => [
                'nullable',
                'string',
            ],
            'mapping.standardFields.min_score' => [
                'nullable',
                'string',
            ],
            'mapping.standardFields.grade' => [
                'nullable',
                'string',
            ],
            'mapping.standardFields.resit_result' => [
                'nullable',
                'string',
            ],
            'mapping.standardFields.result' => [
                'nullable',
                'string',
            ],
            'mapping.standardFields.performance' => [
                'nullable',
                'string',
            ]
        ];
    }
}
