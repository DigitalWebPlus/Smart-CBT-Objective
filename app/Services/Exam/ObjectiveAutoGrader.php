<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\ExamQuestion;
use App\Models\ObjectiveQuestion;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use function collect;

class ObjectiveAutoGrader
{
    /**
     * @param array<int|string> $selectedOptionIds
     * @return array{is_correct: bool, awarded_marks: float}
     */
    public function grade(ExamQuestion $examQuestion, array $selectedOptionIds): array
    {
        $question = $examQuestion->question ?? $examQuestion->questionable ?? null;
        $marks = (float) $examQuestion->marks;

        if (! $question instanceof ObjectiveQuestion) {
            return ['is_correct' => false, 'awarded_marks' => 0.0];
        }

        $selected = $this->normalizeSelections($selectedOptionIds);
        $correct = $question->options()->where('is_correct', true)->pluck('id')->sort()->values();

        if ($correct->isEmpty()) {
            return ['is_correct' => false, 'awarded_marks' => 0.0];
        }

        $isExactMatch = $selected->sort()->values()->all() === $correct->all();
        $settings = $examQuestion->settings ?? [];
        $partialCredit = (bool) Arr::get($settings, 'partial_credit', false);
        $negativeMarking = (bool) Arr::get($settings, 'negative_marking', false);
        $negativeStep = (float) Arr::get($settings, 'negative_mark_value', 0.0);
        $awarded = 0.0;

        switch ($question->question_type) {
            case ObjectiveQuestion::TYPE_MSA:
            case ObjectiveQuestion::TYPE_TOF:
                $isCorrect = $isExactMatch && $selected->count() === 1;
                $awarded = $isCorrect ? $marks : 0.0;
                break;
            case ObjectiveQuestion::TYPE_MMA:
                if ($partialCredit) {
                    $correctSelections = $selected->intersect($correct);
                    $incorrectSelections = $selected->diff($correct);
                    $fraction = $correct->count() > 0
                        ? $correctSelections->count() / $correct->count()
                        : 0.0;
                    $awarded = max(0.0, $marks * $fraction);

                    if ($negativeMarking && $negativeStep > 0 && $incorrectSelections->isNotEmpty()) {
                        $awarded -= $incorrectSelections->count() * $negativeStep;
                    }

                    $awarded = min($marks, max(0.0, $awarded));
                    $isCorrect = $isExactMatch;
                } else {
                    $isCorrect = $isExactMatch;
                    $awarded = $isCorrect ? $marks : 0.0;
                }
                break;
            default:
                $isCorrect = $isExactMatch;
                $awarded = $isCorrect ? $marks : 0.0;
        }

        return [
            'is_correct' => $isCorrect,
            'awarded_marks' => round($awarded, 2),
        ];
    }

    /**
     * @param array<int|string> $selected
     */
    private function normalizeSelections(array $selected): Collection
    {
        return collect($selected)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();
    }
}
