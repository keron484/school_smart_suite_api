<?php

namespace App\Http\Requests\GradeScale;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateGradeScaleCategoryRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grade_scale_category_ids' => 'required|array|min:1',
            'grade_scale_category_ids.*category_id' => 'required|integer|exists:grade_scale_categories,id',
        ];
    }
}
