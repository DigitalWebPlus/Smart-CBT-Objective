@php
    $typeOptions = [
        \App\Models\ObjectiveQuestion::TYPE_MSA => 'Multiple Choice (Single Answer)',
        \App\Models\ObjectiveQuestion::TYPE_MMA => 'Multiple Choice (Multiple Answers)',
        \App\Models\ObjectiveQuestion::TYPE_TOF => 'True or False',
    ];
    $optionData = old('options');

    if (! $optionData && isset($question) && $question->relationLoaded('options')) {
        $optionData = $question->options->map(function ($option) {
            return [
                'label' => $option->label,
                'description' => $option->description,
                'is_correct' => $option->is_correct,
            ];
        })->toArray();
    }

    if (! $optionData || count($optionData) < 2) {
        $minimum = max(4, $optionData ? count($optionData) : 0);
        $optionData = $optionData ?? [];
        while (count($optionData) < $minimum) {
            $optionData[] = ['label' => null, 'description' => null, 'is_correct' => false];
        }
    }
    $selectedSubjectId = $selectedSubjectId ?? null;
@endphp

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Subject</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">Select subject</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected((int) old('subject_id', $question->subject_id ?? $selectedSubjectId) === $subject->id)>
                            {{ $subject->name }} ({{ $subject->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Question Text</label>
                <textarea name="question_text" class="form-control" rows="4" required>{{ old('question_text', $question->question_text) }}</textarea>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Question Type</label>
                <select name="question_type" class="form-select" required>
                    @foreach ($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('question_type', $question->question_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Marks</label>
                <input type="number" step="0.01" name="marks" class="form-control" value="{{ old('marks', $question->marks ?? 1) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Question Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                @if ($question->image_path)
                    <small class="form-hint d-block mt-2">Current: <a href="{{ asset($question->image_path) }}" target="_blank">View image</a></small>
                @endif
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Explanation</label>
                <textarea name="explanation" class="form-control" rows="3">{{ old('explanation', $question->explanation) }}</textarea>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $question->is_active ?? true))>
                    <span class="form-check-label">Active</span>
                </label>
            </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="card-title">Answer Options</h3>
                <p class="text-secondary mb-0">Add at least two options and mark the correct answers.</p>
            </div>
            <button type="button" class="btn btn-outline-primary" id="add-option">
                <i class="ti ti-plus"></i>
                Add Option
            </button>
        </div>

        <div id="options-container">
            @foreach ($optionData as $index => $option)
                <div class="card mb-2" data-option-row>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong>Option {{ chr(64 + $loop->iteration) }}</strong>
                            <button class="btn btn-sm btn-outline-danger" type="button" data-remove-option>Remove</button>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Label</label>
                                <input type="text" name="options[{{ $index }}][label]" class="form-control" value="{{ old("options.$index.label", $option['label']) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" name="options[{{ $index }}][description]" class="form-control" value="{{ old("options.$index.description", $option['description']) }}">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-check mt-4">
                                    <input type="hidden" name="options[{{ $index }}][is_correct]" value="0">
                                    <input class="form-check-input" type="checkbox" name="options[{{ $index }}][is_correct]" value="1" @checked(old("options.$index.is_correct", $option['is_correct']))>
                                    <span class="form-check-label">Correct</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer text-end">
        <a href="{{ route('admin.question-banks.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Question</button>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('options-container');
            const addButton = document.getElementById('add-option');
            let optionIndex = container.querySelectorAll('[data-option-row]').length;

            const optionLabelFromIndex = (index) => {
                const base = 26;
                let label = '';
                let current = index + 1;

                while (current > 0) {
                    current -= 1;
                    label = String.fromCharCode(65 + (current % base)) + label;
                    current = Math.floor(current / base);
                }

                return label;
            };

            const createOptionRow = (index) => {
                return `
                <div class="card mb-2" data-option-row>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong>Option ${optionLabelFromIndex(index)}</strong>
                            <button class="btn btn-sm btn-outline-danger" type="button" data-remove-option>Remove</button>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Label</label>
                                <input type="text" name="options[${index}][label]" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" name="options[${index}][description]" class="form-control" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-check mt-4">
                                    <input type="hidden" name="options[${index}][is_correct]" value="0">
                                    <input class="form-check-input" type="checkbox" name="options[${index}][is_correct]" value="1">
                                    <span class="form-check-label">Correct</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>`;
            };

            addButton?.addEventListener('click', () => {
                container.insertAdjacentHTML('beforeend', createOptionRow(optionIndex));
                optionIndex += 1;
            });

            container.addEventListener('click', (event) => {
                if (event.target.closest('[data-remove-option]')) {
                    const rows = container.querySelectorAll('[data-option-row]');
                    if (rows.length <= 2) {
                        alert('Questions need at least two options.');
                        return;
                    }
                    event.target.closest('[data-option-row]').remove();
                }
            });
        });
    </script>
@endpush
