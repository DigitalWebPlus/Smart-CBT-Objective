@extends('candidate.layouts.app')
@section('title', 'Available Exams')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <p class="text-uppercase small mb-1 text-white">Assessments</p>
                    <h1 class="h3 fw-bold mb-0">Browse upcoming and live exams</h1>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-light fw-semibold"><i class="bi bi-arrow-left me-2"></i>Back to
                    dashboard</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                @include('candidate.partials.profile-card', ['candidate' => auth()->user()])
            </div>
            <div class="col-lg-8">
                <div class="row g-4">
                    @forelse ($exams as $exam)
                        <div class="col-md-6 col-lg-6">
                    <div class="card border-0 rounded-4 glass-card h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            @php
                                $candidateAttempt = $exam->attempts->first();
                                $attemptStatus = $candidateAttempt?->status;
                                $now = now();
                                $isNotActive = $exam->starts_at && $now->lt($exam->starts_at);
                                $isExpired = $exam->ends_at && $now->gt($exam->ends_at);
                                $hasOtherInProgressAttempt = $inProgressExamId && (int) $inProgressExamId !== (int) $exam->id;
                                $attemptStatusColors = [
                                    \App\Models\ExamAttempt::STATUS_IN_PROGRESS => 'warning',
                                    \App\Models\ExamAttempt::STATUS_PENDING => 'secondary',
                                    \App\Models\ExamAttempt::STATUS_SUBMITTED => 'primary',
                                    \App\Models\ExamAttempt::STATUS_GRADED => 'primary',
                                    \App\Models\ExamAttempt::STATUS_PUBLISHED => 'success',
                                    \App\Models\ExamAttempt::STATUS_CANCELED => 'danger',
                                    \App\Models\ExamAttempt::STATUS_RETAKE => 'warning',
                                ];
                                $attemptLabel = $attemptStatus === \App\Models\ExamAttempt::STATUS_RETAKE
                                    ? 'retake required'
                                    : ($attemptStatus ? \Illuminate\Support\Str::headline($attemptStatus) : 'Yet to take exam');
                                $hasCompletedAttempt = $candidateAttempt && in_array($attemptStatus, [
                                        \App\Models\ExamAttempt::STATUS_SUBMITTED,
                                        \App\Models\ExamAttempt::STATUS_GRADED,
                                        \App\Models\ExamAttempt::STATUS_PUBLISHED,
                                    ], true);
                                $attemptStateLabel = ($attemptStatus === \App\Models\ExamAttempt::STATUS_RETAKE || $hasCompletedAttempt)
                                    ? 'Taken'
                                    : ($attemptStatus === \App\Models\ExamAttempt::STATUS_IN_PROGRESS
                                        ? 'In Progress'
                                        : 'Not Taken');
                                $attemptStateBadge = $attemptStatus === \App\Models\ExamAttempt::STATUS_RETAKE
                                    ? 'danger'
                                    : ($hasCompletedAttempt
                                        ? 'success'
                                        : ($attemptStatus === \App\Models\ExamAttempt::STATUS_IN_PROGRESS
                                            ? 'warning'
                                            : 'secondary'));
                            @endphp
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h5 class="card-title mb-0">{{ $exam->title }}</h5>
                                        <span class="badge text-bg-{{ $attemptStateBadge }} text-uppercase">{{ $attemptStateLabel }}</span>
                                    </div>
                                </div>
                                @php
                                    $statusLabel = $isExpired
                                        ? 'expired'
                                        : ($isNotActive
                                            ? 'not active'
                                            : ($exam->status === \App\Models\Exam::STATUS_PUBLISHED
                                                ? 'active'
                                                : str_replace('_', ' ', $exam->status)));
                                    $badge = $isExpired
                                        ? 'danger'
                                        : ($isNotActive
                                            ? 'primary'
                                            : ($exam->status === \App\Models\Exam::STATUS_PUBLISHED ? 'success' : 'secondary'));
                                @endphp
                                <span class="badge text-bg-{{ $badge }} text-uppercase">{{ $statusLabel }}</span>
                            </div>
                            <ul class="list-unstyled small mb-4">
                                <li class="mb-1"><i class="bi bi-calendar-event me-2 text-primary"></i>
                                    {{ optional($exam->starts_at)->format('M j, Y g:i A') ?? 'TBA' }}</li>
                                <li class="mb-1"><i class="bi bi-calendar-x me-2 text-danger"></i>
                                    {{ optional($exam->ends_at)->format('M j, Y g:i A') ?? 'TBA' }}</li>
                            </ul>
                            @if ($hasCompletedAttempt)
                                <div class="w-100 mt-auto d-flex flex-column gap-2">
                                    <span class="badge text-bg-info align-self-start">Taken</span>
                                    <button class="btn btn-outline-secondary w-100 fw-semibold" type="button" disabled>Exam already completed</button>
                                    @if ($candidateAttempt)
                                        @php
                                            $resultScore = $candidateAttempt->total_score ?? $candidateAttempt->score ?? $candidateAttempt->auto_score;
                                            $resultPercentage = $candidateAttempt->percentage;
                                            $resultStatus = $candidateAttempt->status;
                                            $resultLabel = $resultStatus === \App\Models\ExamAttempt::STATUS_PUBLISHED
                                                ? 'Result published'
                                                : 'Result not yet published';
                                            $subjectScores = collect($candidateAttempt->subject_scores ?? []);
                                            $resultTotalMarks = (float) ($exam->questions_sum_marks ?? 0);
                                            if ($resultTotalMarks <= 0) {
                                                $resultTotalMarks = (float) ($exam->total_marks ?? 0);
                                            }
                                            if ($resultTotalMarks <= 0 && $subjectScores->isNotEmpty()) {
                                                $resultTotalMarks = (float) $subjectScores->sum('max_marks');
                                            }
                                            if ($resultTotalMarks <= 0 && isset($exam->questions)) {
                                                $resultTotalMarks = (float) collect($exam->questions)->sum(function ($examQuestion) {
                                                    return (float) ($examQuestion->marks ?? 0);
                                                });
                                            }
                                            if ((is_null($resultPercentage) || (float) $resultPercentage <= 0)
                                                && ! is_null($resultScore)
                                                && $resultTotalMarks > 0) {
                                                $resultPercentage = round(((float) $resultScore / $resultTotalMarks) * 100, 2);
                                            }
                                            $subjectListHtml = '';
                                            if ($subjectScores->isNotEmpty()) {
                                                $subjectListHtml .= '<ul class="list-unstyled mb-0">';
                                                foreach ($subjectScores as $subjectScore) {
                                                    $subjectLabel = $subjectScore['subject_name'] ?? $subjectScore['subject_code'] ?? 'Subject';
                                                    $subjectScoreValue = number_format((float) ($subjectScore['total_score'] ?? 0), 2);
                                                    $subjectMaxValue = number_format((float) ($subjectScore['max_marks'] ?? 0), 2);
                                                    $subjectListHtml .= '<li class="mb-1">'
                                                        . e($subjectLabel)
                                                        . ': '
                                                        . $subjectScoreValue
                                                        . ' / '
                                                        . $subjectMaxValue
                                                        . '</li>';
                                                }
                                                $subjectListHtml .= '</ul>';
                                            } elseif ($exam->subjects->isNotEmpty()) {
                                                $subjectListHtml .= '<ul class="list-unstyled mb-0">';
                                                foreach ($exam->subjects as $subject) {
                                                    $subjectListHtml .= '<li class="mb-1">' . e($subject->name ?? $subject->code ?? 'Subject') . '</li>';
                                                }
                                                $subjectListHtml .= '</ul>';
                                            } else {
                                                $subjectListHtml = '<div>—</div>';
                                            }

                                            $resultHtml = '<div class="text-start">'
                                                . '<div class="fw-semibold mb-1">Status</div>'
                                                . '<div class="mb-3">' . e(\Illuminate\Support\Str::headline($resultStatus)) . '</div>'
                                                . '<div class="fw-semibold mb-1">Score</div>'
                                                . '<div class="mb-3">' . (is_null($resultScore) ? '—' : number_format((float) $resultScore, 2)) . '</div>'
                                                . '<div class="fw-semibold mb-1">Percentage</div>'
                                                . '<div class="mb-3">' . (is_null($resultPercentage) ? '—' : number_format((float) $resultPercentage, 2) . '%') . '</div>'
                                                . '<div class="fw-semibold mb-1">Subjects</div>'
                                                . $subjectListHtml
                                                . '</div>';
                                        @endphp
                                        @if ($exam->allowsResultView())
                                            <button type="button" class="btn btn-primary w-100 fw-semibold" data-result-popup
                                                data-result-title="{{ $resultLabel }}"
                                                data-result-html="{{ $resultHtml }}">
                                                Check result
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-primary w-100 fw-semibold" data-review-disabled
                                                data-review-message="Result viewing is disabled for this exam.">
                                                Check result
                                            </button>
                                        @endif
                                        @if ($exam->allowsReview())
                                            <a href="{{ route('candidate.exam-attempts.show', $candidateAttempt) }}#question-review" class="btn btn-outline-primary w-100 fw-semibold">Review exam</a>
                                        @endif
                                        @if ($candidateAttempt->status === \App\Models\ExamAttempt::STATUS_PUBLISHED)
                                            <a href="{{ route('candidate.results.print', $candidateAttempt) }}" class="btn btn-outline-dark w-100 fw-semibold">
                                                Print result
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            @elseif ($candidateAttempt && $attemptStatus === \App\Models\ExamAttempt::STATUS_IN_PROGRESS)
                                <a href="{{ route('candidate.exam-attempts.show', $candidateAttempt) }}" class="btn btn-warning w-100 mt-auto fw-semibold">
                                    Continue Attempt
                                </a>
                            @else
                                <div class="w-100 mt-auto d-flex flex-column gap-2">
                                    @if ($attemptLabel)
                                        <span class="badge text-bg-{{ $attemptStatusColors[$attemptStatus] ?? 'secondary' }} text-uppercase align-self-start">Status: {{ $attemptLabel }}</span>
                                    @endif
                                    @php
                                        $isAttemptBlocked = in_array($attemptStatus, [
                                            \App\Models\ExamAttempt::STATUS_CANCELED,
                                            \App\Models\ExamAttempt::STATUS_RETAKE,
                                        ], true);
                                        $viewAttemptBlocked = $isAttemptBlocked || ! $exam->allowsReview() || ! $candidateAttempt;
                                        if (! $candidateAttempt) {
                                            $viewAttemptMessage = 'No attempt is available to view yet.';
                                        } elseif ($isAttemptBlocked) {
                                            $viewAttemptMessage = 'This attempt is not available to view.';
                                        } else {
                                            $viewAttemptMessage = 'Exam review is disabled for this assessment.';
                                        }
                                        $examActionBlocked = $isNotActive
                                            || $isExpired
                                            || $attemptStatus === \App\Models\ExamAttempt::STATUS_CANCELED
                                            || $attemptStatus === \App\Models\ExamAttempt::STATUS_RETAKE;
                                        $requiresStartAction = ! $candidateAttempt || $attemptStatus === \App\Models\ExamAttempt::STATUS_RETAKE;
                                        if ($isExpired && $requiresStartAction) {
                                            $examActionMessage = 'This exam has expired. Contact support.';
                                        } elseif ($attemptStatus === \App\Models\ExamAttempt::STATUS_CANCELED && $requiresStartAction) {
                                            $examActionMessage = 'This attempt was canceled and cannot be restarted.';
                                        } elseif ($hasOtherInProgressAttempt && $requiresStartAction) {
                                            $examActionBlocked = true;
                                            $examActionMessage = 'You already have an exam in progress. Finish it before starting another.';
                                        } elseif ($examActionBlocked) {
                                            $examActionMessage = $isNotActive
                                                ? 'This exam has not started yet.'
                                                : 'Exam is not available at the moment.';
                                        } else {
                                            $examActionMessage = 'Exam is not available at the moment.';
                                        }
                                        $viewExamLabel = $attemptStatus === \App\Models\ExamAttempt::STATUS_RETAKE
                                            ? 'Retake Exam'
                                            : ($candidateAttempt ? 'View exam details' : 'Take Exam');
                                        $viewExamBlocked = $isNotActive || $isExpired;
                                        if ($viewExamBlocked && $candidateAttempt && ! $requiresStartAction) {
                                            $viewExamMessage = $isExpired
                                                ? 'This exam has expired.'
                                                : 'This exam has not started yet.';
                                        }
                                    @endphp
                                    @if ($candidateAttempt && ! $viewAttemptBlocked)
                                        <a href="{{ route('candidate.exam-attempts.show', $candidateAttempt) }}" class="btn btn-outline-secondary w-100 fw-semibold">View Attempt</a>
                                    @else
                                        <button type="button" class="btn btn-outline-secondary w-100 fw-semibold" data-review-disabled
                                            data-review-message="{{ $viewAttemptMessage }}">
                                            View Attempt
                                        </button>
                                    @endif
                                    @if ($examActionBlocked && ($requiresStartAction || $hasOtherInProgressAttempt))
                                        <button type="button" class="btn btn-primary w-100 fw-semibold" data-review-disabled
                                            data-review-message="{{ $examActionMessage }}">
                                            {{ $viewExamLabel }}
                                        </button>
                                    @elseif ($viewExamBlocked && $candidateAttempt)
                                        <button type="button" class="btn btn-primary w-100 fw-semibold" data-review-disabled
                                            data-review-message="{{ $viewExamMessage ?? 'Exam is not available at the moment.' }}">
                                            {{ $viewExamLabel }}
                                        </button>
                                    @else
                                        <a href="{{ route('candidate.exams.show', $exam) }}" class="btn btn-primary w-100 fw-semibold">{{ $viewExamLabel }}</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card border-0 rounded-4 glass-card">
                                <div class="card-body p-5 text-center">
                                    <i class="bi bi-emoji-smile display-5 text-primary mb-3"></i>
                                    <h4>No exams at the moment</h4>
                                    <p class="text-muted mb-4">When the admin team schedules a new assessment it will appear here. Check back soon.</p>
                                    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary fw-semibold">Return to dashboard</a>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $exams->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const reviewButtons = Array.from(document.querySelectorAll('[data-review-disabled]'));
            const resultButtons = Array.from(document.querySelectorAll('[data-result-popup]'));
            if (!reviewButtons.length) {
                // continue
            }

            reviewButtons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    const message = button.dataset.reviewMessage || 'Exam review is disabled for this assessment.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Unavailable',
                            text: message,
                            icon: 'info',
                            confirmButtonText: 'OK',
                        });
                        return;
                    }

                    alert(message);
                });
            });

            resultButtons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    const title = button.dataset.resultTitle || 'Result';
                    const html = button.dataset.resultHtml || '';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title,
                            html,
                            icon: undefined,
                            showCloseButton: true,
                            focusConfirm: false,
                            width: 520,
                            confirmButtonText: 'Close',
                            customClass: {
                                popup: 'text-start',
                                confirmButton: 'btn btn-primary',
                                closeButton: 'btn-close',
                            },
                            buttonsStyling: false,
                        });
                        return;
                    }

                    alert(title);
                });
            });
        });
    </script>
@endpush
