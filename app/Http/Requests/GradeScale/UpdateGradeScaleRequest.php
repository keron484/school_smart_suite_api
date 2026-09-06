<?php

namespace App\Http\Requests\GradeScale;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeScaleRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grades' => 'required|array|min:1',
            'grades.*.letter_grade_id' => 'sometimes|nullable|string|exists:letter_grades,id',
            'grades.*.minimum_score' => 'sometimes|nullable|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grades.*.maximum_score' => 'sometimes|nullable|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grades.*.performance' => 'sometimes|nullable|string|max:150',
            'grades.*.grade_points' => "sometimes|nullable|numeric|min:0|max:4.00|regex:/^\d+(\.\d{1,2})?$/",
            'grades.*.resit_result' => 'sometimes|nullable|string|in:no_resit,resit,high_resit_potential,low_resit_potential',
            'grades.*.result' => 'sometimes|nullable|string',
            'category_max_score' => 'sometimes|nullable|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/'
        ];
    }
}
