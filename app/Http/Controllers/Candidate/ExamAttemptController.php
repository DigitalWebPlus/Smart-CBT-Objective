<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ObjectiveResponse;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Collection;

class ExamAttemptController extends Controller
{
    public function store(Exam $exam): RedirectResponse
    {
        if (! $exam->isPublished()) {
            abort(404);
        }

        $userId = Auth::id();
        if (! $userId) {
            abort(403);
        }

        $this->authorizeExamAccess($exam, $userId);

        $hasOtherInProgress = ExamAttempt::query()
            ->where('user_id', $userId)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->where('exam_id', '!=', $exam->id)
            ->exists();

        if ($hasOtherInProgress) {
            NotificationService::ERROR('You have an exam in progress. Finish it before starting another.');

            return redirect()->route('candidate.exams.index');
        }

        $attempt = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $userId)
            ->first();

        $ipAddress = request()->ip();

        if ($attempt) {
            if (in_array($attempt->status, [ExamAttempt::STATUS_IN_PROGRESS, ExamAttempt::STATUS_PENDING], true)) {
                return redirect()->route('candidate.exam-attempts.show', $attempt);
            }

            if ($attempt->status === ExamAttempt::STATUS_RETAKE) {
                DB::transaction(function () use ($attempt, $ipAddress) {
                    $attempt->responses()->delete();

                    $attempt->update([
                        'status' => ExamAttempt::STATUS_IN_PROGRESS,
                        'started_at' => now(),
                        'submitted_at' => null,
                        'login_count' => $ipAddress ? 1 : 0,
                        'login_ips' => $ipAddress ? [$ipAddress] : [],
                        'auto_score' => 0,
                        'manual_score' => 0,
                        'total_score' => 0,
                        'percentage' => 0,
                        'subject_scores' => null,
                        'grade_letter' => null,
                        'grade_remark' => null,
                        'feedback' => null,
                        'graded_by' => null,
                        'graded_at' => null,
                        'score' => 0,
                    ]);
                });

                return redirect()->route('candidate.exam-attempts.show', $attempt);
            }

            if ($attempt->status === ExamAttempt::STATUS_CANCELED) {
                NotificationService::ERROR('This exam attempt has been canceled and cannot be retaken.');

                return redirect()->route('candidate.exams.index');
            }

            NotificationService::ERROR('You have already submitted this exam.');

            return redirect()->route('candidate.exams.index');
        }

        $payload = [
            'exam_id' => $exam->id,
            'user_id' => $userId,
            'status' => ExamAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ];

        if ($ipAddress) {
            $payload['login_count'] = 1;
            $payload['login_ips'] = [$ipAddress];
        }

        $attempt = ExamAttempt::query()->create($payload);

        return redirect()->route('candidate.exam-attempts.show', $attempt);
    }

    public function show(ExamAttempt $attempt): View|RedirectResponse
    {
        $userId = Auth::id();
        if (! $userId || $attempt->user_id !== $userId) {
            abort(403);
        }

        $reviewLockedStatuses = [
            ExamAttempt::STATUS_SUBMITTED,
            ExamAttempt::STATUS_GRADED,
            ExamAttempt::STATUS_PUBLISHED,
            ExamAttempt::STATUS_CANCELED,
        ];

        $attempt->load([
            'candidate',
            'exam.subjects',
            'exam.questions.question.subject',
            'exam.questions.question.options',
            'objectiveResponses.examQuestion.question.subject',
        ]);

        if (in_array($attempt->status, $reviewLockedStatuses, true) && ! $attempt->exam->allowsReview()) {
            NotificationService::ERROR('Exam review is disabled for this assessment.');

            return redirect()->route('candidate.exams.index');
        }

        return view('candidate.exams.attempts.simple', [
            'attempt' => $attempt,
        ]);
    }

    public function submit(Request $request, ExamAttempt $attempt): JsonResponse|RedirectResponse
    {
        $userId = Auth::id();
        if (! $userId || $attempt->user_id !== $userId) {
            abort(403);
        }

        if ($attempt->status === ExamAttempt::STATUS_SUBMITTED) {
            NotificationService::ERROR('Exam already submitted.');

            return redirect()->route('candidate.exams.index');
        }

        $attempt->load(['exam.questions.question.options', 'exam.questions.question.subject']);

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.selected_option_ids' => ['nullable', 'array'],
            'answers.*.selected_option_ids.*' => ['nullable', 'integer'],
            'answers.*.exam_question_id' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($attempt, $validated) {
            $score = 0;
            $subjectTotals = [];

            foreach ($attempt->exam->questions as $examQuestion) {
                $question = $examQuestion->question;
                $rawSelection = $validated['answers'][$examQuestion->id]['selected_option_ids'] ?? null;
                $selectedIds = collect(\App\Models\ObjectiveResponse::normalizeSelectedOptionIds($rawSelection))
                    ->unique()
                    ->values();

                $correctIds = $question->options
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->map(fn ($value) => (int) $value)
                    ->sort()
                    ->values();

                $isCorrect = $selectedIds->sort()->values()->all() === $correctIds->all() && $selectedIds->isNotEmpty();
                $awardedMarks = $isCorrect ? (float) $examQuestion->marks : 0.0;

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
                $subjectTotals[$subjectKey]['auto_score'] += $awardedMarks;
                $subjectTotals[$subjectKey]['total_score'] += $awardedMarks;

                $selectedLabels = \App\Models\ObjectiveResponse::resolveSelectedOptionLabels(
                    $question,
                    $selectedIds->all()
                );
                $metadata = [];
                if ($selectedIds->isNotEmpty()) {
                    $metadata['selected_option_ids'] = $selectedIds->all();
                }
                if (! empty($selectedLabels)) {
                    $metadata['selected_option_labels'] = $selectedLabels;
                }

                ObjectiveResponse::query()->updateOrCreate(
                    [
                        'exam_attempt_id' => $attempt->id,
                        'exam_question_id' => $examQuestion->id,
                    ],
                    [
                        'objective_question_id' => $question->id,
                        'selected_option_ids' => $selectedIds->isEmpty() ? null : $selectedIds->all(),
                        'is_correct' => $isCorrect,
                        'awarded_marks' => $awardedMarks,
                        'metadata' => $metadata ?: null,
                    ]
                );

                $score += $awardedMarks;
            }

            $subjectScores = collect($subjectTotals)
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

            $attempt->update([
                'status' => ExamAttempt::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'score' => $score,
                'auto_score' => $score,
                'total_score' => $score,
                'subject_scores' => $subjectScores,
                'percentage' => $attempt->exam->total_marks > 0
                    ? round(($score / (float) $attempt->exam->total_marks) * 100, 2)
                    : 0,
            ]);
        });

        $autoSubmit = $request->boolean('auto_submit');
        $message = $autoSubmit
            ? 'Time elapsed. Your answers were submitted automatically.'
            : 'Exam submitted successfully.';
        $redirectUrl = route('candidate.exams.index');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => $redirectUrl,
            ]);
        }

        NotificationService::SUCCESS($message);

        return redirect($redirectUrl);
    }

    public function autosave(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $userId = Auth::id();
        if (! $userId || $attempt->user_id !== $userId) {
            abort(403);
        }

        if ($attempt->status !== ExamAttempt::STATUS_IN_PROGRESS) {
            return response()->json([
                'saved' => false,
                'message' => 'This attempt is no longer active.',
            ], 422);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'answers' => ['nullable', 'array'],
            'answers.*.selected_option_ids' => ['nullable', 'array'],
            'answers.*.selected_option_ids.*' => ['nullable', 'integer'],
        ]);

        $questionId = (int) $validated['question_id'];

        $attempt->loadMissing(['exam.questions.question']);
        $examQuestion = $attempt->exam->questions->firstWhere('id', $questionId);

        if (! $examQuestion) {
            return response()->json([
                'saved' => false,
                'message' => 'Question not found for this attempt.',
            ], 404);
        }

        $payload = $request->input("answers.$questionId", []);
        $selectedIds = collect(\App\Models\ObjectiveResponse::normalizeSelectedOptionIds($payload['selected_option_ids'] ?? []))
            ->unique()
            ->values();

        $selectedLabels = \App\Models\ObjectiveResponse::resolveSelectedOptionLabels(
            $examQuestion->question,
            $selectedIds->all()
        );
        $metadata = [];
        if ($selectedIds->isNotEmpty()) {
            $metadata['selected_option_ids'] = $selectedIds->all();
        }
        if (! empty($selectedLabels)) {
            $metadata['selected_option_labels'] = $selectedLabels;
        }

        ObjectiveResponse::query()->updateOrCreate(
            [
                'exam_attempt_id' => $attempt->id,
                'exam_question_id' => $examQuestion->id,
            ],
            [
                'objective_question_id' => $examQuestion->question?->id,
                'selected_option_ids' => $selectedIds->isEmpty() ? null : $selectedIds->all(),
                'is_correct' => false,
                'awarded_marks' => 0,
                'metadata' => $metadata ?: null,
            ]
        );

        return response()->json([
            'saved' => true,
            'updated_questions' => [$questionId],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function authorizeExamAccess(Exam $exam, int $candidateId): void
    {
        $departmentIds = $this->candidateDepartmentIds($candidateId);

        $hasAccess = ! empty($departmentIds)
            && $exam->departments()->whereIn('departments.id', $departmentIds)->exists();

        abort_unless($hasAccess, 403, 'This exam is not available to your department.');
    }

    /**
     * @return array<int, int>
     */
    private function candidateDepartmentIds(int $candidateId): array
    {
        $ids = Department::query()
            ->whereHas('users', fn ($query) => $query->where('users.id', $candidateId))
            ->pluck('departments.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! empty($ids)) {
            return $ids;
        }

        /** @var Collection<int, Department> $defaults */
        $defaults = cache()->remember('default_department_ids', 300, fn () => Department::query()
            ->where('is_default', true)
            ->get(['id']));

        return $defaults->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
