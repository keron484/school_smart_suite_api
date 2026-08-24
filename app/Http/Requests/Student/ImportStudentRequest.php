<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class ImportStudentRequest extends FormRequest
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

            'mapping.standardFields.email' => [
                'required',
                'string',
            ],
            'mapping.standardFields.full_names' => [
                'required',
                'string',
            ],
            'mapping.standardFields.first_name' => [
                'required',
                'string',
            ],
            'mapping.standardFields.last_name' => [
                'required',
                'string',
            ],
            'mapping.standardFields.phone' => [
                'required',
                'string',
            ],
            'mapping.standardFields.gender' => [
                'required',
                'string',
            ],
            'mapping.standardFields.specialty' => [
                'required',
                'string',
            ],
            'mapping.standardFields.level' => [
                'required',
                'string',
            ],
            'mapping.standardFields.batch' => [
                'required',
                'string',
            ],
            'mapping.standardFields.relationship' => [
                'required',
                'string',
            ],
            'mapping.standardFields.student_source' => [
                'required',
                'string',
            ],
            'mapping.standardFields.dob' => [
                'required',
                'string',
            ],
            'mapping.standardFields.guardian' => [
                'required',
                'string',
            ],
            'mapping.standardFields.fee_payment_format' => [
                'nullable',
                'string',
            ]
        ];
    }
}
