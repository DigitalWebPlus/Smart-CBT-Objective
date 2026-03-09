@extends('admin.layouts.master')
@php
    use App\Models\Exam;
    use Illuminate\Support\Str;

    $statusColors = [
        Exam::STATUS_PUBLISHED => 'green',
        Exam::STATUS_DRAFT => 'orange',
        Exam::STATUS_ARCHIVED => 'secondary',
    ];
    $badgeClass = $statusColors[$exam->status] ?? 'secondary';
@endphp

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <div class="page-pretitle">Exams</div>
                    <h2 class="page-title">Edit Exam</h2>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-{{ $badgeClass }} text-white">{{ Str::headline($exam->status) }}</span>
                        <div class="text-secondary">Total Marks: {{ number_format($exam->total_marks, 2) }}</div>
                    </div>
                </div>
                <div class="btn-list mt-3 mt-md-0">
                    <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary">Back to list</a>
                    <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST"
                        data-swal-confirm="Move this exam to the bin?"
                        data-swal-title="Delete Exam"
                        data-swal-confirm-button="Yes, delete">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Delete</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form action="{{ route('admin.exams.update', $exam) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row row-cards">
                        <div class="col-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Owner</label>
                                            <div class="form-control-plaintext">
                                                {{ $exam->admin?->name ?? 'Admin' }}
                                                @if ($exam->admin?->email)
                                                    <span class="text-secondary">({{ $exam->admin->email }})</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">Ownership is managed by the admin team.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select">
                                                @foreach (Exam::STATUSES as $status)
                                                    <option value="{{ $status }}" @selected(old('status', $exam->status) === $status)>
                                                        {{ Str::headline($status) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('status')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        @php
                                            $selectedSubjectIds = collect(old('subject_ids', $selectedSubjectIds ?? []))->map(fn ($id) => (int) $id)->all();
                                            $selectedDepartmentIds = collect(old('department_ids', $selectedDepartments ?? []))->map(fn ($id) => (int) $id)->all();
                                        @endphp
                                        <div class="col-12">
                                            <label class="form-label">Subjects</label>
                                            <div class="d-flex flex-wrap gap-2 mb-2" data-subject-actions>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-subject-select-all>
                                                    Select all subjects
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-subject-clear-all>
                                                    Clear selection
                                                </button>
                                            </div>
                                            <div class="row g-3" role="group" aria-label="Exam subjects">
                                                @foreach ($subjects as $subject)
                                                    @php
                                                        $inputId = 'admin-edit-subject-' . $subject->id;
                                                    @endphp
                                                    <div class="col-sm-6 col-lg-4">
                                                        <div class="border rounded-3 h-100 p-3 subject-card {{ in_array($subject->id, $selectedSubjectIds, true) ? 'border-primary bg-light' : 'bg-white' }}">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="subject_ids[]"
                                                                    value="{{ $subject->id }}" id="{{ $inputId }}" @checked(in_array($subject->id, $selectedSubjectIds, true))>
                                                                <label class="form-check-label w-100" for="{{ $inputId }}">
                                                                    <span class="badge bg-blue-lt text-uppercase mb-2">{{ $subject->code }}</span>
                                                                    <span class="d-block fw-semibold">{{ $subject->name }}</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <small class="text-muted">Select at least one subject. Question pools depend on these choices.</small>
                                            @error('subject_ids')
                                                <small class="text-danger d-block">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Departments</label>
                                            <select name="department_ids[]" class="form-select" multiple required size="{{ min(8, max(3, $departments->count())) }}">
                                                @forelse ($departments as $department)
                                                    <option value="{{ $department->id }}" @selected(in_array($department->id, $selectedDepartmentIds, true))>
                                                        {{ $department->name }}
                                                        @if ($department->code)
                                                            ({{ $department->code }})
                                                        @endif
                                                    </option>
                                                @empty
                                                    <option value="" disabled>No departments available</option>
                                                @endforelse
                                            </select>
                                            <small class="text-muted">Department selection controls candidate visibility.</small>
                                            @error('department_ids')
                                                <small class="text-danger d-block">{{ $message }}</small>
                                            @enderror
                                            @error('department_ids.*')
                                                <small class="text-danger d-block">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <input type="hidden" name="exam_type" value="{{ old('exam_type', $exam->exam_type) }}">
                                        <div class="col-md-12">
                                            <label class="form-label">Title</label>
                                            <input type="text" name="title" class="form-control" value="{{ old('title', $exam->title) }}">
                                            @error('title')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3">{{ old('description', $exam->description) }}</textarea>
                                            @error('description')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Start At</label>
                                            <input type="datetime-local" name="starts_at" class="form-control"
                                                value="{{ old('starts_at', optional($exam->starts_at)->format('Y-m-d\TH:i')) }}">
                                            @error('starts_at')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">End At</label>
                                            <input type="datetime-local" name="ends_at" class="form-control"
                                                value="{{ old('ends_at', optional($exam->ends_at)->format('Y-m-d\TH:i')) }}">
                                            @error('ends_at')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Duration (minutes)</label>
                                            <input type="number" min="0" name="duration_minutes" class="form-control"
                                                value="{{ old('duration_minutes', $exam->duration_minutes) }}">
                                            @error('duration_minutes')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Exam Attempt Review</label>
                                            @php
                                                $allowReview = old('settings.allow_review', $exam->allowsReview());
                                            @endphp
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="settings[allow_review]" value="0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="allow-review-toggle" name="settings[allow_review]" value="1"
                                                    @checked((bool) $allowReview)>
                                                <label class="form-check-label" for="allow-review-toggle">
                                                    Allow candidates to review the exam after submission.
                                                </label>
                                            </div>
                                            @error('settings.allow_review')
                                                <small class="text-danger d-block">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Result Visibility</label>
                                            @php
                                                $allowResultView = old('settings.allow_result_view', $exam->allowsResultView());
                                            @endphp
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="settings[allow_result_view]" value="0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="allow-result-toggle" name="settings[allow_result_view]" value="1"
                                                    @checked((bool) $allowResultView)>
                                                <label class="form-check-label" for="allow-result-toggle">
                                                    Allow candidates to view results.
                                                </label>
                                            </div>
                                            @error('settings.allow_result_view')
                                                <small class="text-danger d-block">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            @include('admin.exams.partials.question-builder')
                        </div>

                        <div class="col-12">
                            <div class="card">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Reminder:</strong> Saving updates the exam for all eligible candidates.
                                    </div>
                                    <button class="btn btn-primary" type="submit">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const selectAllBtn = document.querySelector('[data-subject-select-all]');
            const clearAllBtn = document.querySelector('[data-subject-clear-all]');
            const subjectInputs = () => Array.from(document.querySelectorAll('input[name="subject_ids[]"]'));

            const setAll = (checked) => {
                subjectInputs().forEach((input) => {
                    if (!input.disabled) {
                        input.checked = checked;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            };

            selectAllBtn?.addEventListener('click', () => setAll(true));
            clearAllBtn?.addEventListener('click', () => setAll(false));
        })();
    </script>
@endpush
