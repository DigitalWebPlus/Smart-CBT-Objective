<?php

declare(strict_types=1);

namespace App\Http\Requests\Candidate;

use App\Models\ExamAttempt;
use App\Models\ObjectiveExamQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('auto_submit') && $this->input('answers') === null) {
            $this->merge([
                'answers' => [],
            ]);
        }
    }

    public function rules(): array
    {
        $questionRule = $this->examQuestionRule();

        return [
            'answers' => ['required', 'array'],
            'answers.*.exam_question_id' => array_filter(['required', $questionRule]),
            'answers.*.selected_option_ids' => ['nullable', 'array'],
            'answers.*.selected_option_ids.*' => ['integer', 'exists:bank_options,id'],
        ];
    }

    private function examQuestionRule()
    {
        $attempt = $this->route('attempt');

        if (! $attempt instanceof ExamAttempt) {
            return Rule::in([]);
        }

        $exam = $attempt->exam;

        $table = (new ObjectiveExamQuestion())->getTable();

        if ($table === null) {
            return Rule::in([]);
        }

        return Rule::exists($table, 'id')
            ->where(fn ($query) => $query->where('exam_id', $attempt->exam_id));
    }
}
