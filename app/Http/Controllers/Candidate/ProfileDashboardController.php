<?php

declare(strict_types=1);

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProfileDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $candidate = $request->user();

        abort_if($candidate === null, 403);

        $attemptQuery = ExamAttempt::query()->where('user_id', $candidate->id);

        $totalAttempts = (clone $attemptQuery)->count();
        $publishedAttempts = (clone $attemptQuery)
            ->where('status', ExamAttempt::STATUS_PUBLISHED)
            ->count();
        $bestScore = (clone $attemptQuery)
            ->where('status', ExamAttempt::STATUS_PUBLISHED)
            ->max('score') ?? 0;
        $recentAttempts = (clone $attemptQuery)
            ->with('exam.subjects')
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('candidate.profile.dashboard', [
            'candidate' => $candidate,
            'summary' => [
                'total_attempts' => $totalAttempts,
                'published_attempts' => $publishedAttempts,
                'best_score' => round((float) $bestScore, 1),
            ],
            'recentAttempts' => $recentAttempts,
        ]);
    }
}
