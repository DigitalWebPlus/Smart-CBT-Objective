<div class="col-12">
    <label class="form-label">{{ $field['label'] ?? '' }}</label>

    <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2">
        @foreach (($field['options'] ?? []) as $option)
            <label class="form-selectgroup-item flex-fill">
                <input type="radio" name="{{ $fieldKey }}" value="{{ $option['value'] }}" class="form-selectgroup-input"
                    {{ $currentValue === $option['value'] ? 'checked' : '' }}>
                <div class="form-selectgroup-label d-flex align-items-center p-3">
                    <div class="me-3">
                        <span class="form-selectgroup-check"></span>
                    </div>
                    <div>
                        <span class="form-selectgroup-title strong mb-1">{{ $option['title'] }}</span>
                        <div class="text-secondary">{{ $option['description'] }}</div>
                    </div>
                </div>
            </label>
        @endforeach
    </div>

    @error($fieldKey)
        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
    @enderror
</div>
