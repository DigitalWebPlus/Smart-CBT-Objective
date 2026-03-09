@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Duplicate Question</div>
                    <h2 class="page-title">Objectives Duplicate</h2>
                    <p class="text-secondary mb-0"><code>Duplicates are detected by matching question text within the same subject (case-insensitive). Each group lets you “Keep first, delete others” or delete selected duplicates.</code></p>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @if ($duplicateGroups->isEmpty())
                    <div class="alert alert-success">No duplicate questions found.</div>
                @else
                    @foreach ($duplicateGroups as $group)
                        <div class="card mb-3">
                            <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $group['subject']?->name ?? 'Unknown Subject' }}</div>
                                    <div class="text-secondary small">Duplicates: {{ $group['total'] }}</div>
                                </div>
                                <form method="POST" action="{{ route('admin.duplicate-questions.objective.delete') }}" class="d-flex">
                                    @csrf
                                    @foreach ($group['items']->slice(1) as $item)
                                        <input type="hidden" name="ids[]" value="{{ $item->id }}">
                                    @endforeach
                                    <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($group['items']->count() < 2)>
                                        Keep first, delete others
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <span class="text-secondary">Question text:</span>
                                    <div class="fw-semibold">{{ $group['question_text'] }}</div>
                                </div>
                                <form method="POST" action="{{ route('admin.duplicate-questions.objective.delete') }}">
                                    @csrf
                                    <div class="table-responsive">
                                        <table class="table table-vcenter">
                                            <thead>
                                                <tr>
                                                    <th class="w-1"></th>
                                                    <th class="w-1">ID</th>
                                                    <th>Question Text</th>
                                                    <th>Marks</th>
                                                    <th>Updated</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($group['items'] as $index => $item)
                                                    <tr>
                                                        <td>
                                                            <input class="form-check-input" type="checkbox" name="ids[]" value="{{ $item->id }}" @checked($index > 0)>
                                                        </td>
                                                        <td class="text-secondary">{{ $item->id }}</td>
                                                        <td>{{ $item->question_text }}</td>
                                                        <td>{{ number_format($item->marks, 2) }}</td>
                                                        <td>{{ optional($item->updated_at)->format('M d, Y') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-sm">Delete selected</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
