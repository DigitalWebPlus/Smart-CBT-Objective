<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $subject->name) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Subject Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $subject->code) }}" required>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $subject->description) }}</textarea>
            </div>
            <div class="col-12 mb-3">
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                        @checked(old('is_active', $subject->is_active ?? true))>
                    <span class="form-check-label">Active</span>
                </label>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Subject</button>
    </div>
</div>
