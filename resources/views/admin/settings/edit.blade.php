@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="page-title">Settings</h2>
                    <p class="text-secondary mb-0">Manage core site details used across the platform.</p>
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

                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="card">
                    @csrf
                    @method('PUT')

                    <div class="card-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-control"
                                value="{{ old('site_name', $settings['site_name'] ?? '') }}"
                                placeholder="e.g. CBT Objective Platform">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Site Email</label>
                            <input type="email" name="site_email" class="form-control"
                                value="{{ old('site_email', $settings['site_email'] ?? '') }}"
                                placeholder="e.g. info@example.com">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Site Phone</label>
                            <input type="text" name="site_phone" class="form-control"
                                value="{{ old('site_phone', $settings['site_phone'] ?? '') }}"
                                placeholder="e.g. +234 800 000 0000">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Site Logo</label>
                            <input type="file" name="site_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg">
                            @if (! empty($settings['site_logo']))
                                <div class="mt-2">
                                    <img src="{{ asset($settings['site_logo']) }}" alt="Site logo" class="img-thumbnail"
                                        style="max-height: 80px;">
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <label class="form-label">Site Address</label>
                            <textarea name="site_address" class="form-control" rows="3" placeholder="Site address">{{ old('site_address', $settings['site_address'] ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
