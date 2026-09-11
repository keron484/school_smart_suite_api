<?php

namespace App\Http\Requests\Invigilator;

use Illuminate\Foundation\Http\FormRequest;

class AssignExamInvigilator extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_id' => 'required|uuid|exists:exams,id',
            'invigilators' => 'required|array|min:1',
            'invigilators.*.actorable_id' => 'required|uuid',
            'invigilators.*.actorable_type' => 'required|string|max:150'
        ];
    }
}
