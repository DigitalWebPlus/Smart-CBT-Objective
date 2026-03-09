@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Candidates</div>
                    <h2 class="page-title">Create Candidate</h2>
                </div>
                <div class="btn-list">
                    <a href="{{ route('admin.candidates.upload') }}" class="btn btn-outline-primary">
                        <i class="ti ti-upload"></i>
                        Upload Candidates
                    </a>
                    <a href="{{ route('admin.candidates.index') }}" class="btn btn-secondary">Back to list</a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="card-body">
                        @include('admin.partials.user-form', [
                            'action' => route('admin.candidates.store'),
                            'entity' => 'Candidate',
                            'candidateStatuses' => $statuses,
                            'departmentOptions' => $departments,
                            'defaultDepartmentIds' => $defaultDepartmentIds,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
