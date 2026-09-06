<?php

namespace App\Http\Requests\Grade;

use Illuminate\Foundation\Http\FormRequest;

class CreateGradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grades' => 'required|array',
            'grades.*.letter_grade_id' => 'required|string|exists:letter_grades,id',
            'grades.*.minimum_score' => 'required|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grades.*.maximum_score' => 'required|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grades.*.performance' => 'required|string|max:150',
            'grades.*.grade_points' => "required|numeric|min:0|max:4.00|regex:/^\d+(\.\d{1,2})?$/",
            'grades.*.resit_result' => 'required|string|in:no_resit,resit,high_resit_potential,low_resit_potential',
            'grades.*.result' => 'required|string',
            'grade_max_score' => 'required|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grade_scale_category_id' => 'required|uuid|exists:school_grade_scale_categories,id'
        ];
    }
}
