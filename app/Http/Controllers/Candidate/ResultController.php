<?php

declare(strict_types=1);

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function print(Request $request, ExamAttempt $attempt): View
    {
        $candidate = $request->user();

        abort_if($candidate === null, 403);
        abort_if((int) $attempt->user_id !== (int) $candidate->id, 403);
        abort_if($attempt->status !== ExamAttempt::STATUS_PUBLISHED, 404);

        $attempt->load([
            'exam' => fn ($builder) => $builder->withSum('questions', 'marks')->with('subjects'),
            'candidate',
        ]);

        $totalMarks = $this->resolveTotalMarks($attempt);
        $score = (float) ($attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? 0);
        $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0;

        return view('candidate.results.print', compact('attempt', 'totalMarks', 'percentage'));
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
