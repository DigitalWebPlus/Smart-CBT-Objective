@extends('admin.layouts.master')
@php
    use App\Models\Exam;
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="page-wrapper">
        @php
            $typeLabel = Str::headline($examType ?? Exam::TYPE_OBJECTIVE);
        @endphp

        <div class="page-header d-print-none">
            <div class="container-xl d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <div class="page-pretitle">Exams</div>
                    <h2 class="page-title">Create Exam</h2>
                    <p class="text-secondary mb-0">Define subjects, departments, and build the question set in one flow.</p>
                </div>
                <div class="btn-list mt-3 mt-md-0">
                    <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary">Back to list</a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Please fix the following issues:</strong>
                        <ul class="mt-2 mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.exams.store') }}" method="POST" class="exam-create-form">
                    @csrf

                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">

                                <input type="hidden" name="exam_type"
                                    value="{{ old('exam_type', $examType ?? Exam::TYPE_OBJECTIVE) }}">

                                @php
                                    $selectedSubjectIds = collect(old('subject_ids', []))
                                        ->map(fn ($id) => (int) $id)
                                        ->all();
                                    $selectedDepartmentIds = collect(old('department_ids', $defaultDepartmentIds ?? []))
                                        ->map(fn ($id) => (int) $id)
                                        ->all();
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
                                                $inputId = 'create-subject-' . $subject->id;
                                            @endphp
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="border rounded-3 h-100 p-3 subject-card {{ in_array($subject->id, $selectedSubjectIds, true) ? 'border-primary bg-light' : 'bg-white' }}">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="subject_ids[]"
                                                            value="{{ $subject->id }}" id="{{ $inputId }}"
                                                            @checked(in_array($subject->id, $selectedSubjectIds, true))>
                                                        <label class="form-check-label w-100" for="{{ $inputId }}">
                                                            <span class="badge bg-blue-lt text-uppercase mb-2">{{ $subject->code }}</span>
                                                            <span class="d-block fw-semibold">{{ $subject->name }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted">Choose at least one subject. Candidates will be able to switch between the ones you select.</small>
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
                                    <small class="text-muted">Only candidates in the selected departments will see this exam.</small>
                                    @error('department_ids')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                    @error('department_ids.*')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Exam Title</label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                                    @error('title')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        @foreach (Exam::STATUSES as $status)
                                            <option value="{{ $status }}"
                                                @selected(old('status', Exam::STATUS_DRAFT) === $status)>
                                                {{ Str::headline($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Start At</label>
                                    <input type="datetime-local" name="starts_at" class="form-control"
                                        value="{{ old('starts_at') }}">
                                    @error('starts_at')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">End At</label>
                                    <input type="datetime-local" name="ends_at" class="form-control"
                                        value="{{ old('ends_at') }}">
                                    @error('ends_at')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="number" name="duration_minutes" class="form-control" min="0"
                                        value="{{ old('duration_minutes', 60) }}">
                                    @error('duration_minutes')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Exam Attempt Review</label>
                                    @php
                                        $allowReview = old('settings.allow_review', false);
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
                                    <small class="text-muted">When enabled, the "Review Exam" button becomes available to candidates.</small>
                                    @error('settings.allow_review')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Result Visibility</label>
                                    @php
                                        $allowResultView = old('settings.allow_result_view', false);
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
                                    <small class="text-muted">Controls whether candidates can view results on their exam cards.</small>
                                    @error('settings.allow_result_view')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            @php
                                $builderExam = new Exam();
                                $builderExam->exam_type = $examType ?? Exam::TYPE_OBJECTIVE;
                            @endphp
                            @include('admin.exams.partials.question-builder', [
                                'exam' => $builderExam,
                                'examType' => $examType ?? Exam::TYPE_OBJECTIVE,
                                'questionBank' => $questionBank ?? [],
                                'selectedSubjectIds' => $selectedSubjectIds ?? [],
                            ])
                        </div>
                    </div>

                    <div class="mt-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                        <div class="text-secondary">You can edit questions later without affecting existing attempts.</div>
                        <button class="btn btn-primary" type="submit">
                            Create Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .exam-create-form .card {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        }

        .exam-create-form .subject-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .exam-create-form .subject-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
        }

        .exam-create-form .subject-card .badge {
            letter-spacing: 0.08em;
        }

        .exam-create-form .form-label {
            font-weight: 600;
        }
    </style>
@endpush

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
