class DwComponentLoader {
	#spinner;
	#spinnerActiveClass;

	constructor(parameters) {
		if (!parameters) {
			throw new Error("Cannot create loader without parameters");
		}

		if (!parameters.spinnerSelector) {
			throw new Error("Cannot create loader without spinnerSelector in parameters");
		}

		if (!parameters.spinnerActiveClass) {
			throw new Error("Cannot create loader without spinnerActiveClass in parameters");
		}

		this.#spinner = document.querySelector(parameters.spinnerSelector);

		if (!this.#spinner) {
			throw new Error(`Cannot find spinner element: ${parameters.spinnerSelector}`);
		}

		this.#spinnerActiveClass = parameters.spinnerActiveClass;
	}

	async load(request) {
		if (!(request instanceof Request)) {
			throw new Error("Cannot load component without Request object");
		}

		this.#showSpinner();

		try {
			const response = await this.#sendRequest(request);
			const data = await this.#parseResponse(response);
			await this.#loadAssets(data);
			this.#insert(data);
		} finally {
			this.#hideSpinner();
		}
	}

	async #sendRequest(request) {
		const response = await fetch(request);
		if (!response.ok) {
			throw {
				message: `Cannot fetch component: HTTP ${response.status}`,
				reasons: [`HTTP error: ${response.status}`],
				status: response.status
			};
		}

		return response;
	}

	async #parseResponse(response) {
		const result = await response.json();
		if (result.errors && result.errors.length > 0) {
			throw {
				message: "Cannot load component: API returned errors",
				reasons: result.errors,
				response: result
			};
		}

		if (result.status !== "success" || !result.data) {
			throw {
				message: "Cannot parse response: invalid format",
				reasons: ["Invalid response format"],
				response: result
			};
		}

		if (!result.data.html) {
			throw {
				message: "Cannot render component: no HTML content",
				reasons: ["Missing HTML content in response"],
				response: result
			};
		}

		return result.data;
	}

	async #loadAssets(data) {
		if (!data.assets) {
			return;
		}

		const jsPaths = Array.isArray(data?.assets?.js) ? data.assets.js : [];
		const cssPaths = Array.isArray(data?.assets?.css) ? data.assets.css : [];

		await Promise.all([this.#loadScripts(...jsPaths), this.#loadStyles(...cssPaths)]);
	}

	async #loadScripts(...paths) {
		const promises = paths.map((path) => this.#loadScript(path));
		return Promise.all(promises);
	}

	async #loadStyles(...paths) {
		const promises = paths.map((path) => this.#loadStyle(path));
		return Promise.all(promises);
	}

	#loadScript(path) {
		if (this.#hasScript(path)) {
			return Promise.resolve();
		}

		return new Promise((resolve, reject) => {
			const script = document.createElement("script");
			script.src = path;
			script.onload = resolve;
			script.onerror = () =>
				reject({
					message: `Cannot load script: ${path}`,
					reasons: [`Failed to load script: ${path}`]
				});

			document.head.appendChild(script);
		});
	}

	#loadStyle(path) {
		if (this.#hasStyle(path)) {
			return Promise.resolve();
		}

		return new Promise((resolve, reject) => {
			const link = document.createElement("link");
			link.rel = "stylesheet";
			link.href = path;
			link.onload = resolve;
			link.onerror = () =>
				reject({
					message: `Cannot load stylesheet: ${path}`,
					reasons: [`Failed to load stylesheet: ${path}`]
				});

			document.head.appendChild(link);
		});
	}

	#hasScript(path) {
		return document.querySelector(`script[src="${path}"]`) !== null;
	}

	#hasStyle(path) {
		return document.querySelector(`link[href="${path}"]`) !== null;
	}

	#insert(data) {
		const fragment = document.createRange().createContextualFragment(data.html);

		document.body.appendChild(fragment);
	}

	#showSpinner() {
		this.#spinner.classList.add(this.#spinnerActiveClass);
	}

	#hideSpinner() {
		this.#spinner.classList.remove(this.#spinnerActiveClass);
	}
}
