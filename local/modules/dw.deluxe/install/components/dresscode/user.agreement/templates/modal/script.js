class DwUserAgreement {
	#options;
	#element = null;
	#closeButtons = [];

	constructor(options = {}) {
		this.#options = {
			selector: ".user-agreement",
			closeButtonSelector: ".user-agreement__close-button",
			contentSelector: ".user-agreement__inner-proxy",
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
	}

	#bindEvents() {
		this.#closeButtons.forEach((button) => {
			button.addEventListener("click", this.#handleClose);
		});

		document.addEventListener("keydown", this.#handleEscapeKey);

		this.#element.addEventListener("click", this.#handleOutsideClick);
	}

	#unbindEvents() {
		this.#closeButtons.forEach((button) => {
			button.removeEventListener("click", this.#handleClose);
		});

		document.removeEventListener("keydown", this.#handleEscapeKey);

		this.#element.removeEventListener("click", this.#handleOutsideClick);
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
}
