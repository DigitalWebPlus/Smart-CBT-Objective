@extends('admin.layouts.master')

@section('content')
    @php
        $objectiveTypeLabels = [
            \App\Models\ObjectiveQuestion::TYPE_MSA => 'Multiple Choice (Single Answer)',
            \App\Models\ObjectiveQuestion::TYPE_MMA => 'Multiple Choice (Multiple Answers)',
            \App\Models\ObjectiveQuestion::TYPE_TOF => 'True or False',
        ];
    @endphp
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="page-pretitle">Question Bank</div>
                    <h2 class="page-title">
                        Questions
                        @if (($mode ?? 'summary') === 'subject')
                            <span class="text-secondary">· {{ $subject->name }} ({{ $subject->code }})</span>
                        @endif
                    </h2>
                </div>
                <div class="d-flex gap-2">
                    @if (($mode ?? 'summary') === 'subject')
                        <a href="{{ route('admin.question-banks.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left"></i>
                            Back to Subjects
                        </a>
                        <a href="{{ route('admin.question-banks.create', ['subject_id' => $subject->id]) }}" class="btn btn-primary">
                            <i class="ti ti-plus"></i>
                            New Question
                        </a>
                        <a href="{{ route('admin.question-banks.upload', ['subject_id' => $subject->id]) }}" class="btn btn-outline-primary">
                            <i class="ti ti-upload"></i>
                            Upload Questions
                        </a>
                        <div class="btn-group">
                            <a href="{{ route('admin.question-banks.export', ['subject_id' => $subject->id]) }}" class="btn btn-outline-secondary">
                                <i class="ti ti-download"></i>
                                Download JSON
                            </a>
                            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('admin.question-banks.export', ['subject_id' => $subject->id, 'format' => 'csv']) }}">Download CSV</a>
                                <a class="dropdown-item" href="{{ route('admin.question-banks.export', ['subject_id' => $subject->id, 'format' => 'xlsx']) }}">Download XLSX</a>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('admin.question-banks.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus"></i>
                            New Question
                        </a>
                        <a href="{{ route('admin.question-banks.upload') }}" class="btn btn-outline-primary">
                            <i class="ti ti-upload"></i>
                            Upload Questions
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards mb-3">
                    @php
                        $typeLabels = [
                            'msa' => 'Multiple Choice (Single)',
                            'mma' => 'Multiple Choice (Multiple)',
                            'tof' => 'True or False',
                        ];
                    @endphp
                    @foreach ($typeLabels as $type => $label)
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-secondary">{{ $label }}</div>
                                    <div class="h1">{{ $typeSummary[$type] ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (($mode ?? 'summary') === 'summary')
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th class="w-1">#</th>
                                        <th>Subject</th>
                                        <th>Subject Code</th>
                                        <th>Total Questions</th>
                                        <th>MSA</th>
                                        <th>MMA</th>
                                        <th>TOF</th>
                                        <th>Images</th>
                                        <th class="w-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($subjects as $subjectRow)
                                        <tr>
                                            <td>{{ $subjects->firstItem() + $loop->index }}</td>
                                            <td class="fw-bold">{{ $subjectRow->name }}</td>
                                            <td>{{ $subjectRow->code }}</td>
                                            <td><span class="badge bg-blue-lt">{{ $subjectRow->total_objective_questions }}</span></td>
                                            <td>{{ $subjectRow->msa_count }}</td>
                                            <td>{{ $subjectRow->mma_count }}</td>
                                            <td>{{ $subjectRow->tof_count }}</td>
                                            <td><span class="badge bg-indigo-lt">{{ $subjectRow->image_count }}</span></td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    <a href="{{ route('admin.question-banks.index', ['subject_id' => $subjectRow->id]) }}" class="btn btn-sm btn-outline-primary">
                                                        View Questions
                                                    </a>
                                                    <a href="{{ route('admin.question-banks.create', ['subject_id' => $subjectRow->id]) }}" class="btn btn-sm btn-outline-secondary">
                                                        Add Question
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-secondary">No subjects found. Create a subject first.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $subjects->links() }}
                        </div>
                    </div>
                @else
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th class="w-1">#</th>
                                        <th>Type</th>
                                        <th>Marks</th>
                                        <th>Options</th>
                                        <th>Image</th>
                                        <th>Status</th>
                                        <th>Updated</th>
                                        <th class="w-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($questions as $question)
                                        @php
                                            $questionPayload = [
                                                'id' => $question->id,
                                                'subject_name' => optional($question->subject)->name,
                                                'subject_code' => optional($question->subject)->code,
                                                'question_text' => $question->question_text,
                                                'question_type' => $question->question_type,
                                                'marks' => $question->marks,
                                                'explanation' => $question->explanation,
                                                'is_active' => (bool) $question->is_active,
                                                'image_url' => $question->image_path ? asset($question->image_path) : null,
                                                'options' => $question->options
                                                    ->map(fn ($option) => [
                                                        'label' => $option->label,
                                                        'description' => $option->description,
                                                        'is_correct' => (bool) $option->is_correct,
                                                    ])
                                                    ->values()
                                                    ->toArray(),
                                            ];
                                        @endphp
                                        <tr>
                                            <td>{{ $questions->firstItem() + $loop->index }}</td>
                                            <td class="text-uppercase">{{ $question->question_type }}</td>
                                            <td>{{ number_format($question->marks, 2) }}</td>
                                            <td><span class="badge bg-cyan-lt">{{ $question->options_count }}</span></td>
                                            <td>
                                                <span class="badge bg-{{ $question->image_path ? 'green' : 'secondary' }}-lt">
                                                    {{ $question->image_path ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $question->is_active ? 'success' : 'secondary' }}">
                                                    {{ $question->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>{{ optional($question->updated_at)->format('M d, Y') }}</td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-info"
                                                        data-objective-view
                                                        data-question='@json($questionPayload)'
                                                        data-clone-url="{{ route('admin.question-banks.create', ['subject_id' => $subject->id, 'clone_from' => $question->id]) }}">
                                                        View
                                                    </button>
                                                    <a href="{{ route('admin.question-banks.edit', $question) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                    <form action="{{ route('admin.question-banks.destroy', $question) }}" method="POST"
                                                        data-swal-confirm
                                                        data-swal-title="Delete this question?"
                                                        data-swal-confirm="You will permanently remove this question from the bank."
                                                        data-swal-confirm-button="Yes, delete"
                                                        data-swal-cancel-button="Cancel">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-secondary">No questions found for this subject.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $questions->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="objectiveQuestionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-secondary mb-1" data-field="subject"></div>
                    <h3 class="mb-3" data-field="question_text"></h3>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="text-secondary">Type</div>
                            <div class="fw-bold" data-field="question_type"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">Marks</div>
                            <div class="fw-bold" data-field="marks"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="text-secondary">Status</div>
                            <div class="fw-bold" data-field="status"></div>
                        </div>
                        <div class="col-md-8">
                            <div class="text-secondary">Explanation</div>
                            <div data-field="explanation" class="text-break"></div>
                        </div>
                    </div>

                    <div class="mb-3 d-none" data-image-wrapper>
                        <div class="text-secondary mb-2">Question Image</div>
                        <img src="" alt="Question image" class="img-fluid rounded border" data-field="image">
                    </div>

                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="mb-0">Options</h4>
                            <span class="text-secondary" data-field="option-help"></span>
                        </div>
                        <div class="list-group" data-options></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-primary" data-clone-link>Use to Create New Question</a>
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalElement = document.getElementById('objectiveQuestionModal');
            if (!modalElement || typeof window.bootstrap === 'undefined') {
                return;
            }

            const modal = new window.bootstrap.Modal(modalElement);
            const field = (name) => modalElement.querySelector(`[data-field="${name}"]`);
            const optionsContainer = modalElement.querySelector('[data-options]');
            const cloneButton = modalElement.querySelector('[data-clone-link]');
            const imageWrapper = modalElement.querySelector('[data-image-wrapper]');
            const imageElement = modalElement.querySelector('img[data-field="image"]');
            const typeLabels = @json($objectiveTypeLabels);
            const optionHelp = field('option-help');

            const formatBoolean = (value, trueLabel, falseLabel) => (value ? trueLabel : falseLabel);

            document.querySelectorAll('[data-objective-view]').forEach((button) => {
                button.addEventListener('click', () => {
                    const payload = JSON.parse(button.getAttribute('data-question'));
                    field('subject').textContent = payload.subject_name
                        ? `${payload.subject_name}${payload.subject_code ? ` (${payload.subject_code})` : ''}`
                        : 'No subject';
                    field('question_text').textContent = payload.question_text || 'No question text provided.';
                    field('question_type').textContent = typeLabels[payload.question_type] || (payload.question_type || '—');
                    field('marks').textContent = payload.marks ? Number(payload.marks).toFixed(2) : '0.00';
                    field('status').textContent = formatBoolean(payload.is_active, 'Active', 'Inactive');
                    field('explanation').textContent = payload.explanation || '—';

                    if (payload.image_url) {
                        imageWrapper.classList.remove('d-none');
                        imageElement.src = payload.image_url;
                    } else {
                        imageWrapper.classList.add('d-none');
                        imageElement.src = '';
                    }

                    optionsContainer.innerHTML = '';
                    const options = Array.isArray(payload.options) ? payload.options : [];
                    optionHelp.textContent = options.length ? `${options.length} option${options.length === 1 ? '' : 's'}` : 'No options added yet.';
                    if (options.length === 0) {
                        const emptyState = document.createElement('div');
                        emptyState.className = 'text-secondary';
                        emptyState.textContent = 'No options found for this question.';
                        optionsContainer.appendChild(emptyState);
                    } else {
                        options.forEach((option, index) => {
                            const item = document.createElement('div');
                            item.className = 'list-group-item';
                            const label = document.createElement('div');
                            label.className = 'd-flex justify-content-between align-items-center';
                            const title = document.createElement('strong');
                            title.textContent = `${String.fromCharCode(65 + index)}. ${option.label || 'Option'}`;
                            const badge = document.createElement('span');
                            badge.className = `badge ${option.is_correct ? 'bg-success' : 'bg-secondary'}`;
                            badge.textContent = option.is_correct ? 'Correct' : 'Incorrect';
                            label.appendChild(title);
                            label.appendChild(badge);

                            item.appendChild(label);
                            if (option.description && option.description !== option.label) {
                                const description = document.createElement('div');
                                description.className = 'text-secondary mt-1';
                                description.textContent = option.description;
                                item.appendChild(description);
                            }
                            optionsContainer.appendChild(item);
                        });
                    }

                    cloneButton.setAttribute('href', button.getAttribute('data-clone-url'));
                    modal.show();
                });
            });

        });
    </script>
@endpush
