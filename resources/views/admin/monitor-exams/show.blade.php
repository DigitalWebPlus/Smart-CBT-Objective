@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Attempt Review</div>
                        <h2 class="page-title">{{ $attempt->candidate?->name ?? 'Unknown Candidate' }}</h2>
                        <div class="text-secondary fs-4 fw-semibold">
                            {{ $exam->title }}
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="{{ route('admin.monitor-exams.index') }}" class="btn btn-outline">Back to Monitor</a>
                        <a href="{{ request()->fullUrl() }}" class="btn btn-outline-primary">
                            <i class="ti ti-refresh me-1"></i>Refresh
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @php
                    $subjectScores = collect($attempt->subject_scores ?? []);
                    $attemptScore = $attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? 0;

                    if ((float) $attemptScore <= 0) {
                        $responseCollectionForScore = $attempt->objectiveResponses->isNotEmpty()
                            ? $attempt->objectiveResponses
                            : $attempt->responses;
                        $responseByExamQuestionForScore = $responseCollectionForScore->keyBy('exam_question_id');
                        $responseByObjectiveQuestionForScore = $responseCollectionForScore->keyBy('objective_question_id');

                        $liveScore = 0.0;
                        foreach ($exam->questions ?? [] as $examQuestion) {
                            $question = $examQuestion->question;
                            if (! $question) {
                                continue;
                            }

                            $response = $responseByExamQuestionForScore->get($examQuestion->id)
                                ?: $responseByObjectiveQuestionForScore->get($examQuestion->objective_question_id);

                            if (! $response) {
                                continue;
                            }

                            $rawSelected = $response->selected_option_ids ?? [];
                            if (empty($rawSelected) && ! empty($response->metadata)) {
                                $rawSelected = $response->metadata['selected_option_ids']
                                    ?? $response->metadata['selected_option_id']
                                    ?? $response->metadata['selected_options']
                                    ?? $response->metadata['selected_option']
                                    ?? [];
                            }

                            $selectedIds = collect(\App\Models\ObjectiveResponse::normalizeSelectedOptionIds($rawSelected))
                                ->map(fn ($value) => (int) $value)
                                ->filter(fn ($value) => $value > 0)
                                ->unique()
                                ->sort()
                                ->values();

                            if ($selectedIds->isEmpty()) {
                                continue;
                            }

                            $correctIds = $question->options
                                ->where('is_correct', true)
                                ->pluck('id')
                                ->map(fn ($value) => (int) $value)
                                ->filter(fn ($value) => $value > 0)
                                ->unique()
                                ->sort()
                                ->values();

                            if ($selectedIds->all() === $correctIds->all()) {
                                $liveScore += (float) ($examQuestion->marks ?? 0);
                            }
                        }

                        if ($liveScore > 0) {
                            $attemptScore = $liveScore;
                        }
                    }

                    $displayTotal = (float) ($attempt->computed_total_marks ?? 0);
                    if ($displayTotal <= 0) {
                        $displayTotal = (float) ($exam->questions_sum_marks ?? 0);
                    }
                    if ($displayTotal <= 0) {
                        $displayTotal = (float) collect($exam->questions ?? [])->sum(function ($examQuestion) {
                            return (float) ($examQuestion->marks ?? 0);
                        });
                    }
                    if ($displayTotal <= 0) {
                        $displayTotal = (float) ($exam->total_marks ?? 0);
                    }
                    if ($displayTotal <= 0) {
                        $displayTotal = (float) $subjectScores->sum('max_marks');
                    }
                    $percentage = $displayTotal > 0
                        ? round(((float) $attemptScore / $displayTotal) * 100, 2)
                        : 0;

                    $elapsedSeconds = $attempt->started_at
                        ? $attempt->started_at->diffInSeconds($attempt->submitted_at ?? now())
                        : null;
                    $formattedElapsed = $elapsedSeconds !== null
                        ? sprintf(
                            '%02d:%02d:%02d',
                            intdiv($elapsedSeconds, 3600),
                            intdiv($elapsedSeconds % 3600, 60),
                            $elapsedSeconds % 60,
                        )
                        : '—';

                    $statusColors = [
                        \App\Models\ExamAttempt::STATUS_IN_PROGRESS => 'green',
                        \App\Models\ExamAttempt::STATUS_SUBMITTED => 'yellow',
                        \App\Models\ExamAttempt::STATUS_PUBLISHED => 'green',
                        \App\Models\ExamAttempt::STATUS_CANCELED => 'red',
                        \App\Models\ExamAttempt::STATUS_RETAKE => 'orange',
                    ];
                    $statusOptions = [
                        \App\Models\ExamAttempt::STATUS_SUBMITTED,
                        \App\Models\ExamAttempt::STATUS_PUBLISHED,
                        \App\Models\ExamAttempt::STATUS_CANCELED,
                        \App\Models\ExamAttempt::STATUS_RETAKE,
                    ];
                @endphp

                <div class="card mb-3">
                    <div class="card-body d-flex flex-wrap align-items-center gap-3">
                        <span class="badge bg-{{ $statusColors[$attempt->status] ?? 'secondary' }} text-white fs-6 px-3 py-2">
                            {{ \Illuminate\Support\Str::headline($attempt->status) }}
                        </span>
                    </div>
                </div>

                <div class="row row-cards mb-3">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-uppercase small">Total Score</div>
                                        <div class="h2 mb-0">
                                            {{ number_format((float) $attemptScore, 2) }}
                                            <span class="small">/ {{ number_format((float) ($displayTotal ?? 0), 2) }}</span>
                                        </div>
                                    </div>
                                    <span class="avatar bg-white text-primary">
                                        <i class="ti ti-chart-bar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-uppercase small">Percentage</div>
                                        <div class="h2 mb-0">{{ number_format((float) $percentage, 2) }}%</div>
                                    </div>
                                    <span class="avatar bg-white text-success">
                                        <i class="ti ti-percentage"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-uppercase small">Started</div>
                                        <div class="fw-semibold">{{ optional($attempt->started_at)?->format('M d, h:i a') ?? '—' }}</div>
                                    </div>
                                    <span class="avatar bg-white text-warning">
                                        <i class="ti ti-player-play"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-uppercase small">Time</div>
                                        <div class="h3 mb-0 fw-bold">{{ $formattedElapsed }}</div>
                                    </div>
                                    <span class="avatar bg-white text-info">
                                        <i class="ti ti-clock-hour-4"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($subjectScores->isNotEmpty())
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Subject Scores</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter" data-subject-scores-table>
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Score</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjectScores as $score)
                                        <tr class="subject-score-row">
                                            <td>
                                                <div class="fw-semibold">{{ $score['subject_name'] ?? 'General' }}</div>
                                                <div class="text-secondary small">{{ $score['subject_code'] ?? 'GEN' }}</div>
                                            </td>
                                            <td>
                                                {{ number_format((float) ($score['total_score'] ?? 0), 2) }}
                                                / {{ number_format((float) ($score['max_marks'] ?? 0), 2) }}
                                            </td>
                                            <td>{{ number_format((float) ($score['percentage'] ?? 0), 2) }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @php
                    $responseCollection = $attempt->objectiveResponses->isNotEmpty()
                        ? $attempt->objectiveResponses
                        : $attempt->responses;
                    $responseByExamQuestion = $responseCollection->keyBy('exam_question_id');
                    $responseByObjectiveQuestion = $responseCollection->keyBy('objective_question_id');
                    $questionRows = $exam->questions ?? collect();
                    $allQuestionsCount = $questionRows->count();
                    $subjectCollection = $exam->subjects->isNotEmpty()
                        ? $exam->subjects
                        : $questionRows
                            ->map(fn ($examQuestion) => $examQuestion->question?->subject)
                            ->filter()
                            ->unique('id')
                            ->values();
                    $questionsBySubject = $questionRows->groupBy(function ($examQuestion) {
                        return $examQuestion->question?->subject_id
                            ? (string) $examQuestion->question->subject_id
                            : 'unassigned';
                    });
                    $subjectTabs = $subjectCollection
                        ->map(function ($subject) use ($questionsBySubject) {
                            $subjectKey = (string) $subject->id;
                            return [
                                'id' => $subjectKey,
                                'name' => $subject->name,
                                'code' => $subject->code,
                                'question_count' => $questionsBySubject->get($subjectKey)?->count() ?? 0,
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
                        ]);
                    }
                    if ($subjectTabs->isEmpty() && $questionRows->isNotEmpty()) {
                        $subjectTabs->push([
                            'id' => 'all',
                            'name' => 'All Questions',
                            'code' => 'ALL',
                            'question_count' => $questionRows->count(),
                        ]);
                    }
                    $subjectCardTabs = collect([
                        [
                            'id' => 'all',
                            'name' => 'All Questions',
                            'code' => 'ALL',
                            'question_count' => $allQuestionsCount,
                        ],
                    ])->merge($subjectTabs);
                @endphp

                @if ($questionRows->isNotEmpty())
                    <div class="row g-3 mb-3" data-subject-card-list>
                        @php
                            $subjectCardThemes = [
                                'bg-primary text-white',
                                'bg-success text-white',
                                'bg-warning text-white',
                                'bg-info text-white',
                                'bg-danger text-white',
                                'bg-indigo text-white',
                                'bg-teal text-white',
                            ];
                        @endphp
                        @foreach ($subjectCardTabs as $tab)
                            @php $subjectTheme = $subjectCardThemes[$loop->index % count($subjectCardThemes)]; @endphp
                            <div class="col-6 col-md-3 col-lg-2">
                                <button type="button" class="card h-100 text-start w-100 subject-card {{ $subjectTheme }}" data-subject-card
                                    data-subject-id="{{ $tab['id'] }}">
                                    <div class="card-body">
                                        <div class="text-uppercase small">{{ $tab['code'] ?? 'SUB' }}</div>
                                        <div class="fw-semibold">{{ $tab['name'] }}</div>
                                        <div class="small">{{ $tab['question_count'] ?? 0 }} questions</div>
                                    </div>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Responses</h3>
                    </div>
                    @forelse ($subjectTabs as $subjectTab)
                        @php
                            $subjectKey = $subjectTab['id'];
                            $subjectQuestions = $subjectKey === 'all'
                                ? $questionRows
                                : ($questionsBySubject->get($subjectKey) ?? collect());
                            $subjectRowCounter = 0;
                        @endphp
                        <div class="table-responsive mb-3" data-subject-row data-subject-id="{{ $subjectKey }}">
                            <table class="table table-vcenter table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th colspan="4" class="bg-light">
                                            <h2 class="subject-title mb-0">
                                                {{ $subjectTab['name'] }}
                                            </h2>
                                            <span class="text-secondary small">{{ $subjectTab['question_count'] ?? $subjectQuestions->count() }} Questions</span>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>#</th>
                                        <th>Question</th>
                                        <th>Option</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($subjectQuestions as $examQuestion)
                                    @php
                                        $subjectRowCounter += 1;
                                        $response = $responseByExamQuestion->get($examQuestion->id)
                                            ?: $responseByObjectiveQuestion->get($examQuestion->objective_question_id);
                                        $question = $examQuestion->question;
                                        $selectedLines = $responseSelectionsByExamQuestion[$examQuestion->id]
                                            ?? $responseSelectionsByObjectiveQuestion[$examQuestion->objective_question_id]
                                            ?? $responseSelectionsFallbackByExamQuestion[$examQuestion->id]
                                            ?? $responseSelectionsFallbackByObjectiveQuestion[$examQuestion->objective_question_id]
                                            ?? [];

                                        if (empty($selectedLines) && $response) {
                                            $rawSelected = $response->selected_option_ids ?? [];

                                            if (empty($rawSelected) && ! empty($response->metadata)) {
                                                $rawSelected = $response->metadata['selected_option_ids']
                                                    ?? $response->metadata['selected_option_id']
                                                    ?? $response->metadata['selected_options']
                                                    ?? $response->metadata['selected_option']
                                                    ?? [];
                                            }

                                            if (is_string($rawSelected)) {
                                                $decodedSelected = json_decode($rawSelected, true);
                                                if (json_last_error() === JSON_ERROR_NONE) {
                                                    $rawSelected = $decodedSelected;
                                                } else {
                                                    $rawSelected = array_map('trim', explode(',', $rawSelected));
                                                }
                                            }

                                            $rawValues = collect(is_array($rawSelected) ? $rawSelected : [$rawSelected])
                                                ->filter(fn ($value) => $value !== null && $value !== '')
                                                ->values();

                                            $selectedIds = \App\Models\ObjectiveResponse::normalizeSelectedOptionIds($rawSelected);
                                            $optionMap = collect($question?->options ?? [])->keyBy('id');

                                            if (! empty($selectedIds) && $optionMap->isNotEmpty()) {
                                                $selectedLines = collect($selectedIds)
                                                    ->map(function ($optionId) use ($optionMap) {
                                                        $option = $optionMap->get((int) $optionId);
                                                        return $option?->description ?: $option?->label;
                                                    })
                                                    ->filter()
                                                    ->values()
                                                    ->all();
                                            }

                                            if (empty($selectedLines) && ! empty($response->metadata)) {
                                                $selectedLines = collect($response->metadata['selected_option_labels']
                                                    ?? $response->metadata['selected_option_texts']
                                                    ?? $response->metadata['selected_option_values']
                                                    ?? [])
                                                    ->filter(fn ($value) => $value !== null && $value !== '')
                                                    ->map(fn ($value) => (string) $value)
                                                    ->values()
                                                    ->all();
                                            }

                                            if (empty($selectedLines)) {
                                                $selectedLines = $rawValues
                                                    ->map(function ($value) {
                                                        if (is_array($value)) {
                                                            return (string) ($value['label'] ?? $value['description'] ?? $value['text'] ?? '');
                                                        }

                                                        if (is_object($value)) {
                                                            return (string) ($value->label ?? $value->description ?? $value->text ?? '');
                                                        }

                                                        return ! is_numeric($value) ? (string) $value : '';
                                                    })
                                                    ->filter()
                                                    ->values()
                                                    ->all();
                                            }

                                            if (empty($selectedLines) && $optionMap->isNotEmpty()) {
                                                $optionList = $optionMap->values();
                                                $numericValues = $rawValues
                                                    ->filter(fn ($value) => is_numeric($value))
                                                    ->map(fn ($value) => (int) $value)
                                                    ->values();

                                                if ($numericValues->isNotEmpty()) {
                                                    $indexOffset = $numericValues->contains(0) ? 0 : 1;
                                                    if (! $numericValues->contains(0)) {
                                                        $min = $numericValues->min();
                                                        $max = $numericValues->max();
                                                        if ($min < 1 || $max > $optionList->count()) {
                                                            $indexOffset = 0;
                                                        }
                                                    }

                                                    $selectedLines = $numericValues
                                                        ->map(function ($value) use ($optionList, $indexOffset) {
                                                            $index = $value - $indexOffset;
                                                            $option = $optionList->get($index);
                                                            return $option?->description ?: $option?->label;
                                                        })
                                                        ->filter()
                                                        ->values()
                                                        ->all();
                                                }
                                            }
                                        }

                                        $optionList = collect($question?->options ?? [])->values();
                                        $toAlphabet = function (int $index): string {
                                            $label = '';
                                            $current = $index + 1;
                                            while ($current > 0) {
                                                $current -= 1;
                                                $label = chr(65 + ($current % 26)) . $label;
                                                $current = intdiv($current, 26);
                                            }
                                            return $label;
                                        };

                                        $selectedDisplayLines = collect($selectedLines)
                                            ->map(function ($line) use ($optionList, $toAlphabet) {
                                                $value = trim((string) $line);
                                                if ($value === '' || $optionList->isEmpty()) {
                                                    return $value;
                                                }

                                                $index = $optionList->search(function ($option) use ($value) {
                                                    $description = trim((string) ($option->description ?? ''));
                                                    $label = trim((string) ($option->label ?? ''));
                                                    return $description === $value || $label === $value;
                                                });

                                                if ($index === false) {
                                                    return $value;
                                                }

                                                return '(' . $toAlphabet((int) $index) . '): ' . $value;
                                            })
                                            ->filter(fn ($value) => $value !== '')
                                            ->values()
                                            ->all();

                                        $isCorrectResponse = null;
                                        if ($response && $question) {
                                            $rawSelectedForGrade = $response->selected_option_ids ?? [];
                                            if (empty($rawSelectedForGrade) && ! empty($response->metadata)) {
                                                $rawSelectedForGrade = $response->metadata['selected_option_ids']
                                                    ?? $response->metadata['selected_option_id']
                                                    ?? $response->metadata['selected_options']
                                                    ?? $response->metadata['selected_option']
                                                    ?? [];
                                            }

                                            if (is_string($rawSelectedForGrade)) {
                                                $decoded = json_decode($rawSelectedForGrade, true);
                                                if (json_last_error() === JSON_ERROR_NONE) {
                                                    $rawSelectedForGrade = $decoded;
                                                } else {
                                                    $rawSelectedForGrade = array_map('trim', explode(',', $rawSelectedForGrade));
                                                }
                                            }

                                            $selectedIdsForGrade = collect(\App\Models\ObjectiveResponse::normalizeSelectedOptionIds($rawSelectedForGrade))
                                                ->map(fn ($value) => (int) $value)
                                                ->filter(fn ($value) => $value > 0)
                                                ->unique()
                                                ->sort()
                                                ->values();

                                            if ($selectedIdsForGrade->isEmpty() && $optionList->isNotEmpty()) {
                                                $numericValues = collect(is_array($rawSelectedForGrade) ? $rawSelectedForGrade : [$rawSelectedForGrade])
                                                    ->filter(fn ($value) => $value !== null && $value !== '' && is_numeric($value))
                                                    ->map(fn ($value) => (int) $value)
                                                    ->values();

                                                if ($numericValues->isNotEmpty()) {
                                                    $indexOffset = $numericValues->contains(0) ? 0 : 1;
                                                    if (! $numericValues->contains(0)) {
                                                        $min = $numericValues->min();
                                                        $max = $numericValues->max();
                                                        if ($min < 1 || $max > $optionList->count()) {
                                                            $indexOffset = 0;
                                                        }
                                                    }

                                                    $selectedIdsForGrade = $numericValues
                                                        ->map(function (int $value) use ($optionList, $indexOffset) {
                                                            $index = $value - $indexOffset;
                                                            return (int) ($optionList->get($index)?->id ?? 0);
                                                        })
                                                        ->filter(fn ($value) => $value > 0)
                                                        ->unique()
                                                        ->sort()
                                                        ->values();
                                                }
                                            }

                                            $correctIdsForGrade = $optionList
                                                ->filter(fn ($option) => (bool) ($option->is_correct ?? false))
                                                ->pluck('id')
                                                ->map(fn ($value) => (int) $value)
                                                ->filter(fn ($value) => $value > 0)
                                                ->unique()
                                                ->sort()
                                                ->values();

                                            $isCorrectResponse = $selectedIdsForGrade->isNotEmpty()
                                                && $selectedIdsForGrade->all() === $correctIdsForGrade->all();
                                        }
                                    @endphp
                                    <tr data-response-row>
                                        <td>{{ $subjectRowCounter }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($question?->question_text, 160) }}</td>
                                        <td>
                                            @if (! empty($selectedDisplayLines))
                                                {!! collect($selectedDisplayLines)
                                                    ->map(fn ($label) => '<div class="selected-option-line">' . e($label) . '</div>')
                                                    ->implode('') !!}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($response)
                                                <span class="badge bg-{{ $isCorrectResponse ? 'success' : 'danger' }} text-white">
                                                    {{ $isCorrectResponse ? 'Correct' : 'Incorrect' }}
                                                </span>
                                            @else
                                                <span class="badge bg-danger text-white">Incorrect</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-secondary py-4">No questions found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <div class="table-responsive">
                            <table class="table table-vcenter table-bordered">
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary py-4">No questions found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <style>
        .subject-title {
            font-weight: 800 !important;
        }

        [data-subject-scores-table] .subject-score-row:nth-child(odd) {
            background-color: rgba(36, 46, 66, 0.04);
        }

        .subject-card,
        .subject-card .card-body,
        .subject-card .card-body div {
            color: #fff !important;
        }

        .selected-option-line + .selected-option-line {
            margin-top: 0.25rem;
        }
        [data-response-row].row-odd {
            background-color: rgba(36, 46, 66, 0.04);
        }

        [data-response-row].row-even {
            background-color: transparent;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cards = Array.from(document.querySelectorAll('[data-subject-card]'));
            const rows = Array.from(document.querySelectorAll('[data-subject-row]'));
            const responseRows = Array.from(document.querySelectorAll('[data-response-row]'));

            if (cards.length === 0 || rows.length === 0) {
                return;
            }

            const applyZebra = () => {
                let visibleIndex = 0;
                responseRows.forEach((row) => {
                    const subjectSection = row.closest('[data-subject-row]');
                    if (row.classList.contains('d-none') || subjectSection?.classList.contains('d-none')) {
                        row.classList.remove('row-odd', 'row-even');
                        return;
                    }
                    visibleIndex += 1;
                    row.classList.toggle('row-odd', visibleIndex % 2 === 1);
                    row.classList.toggle('row-even', visibleIndex % 2 === 0);
                });
            };

            const setActive = (subjectId) => {
                cards.forEach((card) => {
                    const isActive = card.dataset.subjectId === subjectId;
                    card.classList.toggle('border-primary', isActive);
                    card.classList.toggle('shadow-sm', isActive);
                });

                rows.forEach((row) => {
                    if (subjectId === 'all') {
                        row.classList.remove('d-none');
                        return;
                    }
                    const matches = row.dataset.subjectId === subjectId;
                    row.classList.toggle('d-none', !matches);
                });

                applyZebra();
            };

            cards.forEach((card) => {
                card.addEventListener('click', () => setActive(card.dataset.subjectId));
            });

            setActive(cards[0].dataset.subjectId || 'all');
        });
    </script>
@endsection
