<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\GradeBand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeBandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $band = $this->route('grade_band');
        $bandId = $band instanceof GradeBand ? $band->id : null;

        return [
            'letter' => ['required', 'string', 'max:2', Rule::unique('grade_bands', 'letter')->ignore($bandId)],
            'min_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }
}
