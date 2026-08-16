<?php

namespace App\Http\Requests\Hall;

use Illuminate\Foundation\Http\FormRequest;

class ImportHallRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:10240',
            ],
            'mapping' => [
                'required',
                'array',
            ],

            'mapping.standardFields' => [
                'required',
                'array',
            ],

            'mapping.standardFields.name' => [
                'required',
                'string',
            ],
            'mapping.standardFields.capacity' => [
                'required',
                'string',
            ],
            'mapping.standardFields.location' => [
                'required',
                'string',
            ],
            'mapping.repeatableGroups.types.*' => [
                'required',
                'array',
            ],
            'mapping.repeatableGroups.types.*.type' => [
                'required',
                'string',
            ],
        ];
    }
}
