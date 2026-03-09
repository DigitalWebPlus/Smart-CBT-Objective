@php
    use Illuminate\Support\Str;

    $currentExamType = $examType ?? $exam->exam_type ?? 'objective';
    $selectedSubjectIds = collect($selectedSubjectIds ?? ($exam?->subjects?->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    $subjectCardStyles = [
        ['bg' => '#E0E7FF', 'border' => '#C7D2FE'],
        ['bg' => '#DCFCE7', 'border' => '#86EFAC'],
        ['bg' => '#FFEDD5', 'border' => '#FDBA74'],
        ['bg' => '#FAE8FF', 'border' => '#F0ABFC'],
        ['bg' => '#EDE9FE', 'border' => '#C4B5FD'],
        ['bg' => '#CFFAFE', 'border' => '#67E8F9'],
    ];

    $questionSource = ($exam ?? null)?->exists ? $exam->questions : collect();
    $initialQuestions = $questionSource->map(function ($question, $index) {
        return [
            'objective_question_id' => $question->objective_question_id,
            'title' => Str::limit($question->question?->question_text ?? 'Question', 90),
            'marks' => (float) $question->marks,
            'display_order' => $question->display_order ?? $index + 1,
            'subject_id' => $question->question?->subject_id ? (int) $question->question->subject_id : null,
            'subject_name' => $question->question?->subject->name ?? 'Unknown subject',
            'subject_code' => $question->question?->subject->code ?? 'N/A',
        ];
    })->values();
@endphp

<div class="card" id="exam-question-builder"
    data-initial='@json($initialQuestions)'
    data-bank='@json($questionBank)'
    data-exam-type="{{ $currentExamType }}"
    data-selected-subjects='@json($selectedSubjectIds)'>
    <div class="card-header">
        <div>
            <h3 class="card-title">Question Builder</h3>
            <p class="text-secondary mb-0">Add {{ Str::headline($currentExamType) }} questions from your selected subjects. Total marks refresh after saving.</p>
        </div>
    </div>
    <div class="card-body">
        @if (empty($questionBank))
            <div class="alert alert-warning mb-0">
                Select at least one subject for this exam to start building questions.
            </div>
        @else
            <div class="alert alert-warning mb-3 @if (! empty($selectedSubjectIds)) d-none @endif" data-role="subject-required-alert">
                Select at least one subject for this exam to start building questions.
            </div>
            <p class="text-muted small mb-4">Each subject has its own builder. Add questions per subject to keep them organized.</p>
            <div class="vstack gap-4" id="subject-question-builders">
                @foreach ($questionBank as $subject)
                    @php
                        $styleIndex = $loop->index % count($subjectCardStyles);
                        $style = $subjectCardStyles[$styleIndex];
                    @endphp
                    <div class="card subject-builder" data-subject-builder data-subject-id="{{ $subject['id'] }}"
                        style="background-color: {{ $style['bg'] }}; border-color: {{ $style['border'] }};">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-3">
                                <div>
                                    <strong class="fs-1">{{ $subject['name'] }}</strong>
                                    <span class="badge bg-blue-lt text-uppercase ms-2">{{ $subject['code'] }}</span>
                                    <p class="mb-0 text-muted small">Manage {{ Str::headline($currentExamType) }} questions for this subject.</p>
                                </div>
                                <div class="text-lg-end">
                                    <p class="mb-0 text-muted small">Questions added</p>
                                    <h4 class="mb-0" data-role="subject-count">0</h4>
                                </div>
                            </div>

                            <div class="row g-3 align-items-end mb-2">
                                <div class="col-md-7">
                                    <label class="form-label">Question Bank</label>
                                    <select class="form-select" data-role="question-select">
                                        <option value="">Select a question</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Marks</label>
                                    <input type="number" step="0.5" min="0" class="form-control" data-role="marks-input" value="0" placeholder="0">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button class="btn btn-primary" type="button" data-role="add-question">
                                        <i class="ti ti-plus"></i>
                                        <span class="ms-1">Add</span>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mb-3">
                                <button class="btn btn-link" type="button" data-role="add-all">
                                    <i class="ti ti-stack"></i>
                                    Add all questions from this subject
                                </button>
                            </div>

                            <div class="vstack gap-2" data-role="question-list"></div>
                            <div class="alert alert-secondary text-center mt-3 mb-0" data-role="empty-state">
                                No questions added for this subject yet.
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        <div id="exam-questions-hidden-inputs"></div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const builder = document.getElementById('exam-question-builder');
            if (!builder) {
                return;
            }

            const state = JSON.parse(builder.dataset.initial || '[]');
            const bank = JSON.parse(builder.dataset.bank || '[]');
            const examType = builder.dataset.examType || 'objective';
            const hiddenInputs = document.getElementById('exam-questions-hidden-inputs');
            const subjectRequiredAlert = builder.querySelector('[data-role="subject-required-alert"]');
            const selectedSubjectIds = new Set(
                JSON.parse(builder.dataset.selectedSubjects || '[]').map((id) => String(id))
            );

            if (!bank.length) {
                return;
            }

            const typeLabel = 'Objective';
            const subjectMap = new Map();
            bank.forEach((subject) => {
                subjectMap.set(String(subject.id), {
                    ...subject,
                    id: Number(subject.id),
                    questions: (subject.questions || []).map((question) => ({
                        ...question,
                        id: Number(question.id),
                        marks: Number(question.marks ?? 0),
                        question_text: question.question_text,
                        subject_id: Number(subject.id),
                    })),
                });
            });

            const subjectUIMap = new Map();
            builder.querySelectorAll('[data-subject-builder]').forEach((section) => {
                const subjectId = String(section.dataset.subjectId || '');
                if (!subjectId) {
                    return;
                }
                subjectUIMap.set(subjectId, {
                    subjectId,
                    section,
                    questionSelect: section.querySelector('[data-role="question-select"]'),
                    marksInput: section.querySelector('[data-role="marks-input"]'),
                    addBtn: section.querySelector('[data-role="add-question"]'),
                    addAllBtn: section.querySelector('[data-role="add-all"]'),
                    questionList: section.querySelector('[data-role="question-list"]'),
                    countLabel: section.querySelector('[data-role="subject-count"]'),
                    emptyState: section.querySelector('[data-role="empty-state"]'),
                });
            });

            const disableControls = (ui) => {
                if (!ui) {
                    return;
                }
                if (ui.questionSelect) ui.questionSelect.disabled = true;
                if (ui.marksInput) {
                    ui.marksInput.disabled = true;
                    ui.marksInput.value = 0;
                }
                if (ui.addBtn) ui.addBtn.disabled = true;
                if (ui.addAllBtn) ui.addAllBtn.disabled = true;
            };

            const populateQuestionOptions = (subjectId) => {
                const ui = subjectUIMap.get(subjectId);
                if (!ui || !ui.questionSelect) {
                    return;
                }
                const subject = subjectMap.get(subjectId);
                ui.questionSelect.innerHTML = '';

                if (!subject) {
                    ui.questionSelect.innerHTML = '<option value="">Subject not available</option>';
                    disableControls(ui);
                    return;
                }

                if (!subject.questions.length) {
                    ui.questionSelect.innerHTML = '<option value="">No questions available</option>';
                    disableControls(ui);
                    return;
                }

                subject.questions.forEach((question, index) => {
                    const option = document.createElement('option');
                    option.value = question.id;
                    const preview = (question.question_text || '').substring(0, 80);
                    option.textContent = preview ? preview : `Question #${question.id}`;
                    ui.questionSelect.appendChild(option);
                    if (index === 0 && ui.marksInput) {
                        ui.marksInput.value = question.marks ?? 0;
                    }
                });

                ui.questionSelect.disabled = false;
                if (ui.marksInput) ui.marksInput.disabled = false;
                if (ui.addBtn) ui.addBtn.disabled = false;
                if (ui.addAllBtn) ui.addAllBtn.disabled = false;
            };

            const getSubjectQuestion = (subjectId, questionId) => {
                const subject = subjectMap.get(subjectId);
                return subject?.questions.find((question) => question.id === questionId);
            };

            const renderHiddenInputs = () => {
                if (!hiddenInputs) {
                    return;
                }
                hiddenInputs.innerHTML = '';
                state.forEach((question, index) => {
                    ['objective_question_id', 'marks', 'display_order'].forEach((field) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `questions[${index}][${field}]`;
                        input.value = question[field];
                        hiddenInputs.appendChild(input);
                    });
                });
            };

            const renderSubjectTables = () => {
                const subjectCounters = {};
                subjectUIMap.forEach((ui) => {
                    if (ui.questionList) {
                        ui.questionList.innerHTML = '';
                    }
                });

                state.forEach((question, index) => {
                    const subjectId = String(question.subject_id ?? '');
                    const ui = subjectUIMap.get(subjectId);
                    if (!ui || !ui.questionList) {
                        return;
                    }
                    subjectCounters[subjectId] = (subjectCounters[subjectId] ?? 0) + 1;
                    const count = subjectCounters[subjectId];

                    const card = document.createElement('div');
                    card.className = 'card border-0 shadow-sm';
                    card.innerHTML = `
                        <div class="card-body py-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                <div>
                                    <div class="text-muted small">Question #${count} • ${typeLabel}</div>
                                    <div class="fw-semibold">${question.title}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div>
                                        <label class="form-label small mb-1">Marks</label>
                                        <input type="number" class="form-control form-control-sm"
                                            step="0.5" min="0" value="${question.marks}"
                                            data-index="${index}" data-role="row-marks-input">
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-index="${index}" data-role="remove-question">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    ui.questionList.appendChild(card);
                });

                subjectUIMap.forEach((ui) => {
                    const count = subjectCounters[ui.subjectId] ?? 0;
                    if (ui.countLabel) {
                        ui.countLabel.textContent = String(count);
                    }
                    if (ui.emptyState) {
                        ui.emptyState.classList.toggle('d-none', count > 0);
                    }
                });
            };

            const setQuestionMarks = (index, value) => {
                const marks = Number(value);
                if (!Number.isFinite(marks)) {
                    return;
                }
                if (!state[index]) {
                    return;
                }
                state[index].marks = marks;
                renderHiddenInputs();
            };

            const addQuestion = (subjectId, questionId, marks) => {
                const question = getSubjectQuestion(subjectId, questionId);
                if (!question) {
                    return;
                }

                const alreadyExists = state.some((item) => item.objective_question_id === question.id);
                if (alreadyExists) {
                    return;
                }

                state.push({
                    objective_question_id: question.id,
                    title: question.question_text.substring(0, 90),
                    marks: Number(marks ?? question.marks ?? 0),
                    display_order: state.length + 1,
                    subject_id: question.subject_id ?? Number(subjectId),
                });

                renderSubjectTables();
                renderHiddenInputs();
            };

            const addAllQuestions = (subjectId) => {
                const subject = subjectMap.get(subjectId);
                if (!subject) {
                    return;
                }
                subject.questions.forEach((question) => {
                    const alreadyExists = state.some((item) => item.objective_question_id === question.id);
                    if (alreadyExists) {
                        return;
                    }
                    state.push({
                        objective_question_id: question.id,
                        title: question.question_text.substring(0, 90),
                        marks: Number(question.marks ?? 0),
                        display_order: state.length + 1,
                        subject_id: question.subject_id ?? Number(subjectId),
                    });
                });

                renderSubjectTables();
                renderHiddenInputs();
            };

            const removeQuestion = (index) => {
                state.splice(index, 1);
                state.forEach((question, idx) => {
                    question.display_order = idx + 1;
                });
                renderSubjectTables();
                renderHiddenInputs();
            };

            const updateSubjectVisibility = () => {
                let visibleCount = 0;
                subjectUIMap.forEach((ui, subjectId) => {
                    const isSelected = selectedSubjectIds.has(subjectId);
                    ui.section.classList.toggle('d-none', !isSelected);
                    if (isSelected) {
                        populateQuestionOptions(subjectId);
                        visibleCount += 1;
                    } else {
                        disableControls(ui);
                    }
                });

                if (subjectRequiredAlert) {
                    subjectRequiredAlert.classList.toggle('d-none', visibleCount > 0);
                }
            };

            const refreshSelectedSubjects = () => {
                selectedSubjectIds.clear();
                document.querySelectorAll('input[name="subject_ids[]"]:checked').forEach((input) => {
                    selectedSubjectIds.add(String(input.value));
                });
                updateSubjectVisibility();
            };

            subjectUIMap.forEach((ui) => {

                ui.addBtn?.addEventListener('click', () => {
                    const questionId = Number(ui.questionSelect?.value);
                    if (!questionId) {
                        return;
                    }
                    addQuestion(ui.subjectId, questionId, ui.marksInput?.value);
                });

                ui.addAllBtn?.addEventListener('click', () => {
                    addAllQuestions(ui.subjectId);
                });

                ui.questionSelect?.addEventListener('change', (event) => {
                    const selectedId = Number(event.target?.value);
                    const question = getSubjectQuestion(ui.subjectId, selectedId);
                    if (question && ui.marksInput) {
                        ui.marksInput.value = question.marks ?? 0;
                    }
                });
            });

            builder.addEventListener('input', (event) => {
                const target = event.target;
                if (target?.dataset?.role === 'row-marks-input') {
                    const index = Number(target.dataset.index);
                    setQuestionMarks(index, target.value);
                }
            });

            builder.addEventListener('click', (event) => {
                const target = event.target.closest('[data-role="remove-question"]');
                if (!target) {
                    return;
                }
                const index = Number(target.dataset.index);
                if (Number.isNaN(index)) {
                    return;
                }
                removeQuestion(index);
            });

            updateSubjectVisibility();
            document.querySelectorAll('input[name="subject_ids[]"]').forEach((input) => {
                input.addEventListener('change', refreshSelectedSubjects);
            });

            renderSubjectTables();
            renderHiddenInputs();
        });
    </script>
@endpush
