@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Question Bank</div>
                    <h2 class="page-title">Create Question</h2>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
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

                <form action="{{ route('admin.question-banks.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('admin.question-banks.objective.partials.form')
                </form>
            </div>
        </div>
    </div>
@endsection
