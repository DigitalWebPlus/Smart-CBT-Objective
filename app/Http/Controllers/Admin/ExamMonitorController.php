<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamMonitorController extends Controller
{
    public function index(Request $request): View
    {
        $examId = $request->integer('exam_id') ?: null;
        $candidateQuery = trim((string) $request->input('candidate', ''));

        $baseQuery = ExamAttempt::query()
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS);

        if ($examId) {
            $baseQuery->where('exam_id', $examId);
        }

        if ($candidateQuery !== '') {
            $baseQuery->whereHas('candidate', function ($builder) use ($candidateQuery): void {
                $builder->where('name', 'like', "%{$candidateQuery}%")
                    ->orWhere('email', 'like', "%{$candidateQuery}%")
                    ->orWhere('registration_number', 'like', "%{$candidateQuery}%");
            });
        }

        $statsBaseQuery = clone $baseQuery;

        $monitorStats = [
            'in_progress_total' => (clone $statsBaseQuery)->count(),
            'active_exams' => (clone $statsBaseQuery)->distinct('exam_id')->count('exam_id'),
            'active_candidates' => (clone $statsBaseQuery)->distinct('user_id')->count('user_id'),
            'average_logins' => round((float) ((clone $statsBaseQuery)->avg('login_count') ?? 0), 1),
        ];

        $attempts = (clone $baseQuery)
            ->with([
                'candidate:id,name,email,registration_number',
                'exam' => static fn ($builder) => $builder->withCount('questions'),
            ])
            ->withCount('responses')
            ->latest('started_at')
            ->paginate(20)
            ->withQueryString();

        $exams = Exam::query()
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.monitor-exams.index', compact('attempts', 'exams', 'examId', 'candidateQuery', 'monitorStats'));
    }
}
