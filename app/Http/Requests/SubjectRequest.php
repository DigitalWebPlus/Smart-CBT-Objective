<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin');
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'code')->ignore($subjectId),
            ],
            'level' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in([
                Subject::STATUS_DRAFT,
                Subject::STATUS_ACTIVE,
                Subject::STATUS_ARCHIVED,
            ])],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
