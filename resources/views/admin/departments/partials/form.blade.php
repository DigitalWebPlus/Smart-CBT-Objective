<div class="card">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label" for="department-name">Department name</label>
                <input type="text" class="form-control" id="department-name" name="name"
                    value="{{ old('name', $department->name) }}" required>
                @error('name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="department-code">Department Code</label>
                <input type="text" class="form-control" id="department-code" name="code"
                    value="{{ old('code', $department->code) }}" placeholder="e.g. SCI">
                <small class="text-muted">Short identifier shown across the app.</small>
                @error('code')
                    <small class="text-danger d-block">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="department-description">Description</label>
                <textarea name="description" id="department-description" rows="3" class="form-control"
                    placeholder="Optional context for admins">{{ old('description', $department->description) }}</textarea>
                @error('description')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-label">Default department</label>
                <div class="form-check form-switch">
                    <input type="hidden" name="is_default" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="department-is-default"
                        name="is_default" value="1"
                        @checked(old('is_default', (int) $department->is_default))>
                    <label class="form-check-label" for="department-is-default">
                        Set as fallback department for new candidates.
                    </label>
                </div>
                <small class="text-muted">Exactly one department must remain default.</small>
                @error('is_default')
                    <small class="text-danger d-block">{{ $message }}</small>
                @enderror
            </div>
        </div>
    </div>
</div>
<div class="mt-4 d-flex justify-content-between">
    <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">
        Cancel
    </a>
    <button type="submit" class="btn btn-primary">
        Save changes
    </button>
</div>
