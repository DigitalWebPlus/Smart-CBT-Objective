<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ObjectiveOption;
use App\Models\ObjectiveResponse;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamAttemptController extends Controller
{
    public function show(Request $request, Exam $exam, ExamAttempt $attempt): View
    {
        abort_if($attempt->exam_id !== $exam->id, 404);

        $attempt->load([
            'exam.questions.question.options',
            'responses.question.options',
            'objectiveResponses.question.options',
            'objectiveResponses.examQuestion.question.options',
            'candidate',
        ]);

        $responses = $attempt->objectiveResponses->isNotEmpty()
            ? $attempt->objectiveResponses
            : $attempt->responses;

        [
            $responseSelectionsByExamQuestion,
            $responseSelectionsByObjectiveQuestion,
            $responseSelectionsFallbackByExamQuestion,
            $responseSelectionsFallbackByObjectiveQuestion,
        ] = $this->buildResponseSelectionMaps($responses);

        return view('admin.monitor-exams.show', [
            'exam' => $exam,
            'attempt' => $attempt,
            'responseSelectionsByExamQuestion' => $responseSelectionsByExamQuestion,
            'responseSelectionsByObjectiveQuestion' => $responseSelectionsByObjectiveQuestion,
            'responseSelectionsFallbackByExamQuestion' => $responseSelectionsFallbackByExamQuestion,
            'responseSelectionsFallbackByObjectiveQuestion' => $responseSelectionsFallbackByObjectiveQuestion,
        ]);
    }

    public function destroy(Request $request, $exam, $attempt): RedirectResponse
    {
        $exam = Exam::findOrFail((int) $exam);
        $attempt = ExamAttempt::findOrFail((int) $attempt);
        abort_if($attempt->exam_id !== $exam->id, 404);

        $attempt->loadMissing('candidate');

        $confirmInput = trim((string) $request->input('confirm_text', ''));
        $candidateName = trim((string) ($attempt->candidate?->name ?? ''));

        $matchesCandidateName = $candidateName !== ''
            && mb_strtolower($confirmInput) === mb_strtolower($candidateName);

        if (! $matchesCandidateName) {
            NotificationService::ERROR('Delete confirmation failed. Type the exact candidate name.');
            return back()->withInput();
        }

        try {
            DB::transaction(function () use ($attempt): void {
                ObjectiveResponse::query()
                    ->where('exam_attempt_id', $attempt->id)
                    ->delete();

                $attempt->delete();
            });

            NotificationService::SUCCESS('Exam attempt deleted permanently.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR('Unable to delete exam attempt.');
            return back();
        }

        if ($request->routeIs('admin.monitor-exams.*')) {
            return redirect()->route('admin.monitor-exams.index');
        }

        return redirect()->route('admin.attempts.show', $exam);
    }

    /**
     * @return array{0: array<int, array<int, string>>, 1: array<int, array<int, string>>, 2: array<int, array<int, string>>, 3: array<int, array<int, string>>}
     */
    private function buildResponseSelectionMaps(Collection $responses): array
    {
        $responseSelectionsByExamQuestion = [];
        $responseSelectionsByObjectiveQuestion = [];
        $responseSelectionsFallbackByExamQuestion = [];
        $responseSelectionsFallbackByObjectiveQuestion = [];

        $allSelectedIds = collect();
        $rawSelections = [];

        foreach ($responses as $response) {
            if (! $response instanceof ObjectiveResponse) {
                continue;
            }

            [$ids, $labels] = $this->normalizeResponseSelections($response);
            $rawSelections[$response->getKey()] = [
                'ids' => $ids,
                'labels' => $labels,
                'exam_question_id' => $response->exam_question_id,
                'objective_question_id' => $response->objective_question_id,
            ];
            $allSelectedIds = $allSelectedIds->merge($ids);
        }

        $optionMap = $allSelectedIds->isNotEmpty()
            ? ObjectiveOption::query()
                ->whereIn('id', $allSelectedIds->unique()->values()->all())
                ->get(['id', 'label', 'description'])
                ->keyBy('id')
            : collect();

        foreach ($rawSelections as $payload) {
            $labels = collect($payload['ids'])
                ->map(function (int $optionId) use ($optionMap) {
                    $option = $optionMap->get($optionId);
                    return $option?->description ?: $option?->label;
                })
                ->filter()
                ->values()
                ->all();

            $fallbackLabels = $payload['labels'];

            if (! empty($payload['exam_question_id'])) {
                if (! empty($labels)) {
                    $responseSelectionsByExamQuestion[$payload['exam_question_id']] = $labels;
                } elseif (! empty($fallbackLabels)) {
                    $responseSelectionsFallbackByExamQuestion[$payload['exam_question_id']] = $fallbackLabels;
                }
            }

            if (! empty($payload['objective_question_id'])) {
                if (! empty($labels)) {
                    $responseSelectionsByObjectiveQuestion[$payload['objective_question_id']] = $labels;
                } elseif (! empty($fallbackLabels)) {
                    $responseSelectionsFallbackByObjectiveQuestion[$payload['objective_question_id']] = $fallbackLabels;
                }
            }
        }

        return [
            $responseSelectionsByExamQuestion,
            $responseSelectionsByObjectiveQuestion,
            $responseSelectionsFallbackByExamQuestion,
            $responseSelectionsFallbackByObjectiveQuestion,
        ];
    }

    /**
     * @return array{0: array<int, int>, 1: array<int, string>}
     */
    private function normalizeResponseSelections(ObjectiveResponse $response): array
    {
        $rawSelected = $response->selected_option_ids ?? [];

        if (empty($rawSelected) && ! empty($response->metadata)) {
            $rawSelected = $response->metadata['selected_option_ids']
                ?? $response->metadata['selected_option_id']
                ?? $response->metadata['selected_options']
                ?? $response->metadata['selected_option']
                ?? [];
        }

        $selectedIds = ObjectiveResponse::normalizeSelectedOptionIds($rawSelected);

        $rawValues = collect(is_array($rawSelected) ? $rawSelected : [$rawSelected])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values();

        $rawLabels = $rawValues
            ->map(function ($value) {
                if (is_array($value)) {
                    return (string) ($value['label'] ?? $value['description'] ?? $value['text'] ?? '');
                }
                if (is_object($value)) {
                    return (string) ($value->label ?? $value->description ?? $value->text ?? '');
                }
                if (! is_numeric($value)) {
                    return (string) $value;
                }
                return '';
            })
            ->filter()
            ->values()
            ->all();

        if (empty($rawLabels) && ! empty($response->metadata)) {
            $rawLabels = collect($response->metadata['selected_option_labels']
                ?? $response->metadata['selected_option_texts']
                ?? $response->metadata['selected_option_values']
                ?? [])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (string) $value)
                ->values()
                ->all();
        }

        if (empty($rawLabels) && empty($selectedIds)) {
            $numericValues = $rawValues
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (int) $value)
                ->values();
            $optionList = $response->examQuestion?->question?->options?->values() ?? collect();

            if ($numericValues->isNotEmpty() && $optionList->isNotEmpty()) {
                $indexOffset = $numericValues->contains(0) ? 0 : 1;
                if (! $numericValues->contains(0)) {
                    $min = $numericValues->min();
                    $max = $numericValues->max();
                    if ($min < 1 || $max > $optionList->count()) {
                        $indexOffset = 0;
                    }
                }

                $rawLabels = $numericValues
                    ->map(function (int $value) use ($optionList, $indexOffset) {
                        $index = $value - $indexOffset;
                        $option = $optionList->get($index);
                        return $option?->description ?: $option?->label;
                    })
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        return [$selectedIds, $rawLabels];
    }
}
