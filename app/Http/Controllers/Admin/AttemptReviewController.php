<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttemptReviewController extends Controller
{
    public function index(Request $request): View
    {
        abort(404);

        $query = ExamAttempt::query()
            ->with(['exam.subjects', 'candidate'])
            ->latest();

        if ($status !== '') {
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

        $exams = Exam::query()->orderBy('title')->get(['id', 'title']);
        $statusCounts = ExamAttempt::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.attempts.index', compact(
            'attempts',
            'exams',
            'statusCounts',
            'status',
            'examId',
            'candidateQuery'
        ));
    }
}
