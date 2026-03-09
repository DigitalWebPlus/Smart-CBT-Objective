@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Result Slip</div>
                    <h2 class="page-title">{{ $attempt->candidate?->name ?? 'Candidate' }}</h2>
                    <p class="text-secondary mb-0">{{ $attempt->exam?->title ?? 'Exam' }}</p>
                </div>
                <div class="btn-list">
                    <a href="{{ route('admin.results.index') }}" class="btn btn-outline-secondary">Back</a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="card-body p-4">
                        @php
                            $photoPath = $attempt->candidate?->photo ?: 'uploads/candidates/default.jpg';
                            $photoUrl = '/' . ltrim($photoPath, '/');
                        @endphp
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h3 class="mb-1">Candidate Result</h3>
                                <div class="text-secondary">Generated {{ now()->format('M d, Y h:i a') }}</div>
                            </div>
                            <span class="badge {{ $attempt->status === \App\Models\ExamAttempt::STATUS_PUBLISHED ? 'bg-green text-white' : 'bg-yellow text-dark' }} fs-5 px-3 py-2">
                                {{ \Illuminate\Support\Str::headline($attempt->status) }}
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-4">
                            <img src="{{ $photoUrl }}" alt="{{ $attempt->candidate?->name ?? 'Candidate' }}"
                                class="rounded border" style="width: 96px; height: 96px; object-fit: cover;">
                            <div>
                                <div class="fw-semibold">{{ $attempt->candidate?->name ?? 'Candidate' }}</div>
                                <div class="text-secondary small">{{ $attempt->candidate?->email ?? '—' }}</div>
                                <div class="text-secondary small">Reg: {{ $attempt->candidate?->registration_number ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="fw-semibold">Candidate Name</div>
                                <div>{{ $attempt->candidate?->name ?? '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="fw-semibold">Registration Number</div>
                                <div>{{ $attempt->candidate?->registration_number ?? '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="fw-semibold">Email</div>
                                <div>{{ $attempt->candidate?->email ?? '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="fw-semibold">Submitted At</div>
                                <div>{{ optional($attempt->submitted_at)->format('M d, Y h:i a') ?? '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="fw-semibold">Total Score</div>
                                <div>{{ number_format((float) ($attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? 0), 2) }} / {{ number_format($totalMarks, 2) }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="fw-semibold">Percentage</div>
                                <div>{{ number_format($percentage, 2) }}%</div>
                            </div>
                        </div>

                        @php($subjectScores = collect($attempt->subject_scores ?? []))
                        @if ($subjectScores->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-vcenter">
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
        </div>
    </div>
@endsection
