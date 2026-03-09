@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Results</div>
                    <h2 class="page-title">Result Management</h2>
                    <p class="text-secondary mb-0">View published candidate results and print result slips.</p>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-secondary text-uppercase small">Published Results</div>
                                <div class="h2 mb-0">{{ number_format((int) $publishedCount) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="GET" class="card mb-4 bg-azure-lt">
                    <div class="card-body row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Exam</label>
                            <select name="exam_id" class="form-select">
                                <option value="">All exams</option>
                                @foreach ($exams as $exam)
                                    <option value="{{ $exam->id }}" @selected((int) $examId === (int) $exam->id)>
                                        {{ $exam->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Candidate</label>
                            <input type="text" name="candidate" class="form-control" value="{{ $candidateQuery }}"
                                placeholder="Search name, email, registration">
                        </div>
                        <div class="col-md-2 text-end">
                            <button class="btn btn-primary w-100" type="submit">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Candidate</th>
                                    <th>Exam</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attempts as $attempt)
                                    @php
                                        $attemptScore = (float) ($attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? 0);
                                        $totalMarks = (float) ($attempt->computed_total_marks ?? 0);
                                        $percentage = (float) ($attempt->computed_percentage ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ ($attempts->currentPage() - 1) * $attempts->perPage() + $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $attempt->candidate?->name ?? 'Candidate' }}</div>
                                            <div class="text-secondary small">{{ $attempt->candidate?->email }}</div>
                                            <div class="text-secondary small">
                                                Reg: {{ $attempt->candidate?->registration_number ?? '—' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $attempt->exam?->title ?? 'Exam' }}</div>
                                            <div class="text-secondary small">Attempt #{{ $attempt->id }}</div>
                                        </td>
                                        <td>{{ number_format($attemptScore, 2) }} / {{ number_format($totalMarks, 2) }}</td>
                                        <td>{{ number_format($percentage, 2) }}%</td>
                                        <td>
                                            <span class="badge bg-green text-white">Published</span>
                                        </td>
                                        <td>{{ optional($attempt->submitted_at)->format('M d, Y h:i a') ?? '—' }}</td>
                                        <td class="text-end">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <a href="{{ route('admin.results.print', $attempt) }}" class="btn btn-sm btn-outline-secondary">
                                                    Print
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-secondary">No results found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $attempts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
