@php
    use App\Models\ExamAttempt;
    use App\Models\ObjectiveQuestion;
    use Illuminate\Support\Str;

    $questions = $attempt->exam->questions->sortBy('display_order');
    $objectiveResponses = $attempt->objectiveResponses->keyBy('exam_question_id');
    $deadline = optional($attempt->started_at)?->addMinutes($attempt->exam->duration_minutes ?? 0);
    $serverNow = now();
    $isReadOnly = $attempt->status !== ExamAttempt::STATUS_IN_PROGRESS;
    $canViewResult = $attempt->exam->allowsResultView();
    $isResultPublished = $canViewResult && $attempt->status === ExamAttempt::STATUS_PUBLISHED;
    $isCanceled = $attempt->status === ExamAttempt::STATUS_CANCELED;
    $isRetake = $attempt->status === ExamAttempt::STATUS_RETAKE;
    $isPendingVerification = $attempt->status === ExamAttempt::STATUS_SUBMITTED;
    $subjectCollection = $attempt->exam->subjects->isNotEmpty()
        ? $attempt->exam->subjects
        : $questions
            ->map(fn ($examQuestion) => $examQuestion->question?->subject)
            ->filter()
            ->unique('id')
            ->values();
    $questionsBySubject = $questions->groupBy(function ($examQuestion) {
        return $examQuestion->question?->subject_id ? (string) $examQuestion->question->subject_id : 'unassigned';
    });
    $subjectAnsweredCounts = [];
    $answeredCount = 0;
    foreach ($questions as $examQuestion) {
        $subjectKey = $examQuestion->question?->subject_id ? (string) $examQuestion->question->subject_id : 'unassigned';
        $objectiveResponse = $objectiveResponses->get($examQuestion->id);
        $selectedOptions = $objectiveResponse?->selected_option_ids;
        $hasObjectiveResponse =
            $objectiveResponse &&
            ((is_array($selectedOptions) && count($selectedOptions) > 0) ||
                (!is_array($selectedOptions) && !empty($selectedOptions)));
        if ($hasObjectiveResponse) {
            $subjectAnsweredCounts[$subjectKey] = ($subjectAnsweredCounts[$subjectKey] ?? 0) + 1;
            $answeredCount += 1;
        }
    }
    $subjectTabs = $subjectCollection
        ->map(function ($subject) use ($questionsBySubject, $subjectAnsweredCounts) {
            $subjectKey = (string) $subject->id;
            return [
                'id' => $subjectKey,
                'name' => $subject->name,
                'code' => $subject->code,
                'question_count' => $questionsBySubject->get($subjectKey)?->count() ?? 0,
                'answered_count' => $subjectAnsweredCounts[$subjectKey] ?? 0,
            ];
        })
        ->values();
    $unassignedQuestions = $questionsBySubject->get('unassigned') ?? collect();
    if ($unassignedQuestions->isNotEmpty()) {
        $subjectTabs->push([
            'id' => 'unassigned',
            'name' => 'General',
            'code' => 'GEN',
            'question_count' => $unassignedQuestions->count(),
            'answered_count' => $subjectAnsweredCounts['unassigned'] ?? 0,
        ]);
    }
    if ($subjectTabs->isEmpty() && $questions->isNotEmpty()) {
        $subjectTabs->push([
            'id' => 'all',
            'name' => 'All Questions',
            'code' => 'ALL',
            'question_count' => $questions->count(),
            'answered_count' => $questions->count() ? $answeredCount : 0,
        ]);
    }
    $fallbackSubjectKey = $subjectTabs->contains(fn($tab) => $tab['id'] === 'all') ? 'all' : 'unassigned';
    $subjectScores = collect($attempt->subject_scores ?? [])->map(function ($score) {
        return array_merge(
            [
                'subject_id' => null,
                'subject_code' => 'GEN',
                'subject_name' => 'General',
                'max_marks' => 0,
                'auto_score' => 0,
                'manual_score' => 0,
                'total_score' => 0,
                'percentage' => 0,
                'grade_letter' => null,
                'grade_remark' => null,
            ],
            $score ?? [],
        );
    });
    $overallSummary = [
        'total_score' => (float) ($attempt->total_score ?? 0),
        'max_marks' => (float) ($attempt->exam->total_marks ?? 0),
        'percentage' => (float) ($attempt->percentage ?? 0),
        'grade_letter' => $attempt->grade_letter,
        'grade_remark' => $attempt->grade_remark,
    ];

    $formatDuration = function (?int $seconds): ?string {
        if ($seconds === null) {
            return null;
        }
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    };

    $unansweredCount = max($questions->count() - $answeredCount, 0);
    $timerDuration = $attempt->exam->duration_minutes ?? null;
    $remainingSeconds = $deadline ? max($serverNow->diffInSeconds($deadline, false), 0) : null;
    if ($remainingSeconds !== null && $timerDuration) {
        $remainingSeconds = min($remainingSeconds, $timerDuration * 60);
    }
    $timeUsedSeconds = null;
    if ($timerDuration && $remainingSeconds !== null) {
        $timeUsedSeconds = max($timerDuration * 60 - $remainingSeconds, 0);
    } elseif ($attempt->started_at) {
        $timeUsedSeconds = $attempt->started_at->diffInSeconds($serverNow);
    }
    $formattedRemaining = $formatDuration($remainingSeconds);
    $formattedTimeUsed = $formatDuration($timeUsedSeconds);
@endphp

@extends('candidate.layouts.app')

@section('title', 'Exam Attempt')

@push('styles')
    <style>
        .subject-overview-card {
            position: relative;
            padding-top: 3.5rem;
        }

        .subject-overview-card .subject-switcher-inline {
            width: 100%;
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            padding-top: 0.65rem;
            margin-top: 0.65rem;
            margin-bottom: -0.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            justify-content: flex-start;
        }

        .subject-overview-card .subject-switcher-inline .subject-tab.active {
            border-color: #2563eb;
            box-shadow: 0 20px 35px rgba(37, 99, 235, 0.18);
            color: #2563eb;
        }

        .question-nav .btn {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-weight: 600;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .question-nav .btn.unanswered {
            background-color: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.45);
            color: #b91c1c;
        }

        .question-nav .btn.unanswered:hover {
            background-color: rgba(239, 68, 68, 0.2);
        }

        .question-nav .btn.answered {
            background-color: rgba(16, 185, 129, 0.18);
            border-color: rgba(16, 185, 129, 0.6);
            color: #0f766e;
        }

        .question-nav .btn.active-question {
            background-color: #2563eb;
            border-color: #2563eb;
            color: #fff;
            box-shadow: 0 12px 20px rgba(37, 99, 235, 0.25);
        }

        .question-card {
            border-radius: 22px;
            border: none;
            box-shadow: 0 24px 45px rgba(15, 23, 42, 0.08);
            display: none;
        }

        .question-card.active {
            display: block;
        }

        .question-card .form-check {
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            cursor: pointer;
        }

        .question-card .form-check:hover {
            border-color: rgba(37, 99, 235, 0.45);
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.22);
            background-color: rgba(37, 99, 235, 0.04);
        }

        .question-card .form-check .form-check-label {
            cursor: pointer;
        }

        .question-progress {
            border-radius: 22px;
            background: rgba(15, 23, 42, 0.04);
            padding: 1.25rem 1.5rem;
        }

        .autosave-status {
            min-height: 1.2rem;
        }

        .question-controls .btn {
            min-width: 140px;
        }

        .subject-score-card {
            border-radius: 20px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.06);
            background: #fff;
        }

        .subject-score-card .badge {
            letter-spacing: 0.05em;
        }

        .general-summary-card {
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(15, 118, 255, 0.1), rgba(99, 102, 241, 0.08));
        }

        .general-summary-card .summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 1rem;
        }

        .summary-box {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 18px;
            padding: 1.25rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.35rem;
        }

        .summary-box.summary-box-image {
            align-items: center;
            text-align: center;
            gap: 0.35rem;
            padding: 0.65rem;
        }

        .summary-box-image .summary-illustration {
            width: 100%;
            min-height: 110px;
            border-radius: 12px;
            background: #fff;
            border: 1px dashed rgba(15, 23, 42, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.2rem;
            box-shadow: none;
            overflow: hidden;
        }

        .summary-box-image .summary-illustration.has-photo {
            background: #fff;
            padding: 0.05rem;
        }

        .summary-box-image .summary-illustration.has-photo .student-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }

        .summary-box-image .summary-illustration.no-photo svg {
            width: 85%;
            max-width: 150px;
            height: auto;
        }

        .summary-box-student-details .student-name {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }

        .summary-box-student-details .student-registration {
            color: #475569;
            letter-spacing: 0.08em;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .summary-box h4 {
            font-weight: 700;
            margin-bottom: 0;
            color: #0f172a;
        }

        .summary-box small {
            color: #64748b;
        }

        .summary-box .timer-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-box .timer-value.timer-countdown {
            color: #dc2626;
        }

        .summary-box .timer-value.timer-used {
            color: #2563eb;
        }

        .summary-box .timer-label {
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
        }

        .summary-box .timer-subtext {
            font-size: 0.9rem;
            color: #475569;
        }

        .subject-switcher .subject-tab {
            border-radius: 14px;
            padding: 0.4rem 0.75rem;
            min-width: auto;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: #fff;
            color: #0f172a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .subject-switcher .subject-tab:hover {
            color: #0f172a;
            background: #fff;
        }

        .subject-switcher .subject-tab.active {
            border-color: #0f76ff;
            box-shadow: 0 10px 20px rgba(15, 118, 255, 0.1);
        }

        .subject-tab .subject-meta {
            font-size: 0.85rem;
        }

        .subject-tab .subject-count {
            background: rgba(15, 118, 255, 0.12);
            color: #0f76ff;
            border-radius: 999px;
            padding: 0.05rem 0.45rem;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 1.5rem;
            text-align: center;
        }

        .subject-tab .subject-answered {
            background: rgba(16, 185, 129, 0.15);
            color: #0f766e;
            border-radius: 999px;
            padding: 0.05rem 0.45rem;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 1.5rem;
            text-align: center;
        }

        .subject-switcher .subject-tab:hover .subject-count {
            color: #0f76ff;
        }

        .subject-switcher .subject-tab:hover .subject-answered {
            color: #0f766e;
        }

        .subject-switcher .subject-tab.active .subject-count {
            background: #0f76ff;
            color: #fff;
        }

        .subject-switcher .subject-tab.active .subject-answered {
            background: #10b981;
            color: #fff;
        }

        .question-card.subject-hidden {
            display: none !important;
        }

        .subject-empty {
            border-radius: 18px;
            padding: 1.5rem;
        }

        @media (max-width: 991.98px) {
            .subject-overview-card .subject-switcher-inline {
                flex-direction: column;
            }

            .subject-overview-card .subject-switcher-inline .subject-tab {
                width: 100%;
                text-align: center;
                border-radius: 14px;
            }
        }

        @media (max-width: 1199.98px) {
            .general-summary-card .summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .general-summary-card .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .general-summary-card .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="row g-4">
            <div class="col-12">
                <div class="glass-card bg-primary text-white px-4 px-lg-5 py-3 py-lg-3 rounded-4 d-flex flex-column gap-3 subject-overview-card">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 w-100">
                        <div>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h1 class="h4 fw-bold mb-1">{{ $attempt->exam->title }}</h1>
                                <span class="badge text-bg-warning text-uppercase">{{ Str::headline($attempt->exam->exam_type) }}</span>
                            </div>

                        </div>
                        @unless ($isReadOnly)
                            <div class="d-flex justify-content-lg-end ms-lg-auto w-100 w-lg-auto">
                                <button type="submit" form="exam-submit-form" class="btn btn-danger px-4 px-lg-5">End Exam</button>
                            </div>
                        @endunless
                    </div>
                    @if ($subjectTabs->isNotEmpty())
                        <div class="subject-switcher subject-switcher-inline" aria-label="Switch subject">
                            @foreach ($subjectTabs as $tab)
                                <button type="button" class="btn subject-tab {{ $loop->first ? 'active' : '' }}"
                                    data-subject-tab data-subject-id="{{ $tab['id'] }}"
                                    data-subject-name="{{ $tab['name'] }}"
                                    data-question-count="{{ $tab['question_count'] ?? 0 }}" title="{{ $tab['name'] }}">
                                    <span class="subject-name">{{ $tab['name'] }}</span>
                                    <span class="subject-count">{{ $tab['question_count'] ?? 0 }}</span>
                                    <span class="subject-answered" title="Answered">{{ $tab['answered_count'] ?? 0 }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1" id="question-review">
            <div class="col-12">
                @if (session('status'))
                    <div class="alert alert-success rounded-4 shadow-sm mb-4">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($isReadOnly)
                    @if ($isCanceled)
                        <div class="alert alert-danger rounded-4 shadow-sm mb-4">
                            This attempt was canceled. No result will be issued for this exam.
                        </div>
                    @elseif ($isRetake)
                        <div class="alert alert-warning rounded-4 shadow-sm mb-4">
                            This attempt requires a retake. Your previous attempt will not be graded.
                            <div class="mt-3">
                                <form method="POST" action="{{ route('candidate.exams.attempts.start', $attempt->exam) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">Start retake</button>
                                </form>
                            </div>
                        </div>
                    @elseif ($isPendingVerification)
                        <div class="alert alert-info rounded-4 shadow-sm mb-4">
                                @if ($canViewResult)
                                    Your attempt has been submitted and is pending verification. Results will appear once published.
                                @else
                                    Your attempt has been submitted. Result visibility is disabled for this exam.
                                @endif
                        </div>
                    @else
                        <div class="alert alert-success rounded-4 shadow-sm mb-4">
                                @if ($canViewResult)
                                    Results have been verified. You can review your responses below.
                                @else
                                    You can review your responses below. Result visibility is disabled for this exam.
                                @endif
                        </div>
                    @endif
                @endif

                <form method="POST" action="{{ route('candidate.exam-attempts.submit', $attempt, false) }}" class="question-form" id="exam-submit-form"
                    data-swal-title="End exam now?"
                    data-swal-confirm="Your answers will be submitted and you won't be able to make changes."
                    data-swal-icon="warning"
                    data-swal-confirm-button="Yes, end exam"
                    data-swal-cancel-button="Keep working"
                    data-autosave-endpoint="{{ route('candidate.exam-attempts.autosave', $attempt, false) }}"
                    data-autosave-enabled="{{ $isReadOnly ? 'false' : 'true' }}">
                    @csrf
                    <input type="hidden" name="auto_submit" value="0" data-auto-submit-flag>

                    @if ($isResultPublished && $subjectScores->isNotEmpty())
                        <div class="row g-3 mb-4">
                            @foreach ($subjectScores as $score)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="subject-score-card p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span
                                                class="badge text-bg-primary text-uppercase">{{ $score['subject_code'] ?? 'GEN' }}</span>
                                            <span
                                                class="badge text-bg-{{ $score['grade_letter'] ?? null ? 'success' : 'secondary' }}">
                                                {{ $score['grade_letter'] ?? 'Pending' }}
                                            </span>
                                        </div>
                                        <h5 class="mb-1">{{ $score['subject_name'] ?? 'Subject' }}</h5>
                                        <p class="mb-1 fw-semibold">
                                            {{ number_format($score['total_score'] ?? 0, 2) }}
                                            /
                                            {{ number_format($score['max_marks'] ?? 0, 2) }}
                                        </p>
                                        <small class="text-muted">
                                            {{ number_format($score['percentage'] ?? 0, 2) }}%
                                            &middot;
                                            {{ $score['grade_remark'] ?? 'Awaiting grading' }}
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @php
                        $candidatePhotoPath = $attempt->candidate?->photo;
                        $candidatePhotoUrl = $candidatePhotoPath ? '/' . ltrim($candidatePhotoPath, '/') : null;
                        $attemptLoginIps = collect($attempt->login_ips ?? [])
                            ->filter(fn ($value) => filled($value))
                            ->unique()
                            ->values();
                        $candidateIpFallback = collect([request()->ip()])->first(fn ($value) => filled($value));
                        $attemptLoginCount = $attempt->login_count ?? null;
                    @endphp

                    <div class="general-summary-card card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="summary-grid">
                                <div class="summary-box summary-box-image"
                                    aria-label="{{ $candidatePhotoUrl ? 'Student profile photo' : 'No student photo available' }}">
                                    <div class="summary-illustration {{ $candidatePhotoUrl ? 'has-photo' : 'no-photo' }}">
                                        @if ($candidatePhotoUrl)
                                            <img src="{{ $candidatePhotoUrl }}"
                                                alt="{{ $attempt->candidate?->name ?? 'Student' }} photo"
                                                class="student-photo" loading="lazy">
                                        @else
                                            @include('candidate.exams.partials.candidate-writing-illustration')
                                        @endif
                                    </div>
                                </div>
                                <div class="summary-box" data-summary-timer
                                    data-server-now="{{ $serverNow->timestamp }}"
                                    @if ($deadline)
                                        data-deadline="{{ $deadline->timestamp }}"
                                    @endif
                                    @if ($timerDuration)
                                        data-timer-duration-seconds="{{ $timerDuration * 60 }}"
                                    @endif
                                    @if ($remainingSeconds !== null)
                                        data-timer-remaining-seconds="{{ $remainingSeconds }}"
                                    @endif
                                    @if ($timeUsedSeconds !== null)
                                        data-timer-used-seconds="{{ $timeUsedSeconds }}"
                                    @endif
                                    data-auto-submit-enabled="{{ $timerDuration && ! $isReadOnly ? 'true' : 'false' }}"
                                    @if ($timerDuration && ! $isReadOnly)
                                        data-auto-submit-redirect="{{ route('candidate.exam-attempts.show', $attempt) }}"
                                        data-auto-submit-title="Time is up!"
                                        data-auto-submit-message="Your allocated time has elapsed. We're submitting your answers now."
                                        data-auto-submit-success-title="Exam submitted"
                                        data-auto-submit-success-message="Your responses have been saved automatically."
                                        data-auto-submit-confirm-text="View result"
                                        data-auto-submit-error-title="Submission issue"
                                        data-auto-submit-error-message="We couldn't confirm the automatic submission. We'll retry now."
                                    @endif>

                                    <div class="timer-label">Time remaining</div>
                                    <div class="timer-value timer-countdown" data-timer-remaining>
                                        {{ $formattedRemaining ?? 'N/A' }}</div>
                                    <small class="timer-subtext">
                                        {{ $timerDuration ? $timerDuration . ' mins total' : 'No timer configured' }}
                                    </small>
                                </div>
                                <div class="summary-box">
                                    <div class="timer-label">Time used</div>
                                    <div class="timer-value timer-used" data-timer-used>
                                        {{ $formattedTimeUsed ?? 'N/A' }}
                                    </div>
                                    <small class="timer-subtext">
                                        {{ $timerDuration ? 'Out of ' . $timerDuration . ' mins' : 'Updates while you work' }}
                                    </small>
                                </div>
                                <div class="summary-box summary-box-student-details">
                                    <p class="text-muted text-uppercase small mb-1">Student</p>
                                    <h4 class="student-name mb-0">{{ $attempt->candidate?->name ?? 'Student' }}</h4>
                                    <small class="student-registration">
                                        {{ $attempt->candidate?->registration_number ?? 'Registration unavailable' }}
                                    </small>
                                </div>
                                <div class="summary-box">
                                    <p class="text-muted text-uppercase small mb-1">IP address</p>
                                    @if ($attemptLoginIps->isNotEmpty())
                                        <div class="text-break fw-semibold">
                                            @foreach ($attemptLoginIps as $ipAddress)
                                                <div>{{ $ipAddress }}</div>
                                            @endforeach
                                        </div>
                                        <small>All sign-in IPs during this attempt</small>
                                    @else
                                        <h4 class="text-break">{{ $candidateIpFallback ?? 'Not recorded' }}</h4>
                                        <small>Captured when you signed in</small>
                                    @endif
                                </div>
                                <div class="summary-box">
                                    <p class="text-muted text-uppercase small mb-1">Login count</p>
                                    <h4>
                                        {{ $attemptLoginCount !== null ? number_format((int) $attemptLoginCount) : 'Not tracked' }}
                                    </h4>
                                    <small>Total sign-ins on record</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning subject-empty d-none" data-subject-empty>
                        No questions are available for this subject yet. Please pick another subject.
                    </div>


                    <div class="question-stack">
                        @php $subjectCounters = []; @endphp
                        @foreach ($questions as $examQuestion)
                            @php
                                $question = $examQuestion->question;
                                $questionSubject = $question?->subject;
                                $questionSubjectKey = $question?->subject_id
                                    ? (string) $question->subject_id
                                    : $fallbackSubjectKey;
                                $questionSubjectCounter = $subjectCounters[$questionSubjectKey] =
                                    ($subjectCounters[$questionSubjectKey] ?? 0) + 1;
                            @endphp
                            <div class="card question-card {{ $loop->first ? 'active' : '' }}"
                                id="question-{{ $examQuestion->id }}" data-question-id="{{ $examQuestion->id }}"
                                data-question-index="{{ $loop->index }}" data-subject-id="{{ $questionSubjectKey }}"
                                data-question-number="{{ $questionSubjectCounter }}">
                                <div class="card-body p-4 p-lg-5">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <p class="text-muted text-uppercase small mb-1">Question
                                                {{ $questionSubjectCounter }}</p>
                                            <h5 class="mb-2">{{ $question?->question_text }}</h5>
                                            <p class="text-muted mb-0">{{ $examQuestion->marks }} marks ·
                                                <span class="fw-semibold">{{ $questionSubject?->name ?? 'General' }}</span>
                                            </p>
                                        </div>
                                        <span class="badge text-bg-primary">
                                            Objective
                                        </span>
                                    </div>

                                    @if ($question?->image_path)
                                        <div class="mb-4">
                                            <img src="{{ asset($question->image_path) }}"
                                                alt="Question illustration" class="img-fluid rounded">
                                        </div>
                                    @endif

                                    <input type="hidden" name="answers[{{ $examQuestion->id }}][exam_question_id]"
                                        value="{{ $examQuestion->id }}">

                                    @php
                                        $inputType =
                                            $question?->question_type === ObjectiveQuestion::TYPE_MMA
                                                ? 'checkbox'
                                                : 'radio';
                                        $optionName = "answers[{$examQuestion->id}][selected_option_ids][]";
                                        $selectedOptions = collect(
                                            $objectiveResponses->get($examQuestion->id)?->selected_option_ids ?? [],
                                        )
                                            ->map(fn($id) => (int) $id)
                                            ->all();
                                        $options = collect($question?->options ?? [])->unique('id')->values();
                                        $optionLabel = function (int $index): string {
                                            $base = 26;
                                            $label = '';
                                            $current = $index + 1;

                                            while ($current > 0) {
                                                $current -= 1;
                                                $label = chr(65 + ($current % $base)) . $label;
                                                $current = intdiv($current, $base);
                                            }

                                            return $label;
                                        };
                                    @endphp
                                    <div class="vstack gap-3">
                                        @foreach ($options as $option)
                                            <div class="form-check border rounded-4 p-3 position-relative">
                                                <input class="form-check-input question-input"
                                                    type="{{ $inputType }}" name="{{ $optionName }}"
                                                    id="question-{{ $examQuestion->id }}-option-{{ $option->id }}"
                                                    value="{{ $option->id }}" data-question-id="{{ $examQuestion->id }}"
                                                    {{ in_array($option->id, $selectedOptions, true) ? 'checked' : '' }}
                                                    {{ $isReadOnly ? 'disabled' : '' }}>
                                                <label class="form-check-label ms-2"
                                                    for="question-{{ $examQuestion->id }}-option-{{ $option->id }}">
                                                    <strong>{{ $optionLabel($loop->index) }}. {{ $option->label }}</strong>
                                                    @if (! empty($option->description) && $option->description !== $option->label)
                                                        <span class="d-block text-muted">{{ $option->description }}</span>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="card glass-card border-0 rounded-4 mt-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-3" data-current-subject-name>
                                {{ $subjectTabs->isNotEmpty() ? $subjectTabs->first()['name'] : 'All questions' }}
                            </h5>
                            <div class="question-nav d-flex flex-wrap justify-content-center gap-2">
                                @php $subjectCounters = []; @endphp
                                @foreach ($questions as $examQuestion)
                                    @php
                                        $hasObjectiveResponse = $objectiveResponses->get($examQuestion->id)
                                            ?->selected_option_ids;
                                        $answered = filled($hasObjectiveResponse);
                                        $questionSubject = $examQuestion->question?->subject;
                                        $questionSubjectKey = $examQuestion->question?->subject_id
                                            ? (string) $examQuestion->question->subject_id
                                            : $fallbackSubjectKey;
                                        $questionSubjectCounter = $subjectCounters[$questionSubjectKey] =
                                            ($subjectCounters[$questionSubjectKey] ?? 0) + 1;
                                    @endphp
                                    <a href="#question-{{ $examQuestion->id }}"
                                        class="btn {{ $answered ? 'answered' : 'unanswered' }}"
                                        data-question-id="{{ $examQuestion->id }}"
                                        data-subject-id="{{ $questionSubjectKey }}"
                                        data-question-number="{{ $questionSubjectCounter }}">
                                        {{ $questionSubjectCounter }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div
                        class="question-progress d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                        <div>
                            <p class="text-muted text-uppercase small mb-1">
                                Question progress
                                <span class="text-primary ms-1" data-current-subject-name>
                                    {{ $subjectTabs->isNotEmpty() ? $subjectTabs->first()['name'] : 'All questions' }}
                                </span>
                            </p>
                            <h5 class="mb-0">
                                <span id="question-position-current">1</span>
                                /
                                <span id="question-position-total">{{ $questions->count() }}</span>
                            </h5>
                        </div>
                        <div class="question-controls d-flex flex-wrap gap-2" data-question-controls>
                            <button type="button" class="btn btn-outline-secondary" data-nav="prev">
                                <i class="bi bi-arrow-left-short"></i> Previous
                            </button>
                            <button type="button" class="btn btn-outline-warning" data-nav="skip">
                                Skip question
                            </button>
                            <button type="button" class="btn btn-primary" data-nav="next">
                                Next question
                            </button>
                        </div>
                        <div class="text-muted small autosave-status" data-autosave-status>
                            <span data-autosave-status-text>
                                {{ $isReadOnly ? 'Responses locked' : 'Autosave ready' }}
                            </span>
                            <span class="ms-1 d-none" data-autosave-status-time></span>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const navButtons = Array.from(document.querySelectorAll('.question-nav .btn'));
        const optionBoxes = Array.from(document.querySelectorAll('.question-card .form-check'));
        const inputs = document.querySelectorAll('.question-input');
        const questionCards = Array.from(document.querySelectorAll('.question-card'));
        const controls = document.querySelector('[data-question-controls]');
        const questionPosition = document.getElementById('question-position-current');
        const questionPositionTotal = document.getElementById('question-position-total');
        const subjectTabs = Array.from(document.querySelectorAll('[data-subject-tab]'));
        const subjectEmptyNotice = document.querySelector('[data-subject-empty]');
        const subjectNameEls = Array.from(document.querySelectorAll('[data-current-subject-name]'));
        const examForm = document.getElementById('exam-submit-form');
        const autoSubmitFlagInput = examForm ? examForm.querySelector('[data-auto-submit-flag]') : null;
        const autosaveEnabled = examForm ? examForm.dataset.autosaveEnabled === 'true' : false;
        const autosaveUrl = examForm ? examForm.dataset.autosaveEndpoint : null;
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;
        const autosaveStatusEl = document.querySelector('[data-autosave-status]');
        const autosaveStatusText = autosaveStatusEl ? autosaveStatusEl.querySelector('[data-autosave-status-text]') : null;
        const autosaveStatusTime = autosaveStatusEl ? autosaveStatusEl.querySelector('[data-autosave-status-time]') : null;
        const autosaveTimers = new Map();
        const AUTOSAVE_DEBOUNCE_MS = 1200;
        let currentQuestionIndex = 0;
        let currentSubjectId = subjectTabs[0]?.dataset.subjectId || null;
        let answeredMap = {};

        const setActiveNavButton = (questionId) => {
            navButtons.forEach((btn) => {
                const isActive = btn.dataset.questionId === questionId;
                btn.classList.toggle('active-question', isActive);
            });
        };

        const getVisibleCards = () =>
            questionCards.filter((card) =>
                !card.classList.contains('subject-hidden'));

        const updatePositionTotals = () => {
            if (questionPositionTotal) {
                questionPositionTotal.textContent = String(getVisibleCards().length || 0);
            }
        };

        const updateAnsweredMap = () => {
            answeredMap = {};
            inputs.forEach((input) => {
                if (input.type === 'radio' || input.type === 'checkbox') {
                    if (input.checked) {
                        answeredMap[input.dataset.questionId] = true;
                    }
                } else if (input.value.trim().length > 0) {
                    answeredMap[input.dataset.questionId] = true;
                }
            });
        };

        const updateControlState = () => {
            if (!controls) {
                return;
            }
            const visibleCards = getVisibleCards();
            const prevBtn = controls.querySelector('[data-nav="prev"]');
            const skipBtn = controls.querySelector('[data-nav="skip"]');
            const nextBtn = controls.querySelector('[data-nav="next"]');
            const atFirst = currentQuestionIndex === 0;
            const atLast = !visibleCards.length || currentQuestionIndex >= visibleCards.length - 1;
            if (prevBtn) {
                prevBtn.disabled = atFirst || !visibleCards.length;
            }
            if (skipBtn) {
                skipBtn.disabled = atLast;
            }
            if (nextBtn) {
                const currentCard = visibleCards[currentQuestionIndex];
                const isAnswered = currentCard ? answeredMap[currentCard.dataset.questionId] : false;
                nextBtn.disabled = atLast || !isAnswered;
                nextBtn.textContent = isAnswered ? 'Next question' : 'Answer to continue';
            }
        };

        const showQuestion = (index) => {
            const visibleCards = getVisibleCards();
            if (!visibleCards.length) {
                currentQuestionIndex = 0;
                if (questionCards.length) {
                    questionCards.forEach((card) => card.classList.remove('active'));
                }
                if (questionPosition) {
                    questionPosition.textContent = '0';
                }
                setActiveNavButton(null);
                updateControlState();
                return;
            }
            const clampedIndex = Math.max(0, Math.min(index, visibleCards.length - 1));
            questionCards.forEach((card) => card.classList.remove('active'));
            const activeCard = visibleCards[clampedIndex];
            activeCard.classList.add('active');
            currentQuestionIndex = clampedIndex;
            if (questionPosition) {
                questionPosition.textContent = String(clampedIndex + 1);
            }
            setActiveNavButton(activeCard.dataset.questionId);
            updateControlState();
        };

        const showQuestionById = (questionId) => {
            const visibleCards = getVisibleCards();
            const targetIndex = visibleCards.findIndex((card) => card.dataset.questionId === questionId);
            if (targetIndex >= 0) {
                showQuestion(targetIndex);
            }
        };

        if (controls) {
            controls.addEventListener('click', (event) => {
                const target = event.target.closest('button[data-nav]');
                if (!target) {
                    return;
                }
                const action = target.dataset.nav;
                if (action === 'prev') {
                    showQuestion(currentQuestionIndex - 1);
                }
                if (action === 'skip') {
                    showQuestion(currentQuestionIndex + 1);
                }
                if (action === 'next') {
                    showQuestion(currentQuestionIndex + 1);
                }
            });
        }

        navButtons.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                showQuestionById(btn.dataset.questionId);
            });
        });

        optionBoxes.forEach((box) => {
            box.addEventListener('click', (event) => {
                if (!event.target || !(event.target instanceof Element)) {
                    return;
                }

                if (event.target.closest('input, label, a, button, textarea, select')) {
                    return;
                }

                const input = box.querySelector('input.question-input[type="radio"], input.question-input[type="checkbox"]');
                if (!input || input.disabled) {
                    return;
                }

                input.click();
                input.focus();
            });
        });

        const applySubjectFilter = (subjectId) => {
            currentSubjectId = subjectId;
            const showAll = !subjectId || subjectId === 'all';
            questionCards.forEach((card) => {
                const matches = showAll || card.dataset.subjectId === subjectId;
                card.classList.toggle('subject-hidden', !matches);
                if (!matches) {
                    card.classList.remove('active');
                }
            });
            navButtons.forEach((btn) => {
                const matches = showAll || btn.dataset.subjectId === subjectId;
                btn.classList.toggle('d-none', !matches);
            });
            subjectTabs.forEach((tab) => {
                tab.classList.toggle('active', tab.dataset.subjectId === subjectId);
            });
            if (subjectNameEls.length) {
                const activeTab = subjectTabs.find((tab) => tab.dataset.subjectId === subjectId);
                const displayName = activeTab?.dataset.subjectName || 'All questions';
                subjectNameEls.forEach((el) => {
                    el.textContent = displayName;
                });
            }
            const visibleCards = getVisibleCards();
            updatePositionTotals();
            if (subjectEmptyNotice) {
                subjectEmptyNotice.classList.toggle('d-none', visibleCards.length > 0);
            }
            if (visibleCards.length) {
                showQuestion(0);
            } else if (questionPosition) {
                questionPosition.textContent = '0';
                updateControlState();
                setActiveNavButton(null);
            }
        };

        if (subjectTabs.length) {
            subjectTabs.forEach((tab) => {
                tab.addEventListener('click', () => applySubjectFilter(tab.dataset.subjectId));
            });
            applySubjectFilter(currentSubjectId);
        } else {
            updatePositionTotals();
        }

        const markAnswered = () => {
            updateAnsweredMap();
            navButtons.forEach((btn) => {
                const id = btn.dataset.questionId;
                const isAnswered = Boolean(answeredMap[id]);
                btn.classList.toggle('answered', isAnswered);
                btn.classList.toggle('unanswered', !isAnswered);
            });
            if (subjectTabs.length) {
                const subjectAnsweredCounts = {};
                questionCards.forEach((card) => {
                    const subjectId = card.dataset.subjectId || 'unassigned';
                    const isAnswered = Boolean(answeredMap[card.dataset.questionId]);
                    if (!subjectAnsweredCounts[subjectId]) {
                        subjectAnsweredCounts[subjectId] = 0;
                    }
                    if (isAnswered) {
                        subjectAnsweredCounts[subjectId] += 1;
                    }
                });
                subjectTabs.forEach((tab) => {
                    const subjectId = tab.dataset.subjectId || 'unassigned';
                    const answeredEl = tab.querySelector('.subject-answered');
                    if (answeredEl) {
                        answeredEl.textContent = String(subjectAnsweredCounts[subjectId] || 0);
                    }
                });
            }
            updateControlState();
        };

        const setAutosaveStatus = (text, options = {}) => {
            if (!autosaveStatusText) {
                return;
            }
            autosaveStatusText.textContent = text;

            if (!autosaveStatusTime) {
                return;
            }

            if (options.timestamp) {
                const parsed = new Date(options.timestamp);
                const formatted = Number.isNaN(parsed.getTime())
                    ? null
                    : `(saved ${parsed.toLocaleTimeString()})`;
                if (formatted) {
                    autosaveStatusTime.textContent = formatted;
                    autosaveStatusTime.classList.remove('d-none');
                    return;
                }
            }

            if (options.raw) {
                autosaveStatusTime.textContent = options.raw;
                autosaveStatusTime.classList.remove('d-none');
                return;
            }

            autosaveStatusTime.classList.add('d-none');
        };

        const buildQuestionFormData = (questionId) => {
            if (!examForm || !csrfToken) {
                return null;
            }

            const questionCard = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
            if (!questionCard) {
                return null;
            }

            const questionInputs = questionCard.querySelectorAll('.question-input');
            if (!questionInputs.length) {
                return null;
            }

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('question_id', questionId);
            formData.append(`answers[${questionId}][exam_question_id]`, questionId);
            let hasPayload = false;

            questionInputs.forEach((input) => {
                if (input.type === 'checkbox') {
                    if (input.checked) {
                        formData.append(`answers[${questionId}][selected_option_ids][]`, input.value);
                    }
                    hasPayload = true;
                    return;
                }

                if (input.type === 'radio') {
                    if (input.checked) {
                        formData.append(`answers[${questionId}][selected_option_ids][]`, input.value);
                    }
                    hasPayload = true;
                    return;
                }

                if (input.tagName === 'TEXTAREA') {
                    formData.set(`answers[${questionId}][answer_text]`, input.value || '');
                    hasPayload = true;
                }
            });

            return hasPayload ? formData : null;
        };

        const sendAutosave = async (questionId) => {
            if (!autosaveEnabled || !autosaveUrl || !csrfToken) {
                return;
            }

            const formData = buildQuestionFormData(questionId);
            if (!formData) {
                return;
            }

            if (autosaveStatusText) {
                setAutosaveStatus('Saving…');
            }

            try {
                const response = await fetch(autosaveUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    let message = 'Autosave failed';
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        const errorPayload = await response.json();
                        message = errorPayload?.message || message;
                    } else {
                        const errorText = await response.text();
                        if (errorText) {
                            message = errorText;
                        }
                    }
                    throw new Error(message);
                }

                let payload = {};
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    payload = await response.json();
                }

                const timestamp = payload.timestamp || new Date().toISOString();
                setAutosaveStatus('Saved', { timestamp });
            } catch (error) {
                console.error('Autosave error', error);
                const errorMessage = error instanceof Error ? error.message : '';
                const userHint = errorMessage && errorMessage !== 'Autosave failed'
                    ? `(${errorMessage})`
                    : '(retry when online)';
                setAutosaveStatus('Save failed', { raw: userHint });
            }
        };

        const queueAutosave = (questionId) => {
            if (!questionId || !autosaveEnabled || !autosaveUrl || !csrfToken) {
                return;
            }

            const pending = autosaveTimers.get(questionId);
            if (pending) {
                window.clearTimeout(pending);
            }

            const timerId = window.setTimeout(() => {
                autosaveTimers.delete(questionId);
                sendAutosave(questionId);
            }, AUTOSAVE_DEBOUNCE_MS);

            autosaveTimers.set(questionId, timerId);
        };

        const isTypingTarget = (target) => {
            if (!target || !(target instanceof Element)) {
                return false;
            }

            if (target.closest('textarea, select, [contenteditable="true"]')) {
                return true;
            }

            const input = target.closest('input');
            if (!input) {
                return false;
            }

            const inputType = (input.getAttribute('type') || 'text').toLowerCase();
            return !['radio', 'checkbox'].includes(inputType);
        };

        const selectOptionByShortcut = (shortcutKey) => {
            const visibleCards = getVisibleCards();
            const activeCard = visibleCards[currentQuestionIndex];

            if (!activeCard) {
                return;
            }

            const shortcutIndex = shortcutKey.charCodeAt(0) - 97;
            if (shortcutIndex < 0 || shortcutIndex > 25) {
                return;
            }

            const optionInputs = Array.from(activeCard.querySelectorAll('input.question-input[type="radio"], input.question-input[type="checkbox"]'));
            const targetInput = optionInputs[shortcutIndex];

            if (!targetInput || targetInput.disabled) {
                return;
            }

            targetInput.click();
            targetInput.focus();
        };

        const getActiveQuestionOptionInputs = () => {
            const visibleCards = getVisibleCards();
            const activeCard = visibleCards[currentQuestionIndex];
            if (!activeCard) {
                return [];
            }

            return Array.from(activeCard.querySelectorAll('input.question-input[type="radio"], input.question-input[type="checkbox"]'))
                .filter((input) => !input.disabled);
        };

        const moveOptionSelection = (direction) => {
            const optionInputs = getActiveQuestionOptionInputs();
            if (!optionInputs.length) {
                return;
            }

            const focusedIndex = optionInputs.findIndex((input) => input === document.activeElement);
            const checkedIndex = optionInputs.findIndex((input) => input.checked);

            let currentIndex = focusedIndex >= 0 ? focusedIndex : checkedIndex;
            if (currentIndex < 0) {
                currentIndex = direction > 0 ? -1 : optionInputs.length;
            }

            const nextIndex = Math.max(0, Math.min(currentIndex + direction, optionInputs.length - 1));
            const targetInput = optionInputs[nextIndex];

            if (!targetInput) {
                return;
            }

            targetInput.click();
            targetInput.focus();
        };

        const handleQuestionInputEvent = (event) => {
            markAnswered();
            const target = event.target;
            if (!target) {
                return;
            }
            const questionId = target.dataset ? target.dataset.questionId : null;
            if (questionId) {
                queueAutosave(questionId);
            }
        };

        document.addEventListener('keydown', (event) => {
            if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            if (isTypingTarget(event.target)) {
                return;
            }

            const keyRaw = String(event.key || '');
            const key = keyRaw.toLowerCase();

            if (keyRaw === 'ArrowRight' || keyRaw === '>') {
                event.preventDefault();
                showQuestion(currentQuestionIndex + 1);
                return;
            }

            if (keyRaw === 'ArrowLeft' || keyRaw === '<') {
                event.preventDefault();
                showQuestion(currentQuestionIndex - 1);
                return;
            }

            if (keyRaw === 'ArrowDown') {
                event.preventDefault();
                moveOptionSelection(1);
                return;
            }

            if (keyRaw === 'ArrowUp') {
                event.preventDefault();
                moveOptionSelection(-1);
                return;
            }

            if (!/^[a-z]$/.test(key)) {
                return;
            }

            event.preventDefault();
            selectOptionByShortcut(key);
        });

        inputs.forEach((input) => {
            input.addEventListener('change', handleQuestionInputEvent);
            input.addEventListener('keyup', handleQuestionInputEvent);
            if (input.tagName === 'TEXTAREA') {
                input.addEventListener('input', handleQuestionInputEvent);
            }
        });

        markAnswered();
        if (!subjectTabs.length) {
            showQuestion(0);
        }

        const summaryTimer = document.querySelector('[data-summary-timer]');
        const timerRemainingEl = summaryTimer ? summaryTimer.querySelector('[data-timer-remaining]') : null;
        const timerUsedEl = document.querySelector('[data-timer-used]');
        const autoSubmitConfig = (() => {
            if (!summaryTimer) {
                return { enabled: false };
            }
            const dataset = summaryTimer.dataset;
            return {
                enabled: dataset.autoSubmitEnabled === 'true',
                redirect: dataset.autoSubmitRedirect || null,
                title: dataset.autoSubmitTitle || 'Time is up!',
                message: dataset.autoSubmitMessage || 'Your allocated time has elapsed. Submitting your answers now.',
                successTitle: dataset.autoSubmitSuccessTitle || 'Exam submitted',
                successMessage: dataset.autoSubmitSuccessMessage || 'Your responses have been saved automatically.',
                confirmText: dataset.autoSubmitConfirmText || 'Continue',
                errorTitle: dataset.autoSubmitErrorTitle || 'Submission issue',
                errorMessage: dataset.autoSubmitErrorMessage || 'We could not confirm the automatic submission. We will retry now.',
            };
        })();
        let autoSubmitTriggered = false;
        const zeroPad = (value) => String(value).padStart(2, '0');
        const formatTimerDuration = (totalSeconds) => {
            if (!Number.isFinite(totalSeconds) || totalSeconds < 0) {
                return '00:00:00';
            }
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = Math.floor(totalSeconds % 60);
            const hourDisplay = hours >= 100 ? String(hours) : zeroPad(hours);
            return `${hourDisplay}:${zeroPad(minutes)}:${zeroPad(seconds)}`;
        };

        const freezeExamInteraction = () => {
            inputs.forEach((input) => {
                if (input.matches?.('input[type="radio"], input[type="checkbox"]')) {
                    input.disabled = true;
                    return;
                }

                if (input.tagName === 'TEXTAREA') {
                    input.readOnly = true;
                } else {
                    input.disabled = true;
                }
            });

            if (controls) {
                controls.querySelectorAll('button').forEach((btn) => {
                    btn.disabled = true;
                });
            }

            navButtons.forEach((btn) => {
                btn.classList.add('disabled');
                btn.setAttribute('aria-disabled', 'true');
                btn.setAttribute('tabindex', '-1');
            });
        };

        const autoSubmitViaAjax = async () => {
            if (!window.fetch) {
                throw new Error('Fetch API is unavailable.');
            }

            const formData = new FormData(examForm);
            formData.set('auto_submit', '1');

            const response = await fetch(examForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'Automatic submission failed.');
            }

            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                return response.json();
            }

            return {};
        };

        const triggerAutoSubmit = async () => {
            if (!autoSubmitConfig.enabled || autoSubmitTriggered || !examForm) {
                return;
            }

            autoSubmitTriggered = true;
            freezeExamInteraction();
            if (autoSubmitFlagInput) {
                autoSubmitFlagInput.value = '1';
            }

            const submitDirectly = () => {
                if (!examForm) {
                    return;
                }
                examForm.dataset.swalSubmitting = 'true';
                examForm.submit();
            };

            if (typeof Swal === 'undefined') {
                submitDirectly();
                return;
            }

            Swal.fire({
                title: autoSubmitConfig.title,
                text: autoSubmitConfig.message,
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const responseData = await autoSubmitViaAjax();
                const redirectTarget = responseData?.redirect_url || autoSubmitConfig.redirect || examForm.action;

                Swal.fire({
                    title: autoSubmitConfig.successTitle,
                    text: autoSubmitConfig.successMessage,
                    icon: 'success',
                    confirmButtonText: autoSubmitConfig.confirmText,
                    allowOutsideClick: false,
                }).then(() => {
                    window.location.href = redirectTarget;
                });
            } catch (error) {
                console.error('Auto submission failed', error);
                Swal.fire({
                    title: autoSubmitConfig.errorTitle,
                    text: autoSubmitConfig.errorMessage,
                    icon: 'warning',
                    confirmButtonText: autoSubmitConfig.confirmText,
                    allowOutsideClick: false,
                }).then(() => {
                    submitDirectly();
                });
            }
        };

        if (summaryTimer && (timerRemainingEl || timerUsedEl)) {
            const dataset = summaryTimer.dataset;
            const getNumber = (value) => {
                const parsed = Number(value);
                return Number.isFinite(parsed) ? parsed : null;
            };
            const serverNowSeconds = getNumber(dataset.serverNow);
            const deadlineSeconds = getNumber(dataset.deadline);
            const timerDurationSeconds = getNumber(dataset.timerDurationSeconds);
            const startingRemainingSeconds = getNumber(dataset.timerRemainingSeconds);
            const startingUsedSeconds = getNumber(dataset.timerUsedSeconds);
            const clientLoadMs = Date.now();
            const serverNowMs = serverNowSeconds !== null ? serverNowSeconds * 1000 : null;
            const deadlineMs = deadlineSeconds !== null ? deadlineSeconds * 1000 : null;
            const clockOffset = serverNowMs !== null ? serverNowMs - clientLoadMs : 0;
            const referenceMs = serverNowMs ?? clientLoadMs;
            let timerIntervalId = null;

            const computeElapsedSinceLoad = (nowMs) => Math.max(Math.round((nowMs - referenceMs) / 1000), 0);

            const updateTimerDisplays = () => {
                const nowMs = Date.now() + clockOffset;
                let remainingSeconds = null;

                if (deadlineMs !== null) {
                    remainingSeconds = Math.max(Math.round((deadlineMs - nowMs) / 1000), 0);
                } else if (startingRemainingSeconds !== null) {
                    remainingSeconds = Math.max(startingRemainingSeconds - computeElapsedSinceLoad(nowMs), 0);
                }

                if (timerRemainingEl) {
                    timerRemainingEl.textContent = remainingSeconds !== null
                        ? formatTimerDuration(remainingSeconds)
                        : 'N/A';
                }

                if (timerUsedEl) {
                    let usedSeconds = null;
                    if (timerDurationSeconds !== null && remainingSeconds !== null) {
                        const consumed = Math.max(timerDurationSeconds - remainingSeconds, 0);
                        usedSeconds = Math.min(timerDurationSeconds, consumed);
                    } else if (startingUsedSeconds !== null) {
                        usedSeconds = startingUsedSeconds + computeElapsedSinceLoad(nowMs);
                    }

                    if (usedSeconds !== null) {
                        timerUsedEl.textContent = formatTimerDuration(usedSeconds);
                    }
                }

                const timerExpired = remainingSeconds === 0 && remainingSeconds !== null;

                if (timerExpired && autoSubmitConfig.enabled) {
                    triggerAutoSubmit();
                }

                if (timerExpired && deadlineMs !== null && timerIntervalId) {
                    clearInterval(timerIntervalId);
                    timerIntervalId = null;
                }
            };

            updateTimerDisplays();
            timerIntervalId = window.setInterval(updateTimerDisplays, 1000);
        }
    </script>
@endpush
