@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Candidates</div>
                    <h2 class="page-title">Upload Candidates</h2>
                </div>
                <div class="btn-list">
                    <a href="{{ route('admin.candidates.create') }}" class="btn btn-outline-primary">
                        <i class="ti ti-plus"></i>
                        Add Candidate
                    </a>
                    <a href="{{ route('admin.candidates.index') }}" class="btn btn-secondary">Back to list</a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @if ($errors->has('file'))
                    <div class="alert alert-danger">{{ $errors->first('file') }}</div>
                @endif

                @if (session('candidate_import_report'))
                    @php
                        $report = session('candidate_import_report');
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
                        <form action="{{ route('admin.candidates.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Candidates file (JSON / CSV / XLSX) <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control" accept=".json,.txt,.csv,.xlsx" required>
                                <small class="form-hint">Upload a UTF-8 JSON array or a spreadsheet whose columns match the schema below. For CSV/XLSX, you can use a comma-separated list or JSON array in the department columns.</small>
                            </div>
                            <div class="mb-3">
                                <div class="fw-semibold mb-2">Download sample files (5 candidates each)</div>
                                <div class="btn-list">
                                    <a class="btn btn-primary" href="{{ asset('sample/candidates-sample.json') }}" download>
                                        <i class="ti ti-download me-1"></i>
                                        Sample JSON
                                    </a>
                                    <a class="btn btn-primary" href="{{ asset('sample/candidates-sample.csv') }}" download>
                                        <i class="ti ti-download me-1"></i>
                                        Sample CSV
                                    </a>
                                    <a class="btn btn-primary" href="{{ asset('sample/candidates-sample.xlsx') }}" download>
                                        <i class="ti ti-download me-1"></i>
                                        Sample XLSX
                                    </a>
                                </div>
                            </div>
                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="overwrite" value="1">
                                <span class="form-check-label">Overwrite candidates that match by email or registration number</span>
                            </label>
                            <div class="border rounded p-3 bg-light mb-3">
                                <div class="fw-semibold mb-2">Expected schema</div>
                                <pre class="small text-break mb-0">[
    {
        "name": "Jane Doe",
        "email": "jane@example.com",
        "registration_number": "REG-001",
        "phone": "08012345678",
        "address": "12 Example Road",
        "status": "active|inactive|suspended|banned",
        "department_codes": ["SCI", "ENG"],
        "photo": "uploads/candidates/student.jpg",
        "password": "optional-password"
    }
]

    CSV/XLSX headers: <code>name,email,registration_number,phone,address,status,department_codes,department_names,department_ids,photo,password</code></pre>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Import Candidates</button>
                                <a href="{{ route('admin.candidates.index') }}" class="btn btn-link">Cancel</a>
                            </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 @endsection
