<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class CreateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'specialty_id' => 'required|string|exists:specialties,id',
            'guardian_id' => 'required|string|exists:parents,id',
            'student_batch_id' => 'required|string|exists:student_batches,id',
            'relationship_id' => 'required|string|exists:stu_par_relationships,id',
            'student_source_id' => 'required|string|exists:student_sources,id',
            'gender_id' => 'required|string|exists:genders,id',
        ];
    }
}
