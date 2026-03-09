<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
