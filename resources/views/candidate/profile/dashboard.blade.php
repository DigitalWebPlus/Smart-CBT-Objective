@extends('candidate.layouts.app')
@php
    use App\Models\ExamAttempt;
    use Illuminate\Support\Str;

    $statusColors = [
        ExamAttempt::STATUS_IN_PROGRESS => 'warning',
        ExamAttempt::STATUS_SUBMITTED => 'info',
    ];

    $photoPath = $candidate->photo ?: 'uploads/candidates/default.jpg';
    $photoUrl = '/' . ltrim($photoPath, '/');
    $departments = $candidate->departments ?? collect();
@endphp

@section('title', 'My Profile')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <p class="text-uppercase small mb-1 text-white">Candidate profile</p>
                    <h1 class="h3 fw-bold mb-0">{{ __('Welcome, :name', ['name' => $candidate->name]) }}</h1>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('candidate.exams.index') }}" class="btn btn-outline-light">
                        <i class="bi bi-journal-text me-2"></i>{{ __('Browse exams') }}
                    </a>
                    <a href="{{ route('profile.edit') }}" class="btn btn-light">
                        <i class="bi bi-pencil-square me-2"></i>{{ __('Update profile') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                @include('candidate.partials.profile-card', ['candidate' => $candidate])
            </div>

            <div class="col-lg-8">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 glass-card h-100">
                            <div class="card-body p-4">
                                <p class="text-muted text-uppercase small mb-1">Total attempts</p>
                                <h3 class="fw-bold mb-0">{{ number_format($summary['total_attempts'] ?? 0) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 glass-card h-100">
                            <div class="card-body p-4">
                                <p class="text-muted text-uppercase small mb-1">Submitted attempts</p>
                                <h3 class="fw-bold mb-0">{{ number_format($summary['published_attempts'] ?? 0) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 glass-card h-100">
                            <div class="card-body p-4">
                                <p class="text-muted text-uppercase small mb-1">Best score</p>
                                <h3 class="fw-bold mb-0">{{ number_format((float) ($summary['best_score'] ?? 0), 1) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 rounded-4 glass-card mt-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                            <div>
                                <h4 class="h5 fw-semibold mb-1">Recent exam attempts</h4>
                                <p class="text-muted small mb-0">Your latest activity across available exams.</p>
                            </div>
                            <a href="{{ route('candidate.exams.index') }}" class="btn btn-outline-primary btn-sm">
                                {{ __('View all exams') }}
                            </a>
                        </div>

                        <div class="vstack gap-3">
                            @forelse ($recentAttempts as $attempt)
                                @php
                                    $exam = $attempt->exam;
                                    $subjectName = $exam?->subjects?->pluck('name')->implode(', ');
                                    $badge = $statusColors[$attempt->status] ?? 'secondary';
                                @endphp
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border rounded-4 p-3 bg-white">
                                    <div class="mb-2 mb-md-0">
                                        <p class="text-muted small mb-1">{{ $subjectName ?: 'General' }}</p>
                                        <h5 class="mb-1">{{ $exam?->title ?? 'Exam attempt' }}</h5>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="badge text-bg-{{ $badge }} text-uppercase status-badge">
                                                {{ str_replace('_', ' ', $attempt->status) }}
                                            </span>
                                            <span class="text-muted small">{{ optional($attempt->created_at)->format('M j, Y g:i A') }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-end">
                                            <p class="text-muted small mb-1">Score</p>
                                            @if ($attempt->status === \App\Models\ExamAttempt::STATUS_PUBLISHED)
                                                <h5 class="mb-0">{{ number_format((float) ($attempt->score ?? 0), 1) }}</h5>
                                            @elseif ($attempt->status === \App\Models\ExamAttempt::STATUS_CANCELED)
                                                <h5 class="mb-0">—</h5>
                                            @elseif ($attempt->status === \App\Models\ExamAttempt::STATUS_RETAKE)
                                                <h5 class="mb-0">Retake</h5>
                                            @else
                                                <h5 class="mb-0">Pending</h5>
                                            @endif
                                        </div>
                                        <a href="{{ route('candidate.exam-attempts.show', $attempt) }}" class="btn btn-outline-secondary">
                                            {{ __('View') }}
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-clipboard-x fs-2 mb-2"></i>
                                    <p class="mb-0">No attempts yet. Start with an available exam.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card border-0 rounded-4 glass-card mt-4">
                    <div class="card-body p-4">
                        <h4 class="h5 fw-semibold mb-2">Contact details</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="text-muted small mb-1">Email</p>
                                <p class="fw-semibold mb-0">{{ $candidate->email }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted small mb-1">Phone</p>
                                <p class="fw-semibold mb-0">{{ $candidate->phone ?? 'N/A' }}</p>
                            </div>
                            <div class="col-12">
                                <p class="text-muted small mb-1">Address</p>
                                <p class="fw-semibold mb-0">{{ $candidate->address ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
