@php
    $model ??= null;
    $entity ??= 'User';
    $submitLabel ??= ($model ? 'Update ' : 'Create ') . $entity;
    $isCandidate = $entity === 'Candidate';
    $statusOptions ??= $isCandidate
        ? \App\Models\User::STATUSES
        : [];
    $defaultStatus ??= $isCandidate
        ? \App\Models\User::STATUS_ACTIVE
        : null;
    $departmentOptions = collect($departmentOptions ?? []);
    $defaultDepartmentIds = collect($defaultDepartmentIds ?? [])->map(fn ($id) => (string) $id)->values()->all();
    $selectedDepartmentIds = collect(
        old('department_ids',
            $selectedDepartmentIds ?? ($model?->departments?->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [])
        )
    )
        ->filter()
        ->map(fn ($id) => (string) $id)
        ->values()
        ->all();

    if (empty($selectedDepartmentIds) && ! empty($defaultDepartmentIds)) {
        $selectedDepartmentIds = $defaultDepartmentIds;
    }
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if (isset($method) && ! in_array($method, ['POST', 'GET']))
        @method($method)
    @endif

    @if ($isCandidate)
        @php
            $photoPath = $model?->photo ?: 'uploads/candidates/student.jpg';
            $photoUrl = '/' . ltrim($photoPath, '/');
            $statusValue = old('status', $model->status ?? $defaultStatus);
            $statusBadge = match ($statusValue) {
                'active' => 'success',
                'inactive' => 'secondary',
                'suspended' => 'warning',
                'banned' => 'danger',
                default => 'secondary',
            };
        @endphp

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="avatar avatar-xl rounded" style="background-image: url('{{ $photoUrl }}')"></span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    {{ $model->name ?? 'New Candidate' }}
                                </div>
                                <div class="text-muted small">
                                    {{ $model->email ?? 'No email yet' }}
                                </div>
                                @if ($model)
                                    <span class="badge bg-{{ $statusBadge }}-lt text-{{ $statusBadge }} mt-2">
                                        {{ ucfirst($statusValue ?? 'active') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            @error('photo')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <div class="form-hint">PNG or JPG up to 2 MB.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Identity</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" value="{{ old('name', $model->name ?? '') }}" class="form-control" required>
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Registration Number</label>
                                <input type="text" name="registration_number" value="{{ old('registration_number', $model->registration_number ?? '') }}" class="form-control" required>
                                @error('registration_number')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    @foreach ($statusOptions as $status)
                                        <option value="{{ $status }}" @selected(old('status', $model->status ?? $defaultStatus) === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Departments</label>
                                <select name="department_ids[]" class="form-select" multiple required size="{{ min(6, max(3, $departmentOptions->count())) }}">
                                    @forelse ($departmentOptions as $department)
                                        <option value="{{ $department->id }}"
                                            @selected(in_array((string) $department->id, $selectedDepartmentIds, true))>
                                            {{ $department->name }}
                                            @if ($department->code)
                                                ({{ $department->code }})
                                            @endif
                                        </option>
                                    @empty
                                        <option value="" disabled>No departments available</option>
                                    @endforelse
                                </select>
                                <small class="text-muted">Choose at least one department (Ctrl/Cmd-click for multiple).</small>
                                @error('department_ids')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                                @error('department_ids.*')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Contact</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" value="{{ old('email', $model->email ?? '') }}" class="form-control" required>
                                @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $model->phone ?? '') }}" class="form-control" required>
                                @error('phone')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" rows="2" class="form-control" required>{{ old('address', $model->address ?? '') }}</textarea>
                                @error('address')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title">Access</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password {{ $model ? '(leave blank to keep current)' : '' }}</label>
                                <input type="password" name="password" class="form-control" {{ $model ? '' : 'required' }}>
                                @error('password')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" {{ $model ? '' : 'required' }}>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
            @if (! $model)
                <a href="{{ route('admin.candidates.upload') }}" class="btn btn-outline-secondary">
                    Upload Candidates
                </a>
            @endif
            <div class="d-flex gap-2 ms-auto">
                <a href="{{ route('admin.candidates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $model->name ?? '') }}" class="form-control" required>
                @error('name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $model->email ?? '') }}" class="form-control" required>
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        </div>
    @endif
</form>
