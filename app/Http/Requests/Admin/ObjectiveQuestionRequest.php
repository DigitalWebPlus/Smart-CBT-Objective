<?php

namespace App\Http\Requests\Admin;

use App\Models\ObjectiveQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ObjectiveQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'question_text' => ['required', 'string'],
            'question_type' => ['required', Rule::in([
                ObjectiveQuestion::TYPE_MSA,
                ObjectiveQuestion::TYPE_MMA,
                ObjectiveQuestion::TYPE_TOF,
            ])],
            'image' => ['nullable', 'image', 'max:4096'],
            'marks' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'explanation' => ['nullable', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.label' => ['required', 'string', 'max:255'],
            'options.*.description' => ['nullable', 'string'],
            'options.*.is_correct' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $options = collect($this->input('options', []));

            if ($options->isEmpty()) {
                $validator->errors()->add('options', 'Please provide at least two answer options.');
                return;
            }

            $correctCount = $options->filter(function ($option) {
                return filter_var(Arr::get($option, 'is_correct', false), FILTER_VALIDATE_BOOL);
            })->count();

            if ($correctCount === 0) {
                $validator->errors()->add('options', 'Mark at least one option as the correct answer.');
            }

            $type = $this->input('question_type');

            if ($type === ObjectiveQuestion::TYPE_MSA && $correctCount !== 1) {
                $validator->errors()->add('options', 'Single-answer questions must have exactly one correct option.');
            }

            if ($type === ObjectiveQuestion::TYPE_MMA && $correctCount < 2) {
                $validator->errors()->add('options', 'Multiple-answer questions must have at least two correct options.');
            }

            if ($type === ObjectiveQuestion::TYPE_TOF) {
                if ($options->count() !== 2) {
                    $validator->errors()->add('options', 'True/False questions must have exactly two options.');
                }

                if ($correctCount !== 1) {
                    $validator->errors()->add('options', 'True/False questions must have exactly one correct option.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
