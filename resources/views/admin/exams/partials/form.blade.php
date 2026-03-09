<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $exam->title) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach (\App\Models\Exam::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('status', $exam->status ?? \App\Models\Exam::STATUS_DRAFT) === $status)>
                            {{ \Illuminate\Support\Str::headline($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Starts At</label>
                <input type="datetime-local" name="starts_at" class="form-control"
                    value="{{ old('starts_at', optional($exam->starts_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Ends At</label>
                <input type="datetime-local" name="ends_at" class="form-control"
                    value="{{ old('ends_at', optional($exam->ends_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Duration (minutes)</label>
                <input type="number" name="duration_minutes" class="form-control"
                    value="{{ old('duration_minutes', $exam->duration_minutes) }}" min="1">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $exam->description) }}</textarea>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Departments</label>
                <select name="department_ids[]" class="form-select" multiple required size="{{ min(6, max(3, count($departments ?? []))) }}">
                    @forelse ($departments as $department)
                        <option value="{{ $department->id }}"
                            @selected(in_array($department->id, old('department_ids', $selectedDepartments ?? []), true))>
                            {{ $department->name }}@if ($department->code) ({{ $department->code }})@endif
                        </option>
                    @empty
                        <option value="" disabled>No departments available</option>
                    @endforelse
                </select>
                <small class="text-muted">Choose at least one department (Ctrl/Cmd-click for multiple).</small>
                @error('department_ids')
                    <small class="text-danger d-block">{{ $message }}</small>
                @enderror
                @error('department_ids.*')
                    <small class="text-danger d-block">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <hr>
        <div class="mb-2 fw-semibold">Select Questions</div>
        <div class="text-secondary mb-3">Choose the questions that make up this exam.</div>

        <div class="row">
            @forelse ($questions as $question)
                <div class="col-md-6 mb-2">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="question_ids[]" value="{{ $question->id }}"
                            @checked(in_array($question->id, old('question_ids', $selectedQuestions), true))>
                        <span class="form-check-label">
                            <span class="fw-semibold">{{ $question->subject?->code }}</span> —
                            {{ \Illuminate\Support\Str::limit($question->question_text, 80) }}
                            <span class="text-secondary">({{ $question->marks }} marks)</span>
                        </span>
                    </label>
                </div>
            @empty
                <div class="col-12 text-secondary">No questions available. Create questions first.</div>
            @endforelse
        </div>
    </div>
    <div class="card-footer text-end">
        <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Exam</button>
    </div>
</div>
