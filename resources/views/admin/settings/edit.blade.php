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
                @php
                    $siteSettingFields = ['site_name', 'site_email', 'site_phone', 'site_address', 'site_logo'];
                    $profileFields = ['name', 'email', 'photo', 'password', 'password_confirmation'];

                    $activeTab = 'site';

                    foreach ($profileFields as $field) {
                        if (old($field) !== null) {
                            $activeTab = 'profile';
                            break;
                        }
                    }

                    if ($activeTab === 'site') {
                        foreach ($siteSettingFields as $field) {
                            if (old($field) !== null) {
                                $activeTab = 'site';
                                break;
                            }
                        }
                    }
                @endphp

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

                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#tab-site-settings" class="nav-link {{ $activeTab === 'site' ? 'active' : '' }}"
                                    data-bs-toggle="tab" aria-selected="{{ $activeTab === 'site' ? 'true' : 'false' }}"
                                    role="tab">Site Settings</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-admin-profile" class="nav-link {{ $activeTab === 'profile' ? 'active' : '' }}"
                                    data-bs-toggle="tab" aria-selected="{{ $activeTab === 'profile' ? 'true' : 'false' }}"
                                    role="tab">Admin Profile</a>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane {{ $activeTab === 'site' ? 'active show' : '' }}" id="tab-site-settings"
                            role="tabpanel">
                            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
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
                                        <input type="file" name="site_logo" class="form-control"
                                            accept=".jpg,.jpeg,.png,.webp,.svg">
                                        @if (! empty($settings['site_logo']))
                                            <div class="mt-2">
                                                <img src="{{ asset($settings['site_logo']) }}" alt="Site logo"
                                                    class="img-thumbnail" style="max-height: 80px;">
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

                        <div class="tab-pane {{ $activeTab === 'profile' ? 'active show' : '' }}" id="tab-admin-profile"
                            role="tabpanel">
                            <form method="POST" action="{{ route('admin.settings.profile.update') }}"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="card-body row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ old('name', $admin?->name ?? '') }}"
                                            placeholder="e.g. System Administrator" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email', $admin?->email ?? '') }}"
                                            placeholder="e.g. admin@example.com" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Profile Photo</label>
                                        <input type="file" name="photo" class="form-control"
                                            accept=".jpg,.jpeg,.png,.webp">
                                        @if (! empty($admin?->photo))
                                            <div class="mt-2">
                                                <img src="{{ asset($admin->photo) }}" alt="Admin photo"
                                                    class="img-thumbnail" style="max-height: 80px;">
                                            </div>
                                        @endif
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="password" class="form-control"
                                            placeholder="Leave blank to keep current password">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="password_confirmation" class="form-control"
                                            placeholder="Re-enter new password">
                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Save Profile</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
