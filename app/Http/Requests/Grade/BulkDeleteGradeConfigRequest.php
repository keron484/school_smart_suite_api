<?php

namespace App\Http\Requests\Grade;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteGradeConfigRequest extends FormRequest
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
           'grade_scale_category_ids' => 'required|array',
           'grade_scale_category_ids.*.category_id' => 'required|string|exists:school_grade_scale_categories,id'
        ];
    }
}
