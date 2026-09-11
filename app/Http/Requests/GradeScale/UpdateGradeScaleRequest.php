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
            'grade_scales' => 'required|array|min:1',
            'grade_scales.*.grade_scale_id' => 'required|uuid|exists:grade_scales,id',
            'grade_scales.*.letter_grade_id' => 'sometimes|nullable|string|exists:letter_grades,id',
            'grade_scales.*.minimum_score' => 'sometimes|nullable|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grade_scales.*.maximum_score' => 'sometimes|nullable|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grade_scales.*.performance' => 'sometimes|nullable|string|max:150',
            'grade_scales.*.grade_points' => "sometimes|nullable|numeric|min:0|max:4.00|regex:/^\d+(\.\d{1,2})?$/",
            'grade_scales.*.resit_result' => 'sometimes|nullable|string|in:no_resit,resit,high_resit_potential,low_resit_potential',
            'grade_scales.*.result' => 'sometimes|nullable|string',
            'category_max_score' => 'sometimes|nullable|numeric|min:0|max:1000|regex:/^\d+(\.\d{1,2})?$/',
            'grades_category_id' => 'required|uuid|exists:school_grade_scale_categories,id'
        ];
    }
}
