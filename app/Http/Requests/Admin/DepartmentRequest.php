<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($departmentId),
            ],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('departments', 'code')->ignore($departmentId),
            ],
            'description' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
