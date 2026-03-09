@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Settings</div>
                        <h2 class="page-title">Edit Grade Band</h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="{{ route('admin.grade-bands.index') }}" class="btn btn-outline">Back</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('admin.grade-bands.update', $band) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="mb-3">
                                        <label class="form-label">Letter</label>
                                        <input type="text" name="letter" value="{{ old('letter', $band->letter) }}" class="form-control" maxlength="2" required>
                                        @error('letter')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Minimum Percentage</label>
                                        <div class="input-group">
                                            <input type="number" name="min_percentage" value="{{ old('min_percentage', $band->min_percentage) }}" class="form-control" min="0" max="100" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                        @error('min_percentage')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Remark</label>
                                        <input type="text" name="remark" value="{{ old('remark', $band->remark) }}" class="form-control">
                                        @error('remark')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="d-grid">
                                        <button class="btn btn-primary" type="submit">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
