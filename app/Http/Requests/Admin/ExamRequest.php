<?php

namespace App\Http\Requests\Admin;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'exam_type' => ['required', Rule::in([Exam::TYPE_OBJECTIVE])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(Exam::STATUSES)],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['required', 'integer', 'exists:departments,id'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['required', 'integer', 'exists:subjects,id'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.objective_question_id' => ['required', 'integer', 'exists:bank_questions,id'],
            'questions.*.marks' => ['required', 'numeric', 'min:0'],
            'questions.*.display_order' => ['required', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
            'settings.allow_review' => ['nullable', 'boolean'],
            'settings.allow_result_view' => ['nullable', 'boolean'],
        ];
    }
}
