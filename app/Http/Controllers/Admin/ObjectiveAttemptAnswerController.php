<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ObjectiveResponse;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

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

    public function show(Request $request, $exam): View
    {
        $exam = Exam::findOrFail((int) $exam);
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

            // if ($examId > 0 && $attempt->exam_id !== $examId) {
            //     abort(404);
            // }
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

    public function downloadResponses(mixed $exam, mixed $attempt): Response
    {
        $examId = $exam instanceof Exam ? $exam->id : (int) $exam;
        $attemptId = $attempt instanceof ExamAttempt ? $attempt->id : (int) $attempt;

        $examModel = Exam::query()->findOrFail($examId);
        $attemptModel = ExamAttempt::query()
            ->with([
                'candidate:id,name,email,registration_number,photo',
                'responses.question.subject',
                'responses.question.options',
            ])
            ->findOrFail($attemptId);

        // abort_if($attemptModel->exam_id !== $examModel->id, 404);

        $responseRows = $attemptModel->responses
            ->map(function (ObjectiveResponse $response): array {
                $question = $response->question;
                $selectedIds = ObjectiveResponse::normalizeSelectedOptionIds($response->selected_option_ids ?? []);
                $orderedOptions = collect($question?->options ?? [])->sortBy(function ($option) {
                    return (int) ($option->display_order ?? 0);
                })->values();
                $optionMap = $orderedOptions->keyBy('id');
                $optionLetterById = $orderedOptions
                    ->values()
                    ->mapWithKeys(function ($option, int $index): array {
                        $label = trim((string) ($option->label ?? ''));
                        if ($label !== '' && preg_match('/^[A-Za-z]$/', $label) === 1) {
                            return [(int) $option->id => strtoupper($label)];
                        }

                        return [(int) $option->id => chr(65 + $index)];
                    });
                $selectedLabels = collect($selectedIds)
                    ->map(function (int $optionId) use ($optionMap, $optionLetterById): ?string {
                        $option = $optionMap->get($optionId);
                        if (! $option) {
                            return null;
                        }

                        $alphabet = (string) ($optionLetterById->get((int) $optionId) ?? '');
                        $answerText = trim((string) ($option->description ?? ''));
                        if ($answerText === '') {
                            $rawLabel = trim((string) ($option->label ?? ''));
                            $answerText = preg_match('/^[A-Za-z]$/', $rawLabel) === 1 ? '' : $rawLabel;
                        }

                        if ($alphabet !== '' && $answerText !== '') {
                            return '(' . $alphabet . ') ' . $answerText;
                        }

                        if ($alphabet !== '') {
                            return '(' . $alphabet . ')';
                        }

                        return $answerText !== '' ? $answerText : null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'subject' => (string) ($question?->subject?->name ?? $question?->subject?->code ?? 'General'),
                    'question' => (string) ($question?->question_text ?? ''),
                    'selected_answer' => implode(' | ', $selectedLabels),
                ];
            })
            ->values();

        $siteName = (string) config('settings.site_name', config('app.name', 'CBT Objective'));
        $brandLogoDataUri = $this->imagePathToDataUri((string) config('settings.site_logo', ''));
        $candidatePhotoDataUri = $this->imagePathToDataUri((string) ($attemptModel->candidate?->photo ?? ''));

        $fileName = sprintf(
            'attempt-responses_exam-%d_attempt-%d_candidate-%d.pdf',
            $examModel->id,
            $attemptModel->id,
            (int) ($attemptModel->user_id ?? 0)
        );

        $pdf = Pdf::loadView('admin.attempts.objective.responses-pdf', [
            'exam' => $examModel,
            'attempt' => $attemptModel,
            'candidate' => $attemptModel->candidate,
            'rows' => $responseRows,
            'siteName' => $siteName,
            'brandLogoDataUri' => $brandLogoDataUri,
            'candidatePhotoDataUri' => $candidatePhotoDataUri,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }

    private function imagePathToDataUri(string $relativePath): ?string
    {
        $path = trim($relativePath);
        if ($path === '') {
            return null;
        }

        $normalizedPath = ltrim($path, '/');
        $candidates = [
            public_path($normalizedPath),
            public_path('uploads/' . $normalizedPath),
            storage_path('app/public/' . $normalizedPath),
        ];

        $absolutePath = null;
        foreach ($candidates as $candidatePath) {
            if (is_file($candidatePath) && is_readable($candidatePath)) {
                $absolutePath = $candidatePath;
                break;
            }
        }

        if ($absolutePath === null) {
            return null;
        }

        $contents = @file_get_contents($absolutePath);
        if ($contents === false) {
            return null;
        }

        $mimeType = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }
}
