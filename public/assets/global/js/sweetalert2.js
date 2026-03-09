(() => {
	const explicitFormSelector = 'form[data-swal-confirm], form[data-swal-title]';

	const isDeleteForm = (form) => {
		const methodInput = form.querySelector("input[name='_method']");

		if (!methodInput) {
			return false;
		}

		return String(methodInput.value || '').toUpperCase() === 'DELETE';
	};

	const isConfirmableForm = (form) => {
		if (!(form instanceof HTMLFormElement)) {
			return false;
		}

		if (form.matches(explicitFormSelector)) {
			return true;
		}

		return isDeleteForm(form);
	};

	const init = () => {
		if (typeof Swal === 'undefined') {
			console.warn('SweetAlert2 is required for data-swal-confirm forms.');
			return;
		}

		document.querySelectorAll('form').forEach((form) => {
			if (isConfirmableForm(form)) {
				bindConfirmation(form);
			}
		});

		const observer = new MutationObserver((mutations) => {
			mutations.forEach((mutation) => {
				mutation.addedNodes.forEach((node) => {
					if (!(node instanceof HTMLElement)) {
						return;
					}

					if (node.matches?.('form') && isConfirmableForm(node)) {
						bindConfirmation(node);
					}

					node.querySelectorAll?.('form').forEach((form) => {
						if (isConfirmableForm(form)) {
							bindConfirmation(form);
						}
					});
				});
			});
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true,
		});
	};

	const bindConfirmation = (form) => {
		if (form.__swalBound) {
			return;
		}

		form.addEventListener('submit', (event) => {
			if (form.dataset.swalSubmitting === 'true') {
				return;
			}

			event.preventDefault();

			const isDelete = isDeleteForm(form);

			const {
				swalTitle,
				swalConfirm,
				swalIcon,
				swalConfirmButton,
				swalCancelButton,
			} = form.dataset;

			Swal.fire({
				title: swalTitle || (isDelete ? 'Delete item?' : 'Are you sure?'),
				text: swalConfirm || 'This action cannot be undone.',
				icon: swalIcon || 'warning',
				showCancelButton: true,
				confirmButtonText: swalConfirmButton || (isDelete ? 'Yes, delete' : 'Yes, proceed'),
				cancelButtonText: swalCancelButton || 'Cancel',
				focusCancel: true,
				customClass: {
					confirmButton: 'btn btn-danger',
					cancelButton: 'btn btn-secondary ms-2',
				},
				buttonsStyling: false,
			}).then((result) => {
				if (result.isConfirmed) {
					form.dataset.swalSubmitting = 'true';
					form.submit();
				}
			});
		});

		form.__swalBound = true;
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
