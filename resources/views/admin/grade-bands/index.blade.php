@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Settings</div>
                        <h2 class="page-title">Grade Bands</h2>
                        <p class="text-secondary mb-0">Define the percentage thresholds used when auto-grading exam attempts.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="row row-cards">
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Add Grade Band</h3></div>
                            <div class="card-body">
                                <form action="{{ route('admin.grade-bands.store') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Letter</label>
                                        <input type="text" name="letter" value="{{ old('letter') }}" class="form-control" maxlength="2" required>
                                        @error('letter')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Minimum Percentage</label>
                                        <div class="input-group">
                                            <input type="number" name="min_percentage" value="{{ old('min_percentage') }}" class="form-control" min="0" max="100" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                        @error('min_percentage')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Remark</label>
                                        <input type="text" name="remark" value="{{ old('remark') }}" class="form-control" placeholder="Excellent, Good, ...">
                                        @error('remark')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="d-grid">
                                        <button class="btn btn-primary" type="submit">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Existing Bands</h3></div>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>Letter</th>
                                            <th>Min %</th>
                                            <th>Remark</th>
                                            <th class="w-1 text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($bands as $band)
                                            <tr>
                                                <td><span class="badge bg-blue-lt">{{ $band->letter }}</span></td>
                                                <td>{{ $band->min_percentage }}%</td>
                                                <td>{{ $band->remark ?? '—' }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('admin.grade-bands.edit', $band) }}" class="btn btn-sm">Edit</a>
                                                        <form action="{{ route('admin.grade-bands.destroy', $band) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-secondary">No grade bands defined yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
