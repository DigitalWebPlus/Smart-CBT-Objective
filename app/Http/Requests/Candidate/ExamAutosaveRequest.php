<?php

declare(strict_types=1);

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;

class ExamAutosaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'min:1'],
            'answers' => ['required', 'array'],
            'answers.*.exam_question_id' => ['required', 'integer', 'min:1'],
            'answers.*.selected_option_ids' => ['sometimes', 'array'],
            'answers.*.selected_option_ids.*' => ['integer'],
            'answers.*.answer_text' => ['nullable', 'string'],
        ];
    }
}
