<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class GetJobRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
           'category' => ['nullable', 'sometimes', 'string', 'max:150'],
           "status" => ['nullable', 'sometimes', 'string', 'in:completed,queued,failed,inprogress'],
           "group_by" => ['nullable', 'sometimes', 'string', 'in:status,stage,category'],
        ];
    }
}
