@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="page-title">Exam Settings</h2>
                    <p class="text-secondary mb-0">Configure how candidates log in to access exams.</p>
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

                <form method="POST" action="{{ route('admin.exam-settings.update') }}" class="card">
                    @csrf
                    @method('PUT')

                    <div class="card-body d-flex flex-column gap-4">
                        @foreach ($sections as $section)
                            <div class="card">
                                <div class="card-header">
                                    <div>
                                        <h3 class="card-title mb-1">{{ $section['title'] ?? 'Section' }}</h3>
                                        @if (! empty($section['description']))
                                            <div class="text-secondary">{{ $section['description'] }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="card-body row g-3">
                                    @foreach (($section['fields'] ?? []) as $fieldKey)
                                        @php
                                            $field = $fields[$fieldKey] ?? null;
                                        @endphp

                                        @if (is_array($field))
                                            @php
                                                $currentValue = old($fieldKey, $settings[$fieldKey] ?? $field['default'] ?? '');
                                            @endphp

                                            @if (($field['type'] ?? null) === 'radio')
                                                @include('admin.exam-settings.partials.radio-field', [
                                                    'fieldKey' => $fieldKey,
                                                    'field' => $field,
                                                    'currentValue' => $currentValue,
                                                ])
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Save Exam Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
