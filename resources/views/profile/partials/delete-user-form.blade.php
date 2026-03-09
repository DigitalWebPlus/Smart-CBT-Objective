<section>
    <header class="mb-3">
        <h2 class="h5 fw-semibold mb-1">
            {{ __('Delete Account') }}
        </h2>

        <p class="text-muted mb-0">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
        {{ __('Delete Account') }}
    </button>

    <div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-labelledby="confirmUserDeletionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmUserDeletionLabel">
                            {{ __('Confirm account deletion') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </p>
                        <div>
                            <label for="delete_password" class="form-label fw-semibold">{{ __('Password') }}</label>
                            <input id="delete_password" name="password" type="password" class="form-control" placeholder="{{ __('Password') }}" />
                            @if ($errors->userDeletion->has('password'))
                                <small class="text-danger">{{ $errors->userDeletion->first('password') }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            {{ __('Delete Account') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->userDeletion->isNotEmpty())
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const modalEl = document.getElementById('confirmUserDeletion');
                    if (modalEl && window.bootstrap?.Modal) {
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                });
            </script>
        @endpush
    @endif
</section>
