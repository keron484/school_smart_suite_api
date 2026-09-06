<?php

namespace App\Http\Requests\GradeScale;

use Illuminate\Foundation\Http\FormRequest;

class CreateGradeScaleRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grade_scales' => 'required|array|min:1',
            'grade_scales.*.letter_grade_id' => 'required|string|exists:letter_grades,id',
            'grade_scales.*.minimum_score' => 'required|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grade_scales.*.maximum_score' => 'required|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grade_scales.*.performance' => 'required|string|max:150',
            'grade_scales.*.grade_points' => "required|numeric|min:0|max:4.00|regex:/^\d+(\.\d{1,2})?$/",
            'grade_scales.*.resit_result' => 'required|string|in:no_resit,resit,high_resit_potential,low_resit_potential',
            'grade_scales.*.result' => 'required|string|in:passed,failed',
            'grade_max_score' => 'required|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grades_category_id' => 'required|uuid|exists:school_grade_scale_categories,id'
        ];
    }
}
