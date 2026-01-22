class DwProductQuickBuyForm {
	#options;
	#element = null;
	#closeButtons = [];
	#form = null;
	#states = {};
	#submitButton = null;
	#errorsContainer = null;
	#orderNumberContainer = null;

	constructor(options = {}) {
		this.#options = {
			selector: ".product-quick-buy-form",
			closeButtonSelector: ".product-quick-buy-form__close-button",
			contentSelector: ".product-quick-buy-form__inner-proxy",
			formSelector: ".product-quick-buy-form__form",
			stateSelector: ".product-quick-buy-form__state",
			inputStateSelector: ".product-quick-buy-form__state--input",
			successStateSelector: ".product-quick-buy-form__state--success",
			submitButtonSelector: ".product-quick-buy-form__submit-button",
			errorsContainerSelector: ".product-quick-buy-form__errors",
			orderNumberSelector: ".product-quick-buy-form__success-order-number",
			fieldErrorClass: "product-quick-buy-form__field--error",
			fieldLabelErrorClass: "product-quick-buy-form__field-label--error",
			activeStateClass: "product-quick-buy-form__state--active",
			visibleErrorClass: "product-quick-buy-form__errors--visible",
			submitButtonLoadingClass: "product-quick-buy-form__submit-button--loading",
			...options
		};
	}

	mount() {
		this.#element = document.querySelector(this.#options.selector);
		if (!this.#element) {
			throw new Error(`Cannot find element with selector "${this.#options.selector}"`);
		}

		this.#closeButtons = this.#element.querySelectorAll(this.#options.closeButtonSelector);
		if (this.#closeButtons.length === 0) {
			console.warn(
				`No close buttons found with selector "${this.#options.closeButtonSelector}"`
			);
		}

		this.#form = this.#element.querySelector(this.#options.formSelector);
		if (!this.#form) {
			throw new Error(`Cannot find form with selector "${this.#options.formSelector}"`);
		}

		this.#states = {
			input: this.#element.querySelector(this.#options.inputStateSelector),
			success: this.#element.querySelector(this.#options.successStateSelector)
		};

		if (!this.#states.input || !this.#states.success) {
			throw new Error("Cannot find required state elements");
		}

		this.#submitButton = this.#form.querySelector(this.#options.submitButtonSelector);
		if (!this.#submitButton) {
			throw new Error(
				`Cannot find submit button with selector "${this.#options.submitButtonSelector}"`
			);
		}

		this.#errorsContainer = this.#element.querySelector(this.#options.errorsContainerSelector);
		if (!this.#errorsContainer) {
			throw new Error(
				`Cannot find errors container with selector "${this.#options.errorsContainerSelector}"`
			);
		}

		this.#orderNumberContainer = this.#element.querySelector(this.#options.orderNumberSelector);

		this.#bindEvents();
	}

	unmount() {
		if (!this.#element) {
			return;
		}

		this.#unbindEvents();

		if (this.#element.parentNode) {
			this.#element.parentNode.removeChild(this.#element);
		}

		this.#element = null;
		this.#closeButtons = [];
		this.#form = null;
		this.#states = {};
		this.#submitButton = null;
		this.#errorsContainer = null;
		this.#orderNumberContainer = null;
	}

	#bindEvents() {
		this.#closeButtons.forEach((button) => {
			button.addEventListener("click", this.#handleClose);
		});

		document.addEventListener("keydown", this.#handleEscapeKey);
		this.#element.addEventListener("click", this.#handleOutsideClick);

		if (this.#form) {
			this.#form.onsubmit = this.#handleSubmit;
		}
	}

	#unbindEvents() {
		this.#closeButtons.forEach((button) => {
			button.removeEventListener("click", this.#handleClose);
		});

		document.removeEventListener("keydown", this.#handleEscapeKey);
		this.#element.removeEventListener("click", this.#handleOutsideClick);

		if (this.#form) {
			this.#form.onsubmit = null;
		}
	}

	#handleClose = (event) => {
		event.preventDefault();

		this.unmount();
	};

	#handleEscapeKey = (event) => {
		if (event.key === "Escape") {
			this.#handleClose(event);
		}
	};

	#handleOutsideClick = (event) => {
		const content = this.#element.querySelector(this.#options.contentSelector);
		if (!content) {
			throw new Error(
				`Cannot find content element with selector "${this.#options.contentSelector}"`
			);
		}

		if (!content.contains(event.target)) {
			this.#handleClose(event);
		}
	};

	#handleSubmit = async (event) => {
		event.preventDefault();

		this.#clearErrors();

		if (!this.#validateForm()) {
			return false;
		}

		this.#showLoading();

		try {
			const formData = new FormData(event.target);

			const url = new URL(window.ajaxPath, window.location.origin);
			url.searchParams.set("act", "productQuickBuy");

			const response = await fetch(url, { method: "POST", body: formData });

			if (!response.ok && response.status !== 422) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}

			const data = await response.json();

			if (data.success) {
				this.#showSuccess(data.orderNumber || data.orderId);
				return false;
			}

			if (data.errors) {
				const errorMessages = Array.isArray(data.errors) ? data.errors : [data.errors];
				this.#showErrors(errorMessages);
			}
		} catch (error) {
			console.error("Cannot complete quick order:", error);
		} finally {
			this.#hideLoading();
		}

		return false;
	};

	#validateForm() {
		let isValid = true;

		const requiredFields = this.#form.querySelectorAll("[data-required='Y']");
		requiredFields.forEach((field) => {
			if (!this.#validateField(field)) {
				this.#addFieldError(field);
				isValid = false;
			}
		});

		return isValid;
	}

	#validateField(field) {
		if (field.type === "checkbox") {
			return field.checked;
		}

		return field.value.trim() !== "";
	}

	#addFieldError(field) {
		if (field.type === "checkbox") {
			this.#addCheckboxLabelError(field);
			return;
		}

		field.classList.add(this.#options.fieldErrorClass);
	}

	#addCheckboxLabelError(field) {
		if (field.parentElement && field.parentElement.tagName === "LABEL") {
			field.parentElement.classList.add(this.#options.fieldLabelErrorClass);
			return;
		}

		const closestLabel = field.closest("label");
		if (closestLabel) {
			closestLabel.classList.add(this.#options.fieldLabelErrorClass);
			return;
		}

		if (field.id) {
			const label = this.#form.querySelector(`label[for="${field.id}"]`);
			if (label) {
				label.classList.add(this.#options.fieldLabelErrorClass);
			}
		}
	}

	#clearErrors() {
		const errorClasses = [this.#options.fieldErrorClass, this.#options.fieldLabelErrorClass];

		errorClasses.forEach((errorClass) => {
			const elements = this.#form.querySelectorAll(`.${errorClass}`);
			elements.forEach((element) => {
				element.classList.remove(errorClass);
			});
		});

		if (this.#errorsContainer) {
			this.#errorsContainer.innerHTML = "";
			this.#errorsContainer.classList.remove(this.#options.visibleErrorClass);
		}
	}

	#showLoading() {
		if (!this.#submitButton) {
			return;
		}

		this.#submitButton.disabled = true;
		this.#submitButton.classList.add(this.#options.submitButtonLoadingClass);
	}

	#hideLoading() {
		if (!this.#submitButton) {
			return;
		}

		this.#submitButton.disabled = false;
		this.#submitButton.classList.remove(this.#options.submitButtonLoadingClass);
	}

	#showSuccess(orderNumber) {
		Object.values(this.#states).forEach((state) => {
			state.classList.remove(this.#options.activeStateClass);
		});

		if (this.#states.success) {
			if (this.#orderNumberContainer && orderNumber) {
				this.#orderNumberContainer.textContent = orderNumber;
			}

			this.#states.success.classList.add(this.#options.activeStateClass);
		}
	}

	#showErrors(errors) {
		if (!this.#errorsContainer) {
			return;
		}

		this.#errorsContainer.innerHTML = "";

		errors.forEach((error) => {
			const errorElement = document.createElement("div");
			errorElement.className = "product-quick-buy-form__error";
			errorElement.textContent = error;

			this.#errorsContainer.appendChild(errorElement);
		});

		this.#errorsContainer.classList.add(this.#options.visibleErrorClass);
	}
}
