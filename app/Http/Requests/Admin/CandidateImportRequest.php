<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CandidateImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin');
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:json,txt,csv,xlsx',
                'mimetypes:application/json,text/plain,text/json,text/csv,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'overwrite' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'overwrite' => $this->boolean('overwrite'),
        ]);
    }
}
