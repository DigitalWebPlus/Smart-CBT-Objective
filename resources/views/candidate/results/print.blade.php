@extends('candidate.layouts.app')

@section('title', 'Print Result')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-4 d-print-none bg-primary text-white">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <p class="text-uppercase small mb-1 text-white">Result Slip</p>
                    <h1 class="h4 fw-bold mb-0">{{ $attempt->exam?->title ?? 'Exam' }}</h1>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('candidate.exams.index') }}" class="btn btn-outline-light">Back to Exams</a>
                    <button type="button" class="btn btn-light" onclick="window.print()">Print</button>
                </div>
            </div>
        </div>

        <div class="card border-0 rounded-4 glass-card">
            <div class="card-body p-4">
                @php
                    $photoPath = $attempt->candidate?->photo ?: 'uploads/candidates/default.jpg';
                    $photoUrl = '/' . ltrim($photoPath, '/');
                @endphp
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="mb-1">Candidate Result</h3>
                        <div class="text-muted">Generated {{ now()->format('M d, Y h:i a') }}</div>
                    </div>
                    <span class="badge text-bg-success text-uppercase">Published</span>
                </div>

                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="{{ $photoUrl }}" alt="{{ $attempt->candidate?->name ?? 'Candidate' }}"
                        class="rounded border" style="width: 96px; height: 96px; object-fit: cover;">
                    <div>
                        <div class="fw-semibold">{{ $attempt->candidate?->name ?? 'Candidate' }}</div>
                        <div class="text-muted small">{{ $attempt->candidate?->email ?? '—' }}</div>
                        <div class="text-muted small">Reg: {{ $attempt->candidate?->registration_number ?? '—' }}</div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Candidate Name</p>
                        <p class="fw-semibold mb-0">{{ $attempt->candidate?->name ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Registration Number</p>
                        <p class="fw-semibold mb-0">{{ $attempt->candidate?->registration_number ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Email</p>
                        <p class="fw-semibold mb-0">{{ $attempt->candidate?->email ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Submitted At</p>
                        <p class="fw-semibold mb-0">{{ optional($attempt->submitted_at)->format('M d, Y h:i a') ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Total Score</p>
                        <p class="fw-semibold mb-0">
                            {{ number_format((float) ($attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? 0), 2) }}
                            / {{ number_format($totalMarks, 2) }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Percentage</p>
                        <p class="fw-semibold mb-0">{{ number_format($percentage, 2) }}%</p>
                    </div>
                </div>

                @php($subjectScores = collect($attempt->subject_scores ?? []))
                @if ($subjectScores->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subjectScores as $score)
                                    <tr>
                                        <td>{{ $score['subject_name'] ?? $score['subject_code'] ?? 'Subject' }}</td>
                                        <td>{{ number_format((float) ($score['total_score'] ?? 0), 2) }} / {{ number_format((float) ($score['max_marks'] ?? 0), 2) }}</td>
                                        <td>{{ number_format((float) ($score['percentage'] ?? 0), 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
