<?php

namespace App\Services;

use App\Models\ObjectiveOption;
use App\Models\ObjectiveQuestion;
use Illuminate\Support\Arr;

class ObjectiveQuestionService
{
    public function syncOptions(ObjectiveQuestion $question, array $options): void
    {
        $question->options()->delete();

        foreach ($options as $index => $option) {
            ObjectiveOption::query()->create([
                'objective_question_id' => $question->id,
                'label' => Arr::get($option, 'label'),
                'description' => Arr::get($option, 'description'),
                'is_correct' => filter_var(Arr::get($option, 'is_correct'), FILTER_VALIDATE_BOOL),
                'display_order' => $index + 1,
                'image_path' => Arr::get($option, 'image_path'),
            ]);
        }
    }
}
