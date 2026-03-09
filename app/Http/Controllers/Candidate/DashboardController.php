<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $userId = auth()->id();

        return view('candidate.dashboard', [
            'stats' => [
                'available_exams' => Exam::query()->where('status', Exam::STATUS_PUBLISHED)->count(),
                'attempts' => ExamAttempt::query()->where('user_id', $userId)->count(),
            ],
        ]);
    }
}
