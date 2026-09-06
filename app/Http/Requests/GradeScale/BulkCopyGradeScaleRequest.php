<?php

namespace App\Http\Requests\GradeScale;

use Illuminate\Foundation\Http\FormRequest;

class BulkCopyGradeScaleRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'configIds' => 'required|array',
            'configIds.*.grade_config_id' => 'required|string|exists:school_grade_scale_categories,id',
            'target_config_id' => 'required|string|exists:school_grade_scale_categories,id'
        ];
    }
}
