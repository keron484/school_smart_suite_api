<?php

namespace App\Http\Requests\GradeScale;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteGradeScaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'grade_scale_category_ids' => 'required|array',
            'grade_scale_category_ids.*.category_id' => 'required|string|exists:school_grade_scale_categories,id'
        ];
    }
}
