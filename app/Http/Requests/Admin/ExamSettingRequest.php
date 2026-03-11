<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExamSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $rules = [];

        foreach (config('exam-settings.fields', []) as $fieldKey => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $fieldRules = $fieldDefinition['rules'] ?? [];

            if (is_array($fieldRules) && $fieldRules !== []) {
                $rules[(string) $fieldKey] = $fieldRules;
            }
        }

        return $rules;
    }
}
