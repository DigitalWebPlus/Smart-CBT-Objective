<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ObjectiveQuestion;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $now = now();

        $totalAttempts = ExamAttempt::query()->count();
        $completedAttempts = ExamAttempt::query()
            ->whereIn('status', [
                ExamAttempt::STATUS_SUBMITTED,
                ExamAttempt::STATUS_GRADED,
                ExamAttempt::STATUS_PUBLISHED,
            ])
            ->count();

        $completionRate = $totalAttempts > 0
            ? round(($completedAttempts / $totalAttempts) * 100, 1)
            : 0.0;

        $passRateRaw = ExamAttempt::query()
            ->whereIn('status', [
                ExamAttempt::STATUS_SUBMITTED,
                ExamAttempt::STATUS_GRADED,
                ExamAttempt::STATUS_PUBLISHED,
            ])
            ->whereNotNull('percentage')
            ->selectRaw('AVG(CASE WHEN percentage >= 50 THEN 1 ELSE 0 END) as pass_rate')
            ->value('pass_rate');

        $passRate = $passRateRaw !== null
            ? round(((float) $passRateRaw) * 100, 1)
            : 0.0;

        $days = collect(range(6, 0))->map(fn (int $offset) => $now->copy()->subDays($offset));
        $trendLabels = $days->map(fn ($day) => $day->format('M d'))->values();
        $attemptTrend = $days->map(function ($day) {
            return ExamAttempt::query()
                ->whereDate('created_at', $day->toDateString())
                ->count();
        })->values();
        $submissionTrend = $days->map(function ($day) {
            return ExamAttempt::query()
                ->whereDate('submitted_at', $day->toDateString())
                ->count();
        })->values();

        $recentAttemptActivities = ExamAttempt::query()
            ->with(['candidate:id,name', 'exam:id,title'])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (ExamAttempt $attempt) {
                return [
                    'type' => 'attempt',
                    'title' => ($attempt->candidate?->name ?? 'Candidate') . ' • ' . ($attempt->exam?->title ?? 'Exam'),
                    'description' => 'Attempt status: ' . str_replace('_', ' ', $attempt->status),
                    'timestamp' => $attempt->updated_at,
                ];
            });

        $recentTicketActivities = SupportTicket::query()
            ->with('candidate:id,name')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (SupportTicket $ticket) {
                return [
                    'type' => 'ticket',
                    'title' => ($ticket->candidate?->name ?? 'Candidate') . ' • ' . $ticket->subject,
                    'description' => 'Ticket ' . $ticket->status . ' • priority ' . $ticket->priority,
                    'timestamp' => $ticket->updated_at,
                ];
            });

        $recentActivity = $recentAttemptActivities
            ->merge($recentTicketActivities)
            ->sortByDesc('timestamp')
            ->take(8)
            ->values();

        $pendingTickets = SupportTicket::query()
            ->with('candidate:id,name')
            ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_PENDING])
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get();

        $pendingReviews = ExamAttempt::query()
            ->with(['candidate:id,name', 'exam:id,title'])
            ->where('status', ExamAttempt::STATUS_SUBMITTED)
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'kpis' => [
                'subjects' => DB::table('subjects')->count(),
                'questions' => ObjectiveQuestion::query()->count(),
                'exams' => Exam::query()->count(),
                'active_exams' => Exam::query()
                    ->where('status', Exam::STATUS_PUBLISHED)
                    ->where(function ($query) use ($now) {
                        $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                    })
                    ->where(function ($query) use ($now) {
                        $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                    })
                    ->count(),
                'attempts' => $totalAttempts,
                'in_progress_attempts' => ExamAttempt::query()
                    ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
                    ->count(),
                'candidates' => User::query()->count(),
                'new_candidates_30d' => User::query()
                    ->whereDate('created_at', '>=', $now->copy()->subDays(30)->toDateString())
                    ->count(),
                'completion_rate' => $completionRate,
                'pass_rate' => $passRate,
                'open_tickets' => SupportTicket::query()
                    ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_PENDING])
                    ->count(),
                'pending_reviews' => ExamAttempt::query()
                    ->where('status', ExamAttempt::STATUS_SUBMITTED)
                    ->count(),
            ],
            'charts' => [
                'attempt_trend_labels' => $trendLabels,
                'attempt_trend_series' => $attemptTrend,
                'submission_trend_series' => $submissionTrend,
                'exam_status' => [
                    'draft' => Exam::query()->where('status', Exam::STATUS_DRAFT)->count(),
                    'published' => Exam::query()->where('status', Exam::STATUS_PUBLISHED)->count(),
                    'archived' => Exam::query()->where('status', Exam::STATUS_ARCHIVED)->count(),
                ],
            ],
            'quickActions' => [
                ['label' => 'Create Exam', 'route' => route('admin.exams.create')],
                ['label' => 'Add Candidates', 'route' => route('admin.candidates.upload')],
                ['label' => 'Question Bank', 'route' => route('admin.question-banks.create')],
                ['label' => 'Monitor Live Exams', 'route' => route('admin.monitor-exams.index')],
            ],
            'recentActivity' => $recentActivity,
            'pendingTickets' => $pendingTickets,
            'pendingReviews' => $pendingReviews,
            'generatedAt' => $now,
        ]);
    }
}
