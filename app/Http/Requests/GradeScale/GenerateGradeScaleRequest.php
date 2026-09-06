<?php

namespace App\Http\Requests\GradeScale;

use Illuminate\Foundation\Http\FormRequest;

class GenerateGradeScaleRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'max_score' => 'required|numeric|min:0',
            'exam_type' => 'required|string|in:exam,ca,resit'
        ];
    }
}
