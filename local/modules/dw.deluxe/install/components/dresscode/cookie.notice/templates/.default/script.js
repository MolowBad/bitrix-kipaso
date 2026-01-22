class DwCookieNotice {
	#element = null;
	#confirmButton = null;
	#options;

	constructor(options = {}) {
		this.#options = {
			selector: ".cookie-notice",
			buttonSelector: ".cookie-notice__confirm-button",
			cookieName: "cookieNotice",
			cookieValue: "1",
			cookieMaxAge: 31536000,
			visibleClass: "cookie-notice--visible",
			...options
		};
	}

	mount() {
		this.#element = document.querySelector(this.#options.selector);
		if (!this.#element) {
			throw new Error(`Cannot find element with selector "${this.#options.selector}"`);
		}

		this.#confirmButton = this.#element.querySelector(this.#options.buttonSelector);
		if (!this.#confirmButton) {
			throw new Error(
				`Cannot find confirm button with selector "${this.#options.buttonSelector}"`
			);
		}

		if (this.#isCookieSet()) {
			return;
		}

		this.#show();
		this.#bindEvents();
	}

	unmount() {
		if (!this.#element) {
			return;
		}

		this.#unbindEvents();

		this.#element.remove();
		this.#element = null;
		this.#confirmButton = null;
	}

	#bindEvents() {
		if (this.#confirmButton) {
			this.#confirmButton.addEventListener("click", this.#handleClick);
		}
	}

	#unbindEvents() {
		if (this.#confirmButton) {
			this.#confirmButton.removeEventListener("click", this.#handleClick);
		}
	}

	#show() {
		if (this.#element) {
			this.#element.classList.add(this.#options.visibleClass);
		}
	}

	#isCookieSet() {
		const { cookieName, cookieValue } = this.#options;
		return document.cookie
			.split(";")
			.some((cookie) => cookie.trim() === `${cookieName}=${cookieValue}`);
	}

	#setCookie() {
		const { cookieName, cookieValue, cookieMaxAge } = this.#options;
		document.cookie = `${cookieName}=${cookieValue}; max-age=${cookieMaxAge}; path=/`;
	}

	#handleClick = (event) => {
		event.preventDefault();

		this.#setCookie();
		this.unmount();
	};
}
