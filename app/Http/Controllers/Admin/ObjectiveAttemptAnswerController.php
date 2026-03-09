<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ObjectiveOption;
use App\Models\ObjectiveResponse;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Collection;

class ObjectiveAttemptAnswerController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $examId = $request->integer('exam_id') ?: null;
        $candidateQuery = trim((string) $request->input('candidate', ''));
        $statusOptions = [
            ExamAttempt::STATUS_SUBMITTED,
            ExamAttempt::STATUS_PUBLISHED,
            ExamAttempt::STATUS_CANCELED,
            ExamAttempt::STATUS_RETAKE,
        ];

        $query = ExamAttempt::query()
            ->with([
                'exam' => fn ($builder) => $builder->withSum('questions', 'marks')->with('subjects', 'questions.question.subject'),
                'candidate',
                'responses.question.subject',
            ])
            ->latest()
            ->whereHas('exam', function ($builder): void {
                $builder->where('exam_type', Exam::TYPE_OBJECTIVE);
            })
            ->whereIn('status', $statusOptions);

        if ($status !== '' && in_array($status, $statusOptions, true)) {
            $query->where('status', $status);
        }

        if ($examId) {
            $query->where('exam_id', $examId);
        }

        if ($candidateQuery !== '') {
            $query->whereHas('candidate', function ($builder) use ($candidateQuery): void {
                $builder->where('name', 'like', "%{$candidateQuery}%")
                    ->orWhere('email', 'like', "%{$candidateQuery}%")
                    ->orWhere('registration_number', 'like', "%{$candidateQuery}%");
            });
        }

        $attempts = $query->paginate(20)->withQueryString();

        $attempts->getCollection()->transform(function (ExamAttempt $attempt) {
            $subjectScores = collect($attempt->subject_scores ?? []);
            $responsesByQuestion = $attempt->responses->keyBy('exam_question_id');
            $subjectTotals = [];
            $totalMaxMarks = 0;
            $totalScore = 0;

            foreach ($attempt->exam?->questions ?? [] as $examQuestion) {
                $question = $examQuestion->question;
                $subjectId = $question?->subject_id;
                $subjectKey = $subjectId ? (string) $subjectId : 'unassigned';
                if (! isset($subjectTotals[$subjectKey])) {
                    $subjectTotals[$subjectKey] = [
                        'subject_id' => $subjectId,
                        'subject_code' => $question?->subject?->code ?? 'GEN',
                        'subject_name' => $question?->subject?->name ?? 'General',
                        'max_marks' => 0,
                        'auto_score' => 0,
                        'manual_score' => 0,
                        'total_score' => 0,
                        'percentage' => 0,
                        'grade_letter' => null,
                        'grade_remark' => null,
                    ];
                }

                $subjectTotals[$subjectKey]['max_marks'] += (float) $examQuestion->marks;
                $totalMaxMarks += (float) $examQuestion->marks;

                $response = $responsesByQuestion->get($examQuestion->id);
                $awarded = (float) ($response?->awarded_marks ?? 0);
                $subjectTotals[$subjectKey]['auto_score'] += $awarded;
                $subjectTotals[$subjectKey]['total_score'] += $awarded;
                $totalScore += $awarded;
            }

            if ($totalMaxMarks <= 0 && $attempt->exam_id) {
                $totalMaxMarks = (float) ExamQuestion::query()
                    ->where('exam_id', $attempt->exam_id)
                    ->sum('marks');
            }

            $computed = collect($subjectTotals)
                ->map(function (array $subject): array {
                    $maxMarks = (float) ($subject['max_marks'] ?? 0);
                    $totalScore = (float) ($subject['total_score'] ?? 0);
                    $subject['percentage'] = $maxMarks > 0
                        ? round(($totalScore / $maxMarks) * 100, 2)
                        : 0;
                    return $subject;
                })
                ->values()
                ->all();

            if ($subjectScores->isNotEmpty()) {
                $totalMaxMarks = (float) $subjectScores->sum('max_marks');
                $totalScore = (float) ($attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? $subjectScores->sum('total_score'));
            }

            $attempt->computed_subject_scores = $subjectScores->isNotEmpty()
                ? $subjectScores->all()
                : $computed;
            $attempt->computed_total_marks = $totalMaxMarks;
            $attemptScore = $attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? $totalScore;
            $attempt->computed_percentage = $totalMaxMarks > 0
                ? round(((float) $attemptScore / $totalMaxMarks) * 100, 2)
                : 0;

            return $attempt;
        });

        $exams = Exam::query()
            ->where('exam_type', Exam::TYPE_OBJECTIVE)
            ->withCount([
                'subjects',
                'questions',
                'attempts as attempts_count' => function ($builder) use ($statusOptions): void {
                    $builder->whereIn('status', $statusOptions);
                },
            ])
            ->withSum('questions', 'marks')
            ->addSelect([
                'candidate_target_count' => DB::table('exam_department_assignments')
                    ->join('department_user', 'department_user.department_id', '=', 'exam_department_assignments.department_id')
                    ->whereColumn('exam_department_assignments.exam_id', 'exams.id')
                    ->selectRaw('COUNT(DISTINCT department_user.user_id)')
                    ->limit(1),
            ])
            ->orderBy('title')
            ->get(['id', 'title', 'ends_at']);

        $statusCounts = ExamAttempt::query()
            ->whereHas('exam', function ($builder): void {
                $builder->where('exam_type', Exam::TYPE_OBJECTIVE);
            })
            ->whereIn('status', $statusOptions)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.attempts.objective.index', compact(
            'attempts',
            'exams',
            'statusCounts',
            'statusOptions',
            'status',
            'examId',
            'candidateQuery'
        ));
    }

    public function show(Request $request, Exam $exam): View
    {
        $status = $request->string('status')->toString();
        $candidateQuery = trim((string) $request->input('candidate', ''));
        $statusOptions = [
            ExamAttempt::STATUS_SUBMITTED,
            ExamAttempt::STATUS_PUBLISHED,
            ExamAttempt::STATUS_CANCELED,
            ExamAttempt::STATUS_RETAKE,
        ];

        $query = ExamAttempt::query()
            ->with([
                'exam' => fn ($builder) => $builder->withSum('questions', 'marks')->with('subjects', 'questions.question.subject'),
                'candidate',
                'responses.question.subject',
            ])
            ->latest()
            ->where('exam_id', $exam->id)
            ->whereIn('status', $statusOptions);

        if ($status !== '' && in_array($status, $statusOptions, true)) {
            $query->where('status', $status);
        }

        if ($candidateQuery !== '') {
            $query->whereHas('candidate', function ($builder) use ($candidateQuery): void {
                $builder->where('name', 'like', "%{$candidateQuery}%")
                    ->orWhere('email', 'like', "%{$candidateQuery}%")
                    ->orWhere('registration_number', 'like', "%{$candidateQuery}%");
            });
        }

        $attempts = $query->paginate(20)->withQueryString();

        $exam->loadCount([
            'subjects',
            'questions',
            'attempts as attempts_count' => function ($builder) use ($statusOptions): void {
                $builder->whereIn('status', $statusOptions);
            },
        ]);

        $exam->candidate_target_count = (int) DB::table('exam_department_assignments')
            ->join('department_user', 'department_user.department_id', '=', 'exam_department_assignments.department_id')
            ->where('exam_department_assignments.exam_id', $exam->id)
            ->selectRaw('COUNT(DISTINCT department_user.user_id)')
            ->value(DB::raw('COUNT(DISTINCT department_user.user_id)'));

        $statusCounts = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->whereIn('status', $statusOptions)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $attempts->getCollection()->transform(function (ExamAttempt $attempt) {
            $subjectScores = collect($attempt->subject_scores ?? []);
            $responsesByQuestion = $attempt->responses->keyBy('exam_question_id');
            $subjectTotals = [];
            $totalMaxMarks = 0;
            $totalScore = 0;

            foreach ($attempt->exam?->questions ?? [] as $examQuestion) {
                $question = $examQuestion->question;
                $subjectId = $question?->subject_id;
                $subjectKey = $subjectId ? (string) $subjectId : 'unassigned';
                if (! isset($subjectTotals[$subjectKey])) {
                    $subjectTotals[$subjectKey] = [
                        'subject_id' => $subjectId,
                        'subject_code' => $question?->subject?->code ?? 'GEN',
                        'subject_name' => $question?->subject?->name ?? 'General',
                        'max_marks' => 0,
                        'auto_score' => 0,
                        'manual_score' => 0,
                        'total_score' => 0,
                        'percentage' => 0,
                        'grade_letter' => null,
                        'grade_remark' => null,
                    ];
                }

                $subjectTotals[$subjectKey]['max_marks'] += (float) $examQuestion->marks;
                $totalMaxMarks += (float) $examQuestion->marks;

                $response = $responsesByQuestion->get($examQuestion->id);
                $awarded = (float) ($response?->awarded_marks ?? 0);
                $subjectTotals[$subjectKey]['auto_score'] += $awarded;
                $subjectTotals[$subjectKey]['total_score'] += $awarded;
                $totalScore += $awarded;
            }

            if ($totalMaxMarks <= 0 && $attempt->exam_id) {
                $totalMaxMarks = (float) ExamQuestion::query()
                    ->where('exam_id', $attempt->exam_id)
                    ->sum('marks');
            }

            $computed = collect($subjectTotals)
                ->map(function (array $subject): array {
                    $maxMarks = (float) ($subject['max_marks'] ?? 0);
                    $totalScore = (float) ($subject['total_score'] ?? 0);
                    $subject['percentage'] = $maxMarks > 0
                        ? round(($totalScore / $maxMarks) * 100, 2)
                        : 0;
                    return $subject;
                })
                ->values()
                ->all();

            if ($subjectScores->isNotEmpty()) {
                $totalMaxMarks = (float) $subjectScores->sum('max_marks');
                $totalScore = (float) ($attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? $subjectScores->sum('total_score'));
            }

            $attempt->computed_subject_scores = $subjectScores->isNotEmpty()
                ? $subjectScores->all()
                : $computed;
            $attempt->computed_total_marks = $totalMaxMarks;
            $attemptScore = $attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? $totalScore;
            $attempt->computed_percentage = $totalMaxMarks > 0
                ? round(((float) $attemptScore / $totalMaxMarks) * 100, 2)
                : 0;

            return $attempt;
        });

        return view('admin.attempts.objective.show', compact(
            'attempts',
            'statusCounts',
            'statusOptions',
            'status',
            'exam',
            'candidateQuery'
        ));
    }

    public function showAttempt(Request $request, Exam $exam, ExamAttempt $attempt): View
    {
        return app(ExamAttemptController::class)->show($request, $exam, $attempt);
    }

    private function backfillResponseSelections(Collection $responses): void
    {
        foreach ($responses as $response) {
            $question = $response->examQuestion?->question ?? $response->question;
            if (! $question) {
                continue;
            }

            $rawSelected = $response->selected_option_ids ?? [];
            if (empty($rawSelected) && ! empty($response->metadata)) {
                $rawSelected = $response->metadata['selected_option_ids']
                    ?? $response->metadata['selected_option_id']
                    ?? $response->metadata['selected_options']
                    ?? $response->metadata['selected_option']
                    ?? [];
            }

            $selectedIds = ObjectiveResponse::normalizeSelectedOptionIds($rawSelected);
            $rawLabels = collect($response->metadata['selected_option_labels'] ?? [])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (string) $value)
                ->values()
                ->all();

            if (empty($selectedIds) && ! empty($rawLabels)) {
                $optionMap = $question->options->mapWithKeys(function ($option) {
                    $label = mb_strtolower(trim((string) $option->label));
                    $description = mb_strtolower(trim((string) ($option->description ?? '')));
                    return [
                        $label => $option->id,
                        $description => $option->id,
                    ];
                });

                $selectedIds = collect($rawLabels)
                    ->map(fn ($label) => $optionMap->get(mb_strtolower(trim((string) $label))))
                    ->filter(fn ($value) => $value !== null)
                    ->map(fn ($value) => (int) $value)
                    ->values()
                    ->all();
            }

            $selectedLabels = ObjectiveResponse::resolveSelectedOptionLabels($question, $selectedIds);

            $metadata = $response->metadata ?? [];
            $dirty = false;

            if (empty($response->selected_option_ids) && ! empty($selectedIds)) {
                $response->selected_option_ids = $selectedIds;
                $dirty = true;
            }

            if (! empty($selectedIds) && empty($metadata['selected_option_ids'])) {
                $metadata['selected_option_ids'] = $selectedIds;
                $dirty = true;
            }

            if (! empty($selectedLabels) && empty($metadata['selected_option_labels'])) {
                $metadata['selected_option_labels'] = $selectedLabels;
                $dirty = true;
            }

            if ($dirty) {
                $response->metadata = $metadata;
                $response->save();
            }
        }
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

    public function updateStatus(Request $request, mixed $first = null, mixed $second = null): RedirectResponse
    {
        $attemptRouteValue = $request->route('attempt');
        $attemptId = $attemptRouteValue instanceof ExamAttempt
            ? $attemptRouteValue->id
            : (int) $attemptRouteValue;

        $attempt = ExamAttempt::query()->findOrFail($attemptId);

        $examRouteValue = $request->route('exam');
        if ($examRouteValue !== null && $examRouteValue !== '') {
            $examId = $examRouteValue instanceof Exam
                ? $examRouteValue->id
                : (int) $examRouteValue;

            if ($examId > 0 && $attempt->exam_id !== $examId) {
                abort(404);
            }
        }

        $statusOptions = [
            ExamAttempt::STATUS_SUBMITTED,
            ExamAttempt::STATUS_PUBLISHED,
            ExamAttempt::STATUS_CANCELED,
            ExamAttempt::STATUS_RETAKE,
        ];

        $status = $request->string('status')->toString();

        if (! in_array($status, $statusOptions, true)) {
            NotificationService::ERROR('Invalid status selected.');
            return back();
        }

        try {
            $attempt->update(['status' => $status]);
            NotificationService::SUCCESS('Attempt status updated.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();
        }

        return back();
    }
}
