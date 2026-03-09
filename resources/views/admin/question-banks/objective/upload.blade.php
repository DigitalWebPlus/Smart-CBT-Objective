@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="page-pretitle">Question Bank</div>
                    <h2 class="page-title">Upload Questions</h2>
                </div>
                <div class="btn-list">
                    <a href="{{ route('admin.question-banks.index') }}" class="btn btn-secondary">Back to Questions</a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @if ($errors->has('file'))
                    <div class="alert alert-danger">{{ $errors->first('file') }}</div>
                @endif

                @if (session('objective_import_report'))
                    @php
                        $report = session('objective_import_report');
                    @endphp
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <strong>Bulk import summary</strong>
                            </div>
                            <div class="d-flex gap-3 flex-wrap small">
                                <span>{{ $report['created'] ?? 0 }} created</span>
                                <span>{{ $report['updated'] ?? 0 }} updated</span>
                                <span>{{ $report['skipped'] ?? 0 }} skipped</span>
                                <span>{{ $report['total'] ?? 0 }} total rows</span>
                            </div>
                        </div>
                        @if (!empty($report['errors']))
                            <hr class="my-2">
                            <div class="small text-danger">
                                <div class="fw-semibold mb-1">Issues detected (showing up to 5):</div>
                                <ul class="mb-0 ps-3">
                                    @foreach (array_slice($report['errors'], 0, 5) as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                    @if (count($report['errors']) > 5)
                                        <li>+ {{ count($report['errors']) - 5 }} more error(s). Please review your file.</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.question-banks.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="" disabled {{ empty($selectedSubjectId) ? 'selected' : '' }}>Select a subject</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}" @selected((int) $selectedSubjectId === (int) $subject->id)>
                                            {{ $subject->name }} ({{ $subject->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Questions file (JSON / CSV / XLSX) <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control" accept=".json,.txt,.csv,.xlsx" required>
                                <small class="form-hint">
                                    Upload a UTF-8 JSON array or a spreadsheet whose columns match the schema below.
                                    For CSV/XLSX, store the <code>options</code> column as a JSON array string.
                                </small>
                            </div>
                            <div class="mb-3">
                                <div class="fw-semibold mb-2">Download sample files (3 questions)</div>
                                <div class="btn-list">
                                    <a class="btn btn-success" href="{{ asset('sample/objective-questions-sample.json') }}" download>
                                        <i class="ti ti-download me-1"></i>
                                        Sample JSON
                                    </a>
                                    <a class="btn btn-success" href="{{ asset('sample/objective-questions-sample.csv') }}" download>
                                        <i class="ti ti-download me-1"></i>
                                        Sample CSV
                                    </a>
                                    <a class="btn btn-success" href="{{ asset('sample/objective-questions-sample.xlsx') }}" download>
                                        <i class="ti ti-download me-1"></i>
                                        Sample XLSX
                                    </a>
                                </div>
                            </div>
                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="overwrite" value="1">
                                <span class="form-check-label">Overwrite questions that have the same question text</span>
                            </label>
                            <div class="border rounded p-3 bg-light mb-3">
                                <div class="fw-semibold mb-2">Expected schema</div>
                                <pre class="small text-break mb-0">[
    {
        "question_text": "What is gravity?",
        "question_type": "msa|mma|tof",
        "marks": 2,
        "is_active": true,
        "explanation": "Optional explanation",
        "metadata": {"difficulty": "easy"},
        "options": [
            {"label": "A", "description": "Answer", "is_correct": true},
            {"label": "B", "description": "Another", "is_correct": false}
        ]
    }
]

CSV/XLSX headers: <code>question_text,question_type,marks,is_active,explanation,metadata,options</code></pre>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Import Questions</button>
                                <a href="{{ route('admin.question-banks.index') }}" class="btn btn-link">Cancel</a>
                            </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 @endsection
