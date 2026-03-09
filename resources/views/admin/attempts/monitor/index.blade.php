@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Monitor</div>
                    <h2 class="page-title">Monitor Live Exam</h2>
                    <p class="text-secondary mb-0">Track candidates currently taking exams.</p>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <form method="GET" class="card mb-4">
                    <div class="card-body row g-3 align-items-end">
                        <div class="col-md-5">
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
                        <div class="col-md-5">
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
                                    <th>Exam</th>
                                    <th>Candidate</th>
                                    <th>Status</th>
                                    <th>Started</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attempts as $attempt)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $attempt->exam?->title ?? 'Exam' }}</div>
                                            <div class="text-secondary small">ID: {{ $attempt->exam_id }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $attempt->candidate?->name ?? 'Candidate' }}</div>
                                            <div class="text-secondary small">{{ $attempt->candidate?->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-white">
                                                {{ \Illuminate\Support\Str::headline($attempt->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ optional($attempt->started_at)?->format('M d, h:i a') ?? '—' }}
                                        </td>
                                        <td class="text-end">
                                            @if ($attempt->exam)
                                                <a href="{{ route('admin.monitor-exams.attempts.show', [$attempt->exam, $attempt]) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary">No in-progress attempts found.</td>
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
