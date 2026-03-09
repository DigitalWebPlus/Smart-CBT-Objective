@php
    $candidate = $candidate ?? auth()->user();
    $photoPath = $candidate?->photo ?: 'uploads/candidates/default.jpg';
    $photoUrl = '/' . ltrim($photoPath, '/');
    $departments = $candidate?->departments ?? collect();
@endphp

<div class="card border-0 rounded-4 glass-card h-100 bg-primary text-white">
    <div class="card-body p-4 text-center fs-5">
        <img src="{{ $photoUrl }}" alt="{{ $candidate?->name ?? 'Candidate' }}" class="rounded-circle mb-3" style="width: 128px; height: 128px; object-fit: cover;">
        <h2 class="h5 fw-semibold mb-1">{{ $candidate?->name ?? 'Candidate' }}</h2>
        <span class="badge rounded-pill text-bg-light text-primary mb-3">
            {{ $candidate?->email ?? 'N/A' }}
        </span>
        <div class="d-flex flex-column gap-2 text-start">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-white small">Registration No.</span>
                <span class="fw-semibold">{{ $candidate?->registration_number ?? 'N/A' }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-white small">Status</span>
                <span class="badge text-bg-{{ $candidate?->status === 'active' ? 'success' : ($candidate?->status === 'inactive' ? 'secondary' : ($candidate?->status === 'suspended' ? 'warning' : 'danger')) }}">
                    {{ ucfirst($candidate?->status ?? 'active') }}
                </span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-white small">Phone</span>
                <span class="fw-semibold">{{ $candidate?->phone ?? 'N/A' }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-white small">Address</span>
                <span class="fw-semibold text-end">{{ $candidate?->address ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="border-top pt-3 mt-3 text-start">
            <p class="text-white small mb-1">Department</p>
            <div class="d-flex flex-wrap gap-1">
                @forelse ($departments as $department)
                    <span class="badge rounded-pill text-bg-light text-uppercase">
                        {{ $department->code ?? $department->name }}
                    </span>
                @empty
                    <span class="text-white small">Not assigned</span>
                @endforelse
            </div>
        </div>
    </div>
</div>
