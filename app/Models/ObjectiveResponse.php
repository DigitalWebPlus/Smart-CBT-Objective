<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ExamQuestion;

class ObjectiveResponse extends Model
{
    use HasFactory;

    protected $table = 'exam_objective_responses';

    /**
     * @return array<int, int>
     */
    public static function normalizeSelectedOptionIds(mixed $rawSelected): array
    {
        if (is_string($rawSelected)) {
            $decoded = json_decode($rawSelected, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $rawSelected = $decoded;
            } else {
                $rawSelected = array_map('trim', explode(',', $rawSelected));
            }
        }

        return collect(is_array($rawSelected) ? $rawSelected : [$rawSelected])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(function ($value) {
                if (is_array($value)) {
                    return (int) ($value['id'] ?? $value['option_id'] ?? $value['value'] ?? 0);
                }
                if (is_object($value)) {
                    return (int) ($value->id ?? $value->option_id ?? $value->value ?? 0);
                }
                if (is_numeric($value)) {
                    return (int) $value;
                }
                return 0;
            })
            ->filter(fn ($value) => $value > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function resolveSelectedOptionLabels(?ObjectiveQuestion $question, array $selectedIds): array
    {
        if (! $question || empty($selectedIds)) {
            return [];
        }

        $optionMap = $question->options->keyBy('id');

        return collect($selectedIds)
            ->map(function (int $optionId) use ($optionMap) {
                $option = $optionMap->get($optionId);
                return $option?->description ?: $option?->label;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected $fillable = [
        'exam_attempt_id',
        'exam_question_id',
        'objective_question_id',
        'selected_option_ids',
        'is_correct',
        'awarded_marks',
        'metadata',
    ];

    protected $casts = [
        'selected_option_ids' => 'array',
        'metadata' => 'array',
        'is_correct' => 'boolean',
        'awarded_marks' => 'decimal:2',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ObjectiveQuestion::class, 'objective_question_id');
    }

    public function examQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class);
    }
}
