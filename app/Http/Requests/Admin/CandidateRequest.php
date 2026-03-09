<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $candidateId = $this->route('candidate')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'registration_number')->ignore($candidateId),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($candidateId),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', 'distinct', 'exists:departments,id'],
            'status' => ['required', Rule::in(User::STATUSES)],
            'password' => [
                $candidateId ? 'nullable' : 'required',
                'confirmed',
                Password::defaults(),
            ],
            'photo' => [
                'nullable',
                'image',
                'max:2048',
            ],
        ];
    }
}
