<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ResultManagementController extends Controller
{
    public function index(Request $request): View
    {
        $examId = $request->integer('exam_id') ?: null;
        $candidateQuery = trim((string) $request->input('candidate', ''));

        $query = ExamAttempt::query()
            ->with([
                'exam' => fn ($builder) => $builder->withSum('questions', 'marks'),
                'candidate',
            ])
            ->where('status', ExamAttempt::STATUS_PUBLISHED)
            ->latest();

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

        $attempts->getCollection()->transform(function (ExamAttempt $attempt): ExamAttempt {
            $attempt->computed_total_marks = $this->resolveTotalMarks($attempt);
            $score = (float) ($attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? 0);
            $attempt->computed_percentage = (float) ($attempt->computed_total_marks > 0
                ? round(($score / $attempt->computed_total_marks) * 100, 2)
                : 0);

            return $attempt;
        });

        $publishedCount = ExamAttempt::query()
            ->where('status', ExamAttempt::STATUS_PUBLISHED)
            ->count();

        $exams = Exam::query()
            ->where('exam_type', Exam::TYPE_OBJECTIVE)
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.results.index', compact(
            'attempts',
            'publishedCount',
            'exams',
            'examId',
            'candidateQuery'
        ));
    }

    public function print(ExamAttempt $attempt): View
    {
        $attempt->load([
            'exam' => fn ($builder) => $builder->withSum('questions', 'marks')->with('subjects'),
            'candidate',
        ]);

        $totalMarks = $this->resolveTotalMarks($attempt);
        $score = (float) ($attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? 0);
        $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0;

        return view('admin.results.print', compact('attempt', 'totalMarks', 'percentage'));
    }

    private function resolveTotalMarks(ExamAttempt $attempt): float
    {
        $total = (float) ($attempt->exam?->questions_sum_marks ?? 0);

        if ($total <= 0) {
            $total = (float) ($attempt->exam?->total_marks ?? 0);
        }

        if ($total <= 0) {
            $total = (float) collect($attempt->subject_scores ?? [])->sum('max_marks');
        }

        if ($total <= 0 && $attempt->exam_id) {
            $total = (float) ExamQuestion::query()
                ->where('exam_id', $attempt->exam_id)
                ->sum('marks');
        }

        return $total;
    }
}
