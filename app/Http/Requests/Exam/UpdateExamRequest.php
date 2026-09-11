<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date',
            'exam_type_id' => 'sometimes|nullable|string|exists:exam_types,id',
            'max_score' => [
                'sometimes',
                'nullable',
                'numeric',
                'regex:/^\d{1,3}(\.\d{1,2})?$/',
                'min:0',
                'max:999.99'
            ],
            'school_year_id' => 'sometimes|nullable|string|exists:school_academic_years,id',
        ];
    }
}
