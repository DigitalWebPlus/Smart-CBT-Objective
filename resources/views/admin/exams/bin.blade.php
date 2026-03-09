@extends('admin.layouts.master')
@php
    use App\Models\Exam;
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <div class="page-pretitle">Assessments</div>
                    <h2 class="page-title">Exam Bin</h2>
                    <p class="text-secondary mb-0">Restore deleted exams or remove them permanently.</p>
                </div>
                <div class="btn-list mt-3 mt-md-0">
                    <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary">Back to list</a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Subjects</th>
                                    <th>Deleted</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exams as $exam)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-semibold">{{ $exam['title'] }}</span>
                                                <span class="badge bg-purple-lt">{{ Str::headline($exam['exam_type']) }}</span>
                                            </div>
                                            <div class="text-secondary text-truncate">
                                                {{ Str::limit($exam['description'], 80) ?: 'No description' }}
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $subjectCollection = $exam['subjects'] ?? collect();
                                            @endphp
                                            @if ($subjectCollection->isEmpty())
                                                <span class="text-secondary">No subjects</span>
                                            @else
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach ($subjectCollection as $subject)
                                                        <span class="badge bg-blue-lt">{{ $subject->code }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ optional($exam['deleted_at'])?->format('M d, h:i a') ?? '—' }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-list flex-nowrap">
                                                <form action="{{ route('admin.exams.restore', ['type' => $exam['exam_type'], 'exam' => $exam['id']]) }}" method="POST">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success" type="submit">Restore</button>
                                                </form>
                                                <form action="{{ route('admin.exams.force-delete', ['type' => $exam['exam_type'], 'exam' => $exam['id']]) }}" method="POST"
                                                    data-swal-confirm="Delete this exam permanently? This cannot be undone."
                                                    data-swal-title="Permanent Delete"
                                                    data-swal-confirm-button="Yes, delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary py-5">
                                            No deleted exams.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
