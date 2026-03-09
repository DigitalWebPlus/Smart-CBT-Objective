@extends('candidate.layouts.app')

@section('title', $exam->title)

@section('content')
    <div class="container py-4 border border-1 rounded-4">
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h3 fw-bold mb-2">{{ $exam->title }}</h1>
                    </div>
                    <a href="{{ route('candidate.exams.index') }}" class="btn btn-outline-light">
                        <i class="bi bi-arrow-left me-1"></i>Back to exams
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                @include('candidate.partials.profile-card', ['candidate' => auth()->user()])
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-3 bg-primary text-white">
                    <div class="card-body p-4">
                        <h2 class="h6 text-uppercase text-white fw-bold mb-2">Exam description</h2>
                        <p class="mb-0 text-white">{{ $exam->description ?? 'No description provided.' }}</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="text-white text-uppercase small">Duration</div>
                                    <i class="bi bi-clock-fill fs-3"></i>
                                </div>
                                <div class="h4 fw-semibold text-white">{{ $exam->duration_minutes ?? 0 }} mins</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="text-white text-uppercase small">Total marks</div>
                                    <i class="bi bi-trophy-fill fs-3"></i>
                                </div>
                                <div class="h4 fw-semibold text-white">{{ number_format((float) ($exam->total_marks ?: $exam->questions->sum('marks'))) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="text-white text-uppercase small">Questions</div>
                                    <i class="bi bi-list-check fs-3"></i>
                                </div>
                                <div class="h4 fw-semibold text-white">{{ $exam->questions->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h2 class="h5 fw-semibold mb-0">Subjects</h2>
                        <span class="text-muted small">{{ $exam->subjects->count() }} total</span>
                    </div>
                    @if ($exam->subjects->isNotEmpty())
                        <div class="row g-3">
                            @foreach ($exam->subjects as $subject)
                                @php
                                    $subjectQuestionCount = $exam->questions
                                        ->filter(fn ($examQuestion) => (int) ($examQuestion->question?->subject_id ?? 0) === (int) $subject->id)
                                        ->count();
                                    $subjectTheme = 'bg-primary';
                                @endphp
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100 text-white {{ $subjectTheme }}">
                                        <div class="card-body d-flex align-items-start justify-content-between fw-semibold">
                                            <div>
                                                <h2 class="h5 mb-2 text-white fw-bolder">{{ $subject->name ?? 'Subject' }}</h2>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge bg-light text-dark">Code: {{ $subject->code ?: 'N/A' }}</span>
                                                    <span class="badge bg-light text-dark">{{ $subjectQuestionCount }} questions</span>
                                                </div>
                                            </div>
                                            <div class="display-6 fw-bolder text-white">{{ $loop->iteration }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info mb-0">No subjects are assigned to this exam yet.</div>
                    @endif
                </div>

                <div class="mt-4">
                    <form method="POST" action="{{ route('candidate.exams.attempts.start', $exam) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">Start Exam</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
            const serverNow = Number.parseInt(countdownCard.dataset.serverNow ?? '', 10);

            if (!Number.isFinite(startTimestamp) || !Number.isFinite(serverNow)) {
                return;
            }

            const serverNowMs = serverNow * 1000;
            const startMs = startTimestamp * 1000;
            const clientNowMs = Date.now();

            const pad = (value) => String(value).padStart(2, '0');
            const formatCountdown = (totalSeconds) => {
                const safeSeconds = Math.max(totalSeconds, 0);
                const hours = Math.floor(safeSeconds / 3600);
                const minutes = Math.floor((safeSeconds % 3600) / 60);
                const seconds = Math.floor(safeSeconds % 60);
                const hoursDisplay = hours >= 100 ? String(hours) : pad(hours);
                return `${hoursDisplay}:${pad(minutes)}:${pad(seconds)}`;
            };

            const tick = () => {
                const nowMs = serverNowMs + (Date.now() - clientNowMs);
                const remainingSeconds = Math.ceil((startMs - nowMs) / 1000);

                if (remainingSeconds <= 0) {
                    if (valueEl) {
                        valueEl.textContent = '00:00:00';
                    }
                    if (startButton) {
                        startButton.removeAttribute('disabled');
                    }
                    if (noteEl) {
                        noteEl.textContent = 'By starting you confirm you understand and will abide by the exam regulations.';
                    }
                    countdownCard.classList.remove('alert-warning');
                    countdownCard.classList.add('alert-success');
                    countdownCard.querySelector('.text-muted')?.classList.add('text-success');
                    clearInterval(timerId);
                    return;
                }

                if (valueEl) {
                    valueEl.textContent = formatCountdown(remainingSeconds);
                }
            };

            tick();
            const timerId = window.setInterval(tick, 1000);
        })();
    </script>
@endpush
