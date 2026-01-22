(function ($) {
	$.fn.masked = function (options) {
		return this.filter("input").each((index, input) => {
			return new Masked(
				input,
				$.extend(
					true,
					{
						mask: "+7 (999) 999-99-99",
						namespace: "masked",
						autoclear: true,
						placeholder: "_",
						definitions: {
							9: "[0-9]",
							a: "[A-Za-z]",
							"*": "[A-Za-z0-9]"
						},
						completed: () => {},
						uncompleted: () => {}
					},
					options
				)
			);
		});
	};

	class Masked {
		constructor(input, options) {
			this.$input = $(input);
			this.options = options;

			this.processor = new Processor(this);
			if (!this.processor.isComplited()) {
				this.processor.first();
			}

			this.events = new Events(this);
			this.events.bind();

			this.state = new State(this);
			this.state.process();
		}

		applyCompleted() {
			this.options.completed.call(this, this);
			this.getInput().trigger("completed", this);

			return this;
		}

		applyUncompleted() {
			this.options.uncompleted.call(this, this);
			this.getInput().trigger("uncompleted", this);

			return this;
		}

		isAutoclearEnabled() {
			return this.options.autoclear;
		}

		getPlaceholder() {
			return this.options.placeholder;
		}

		getDefinitions() {
			return this.options.definitions;
		}

		getNamespace() {
			return this.options.namespace;
		}

		getMask() {
			return this.options.mask;
		}

		getProcessor() {
			return this.processor;
		}

		getEvents() {
			return this.events;
		}

		getState() {
			return this.state;
		}

		getInput() {
			return this.$input;
		}
	}

	class Events {
		constructor(masked) {
			this.masked = masked;
		}

		bind() {
			this.masked
				.getInput()
				.on(this.addNamespace("beforeinput"), (event) => this.beforeinput(event));
			this.masked.getInput().on(this.addNamespace("input"), (event) => this.input(event));
			this.masked.getInput().on(this.addNamespace("focus"), (event) => this.focus(event));
			this.masked.getInput().on(this.addNamespace("blur"), (event) => this.blur(event));
		}

		focus(event) {
			if (this.masked.getProcessor().needReset()) {
				this.masked.getProcessor().reset();
				this.masked.getState().process();
			}
		}

		beforeinput(event) {
			this.masked.getProcessor().updateCache();
		}

		input(event) {
			if (!this.masked.getProcessor().updateCache().isComplited()) {
				this.masked.getProcessor().correct();
				this.masked.getState().process();
			}
		}

		blur(event) {
			if (this.masked.getProcessor().needClear()) {
				this.masked.getProcessor().clear();
				this.masked.getState().process();
			}
		}

		addNamespace(event) {
			return event + "." + this.masked.getNamespace();
		}
	}

	class State {
		constructor(masked) {
			this.masked = masked;
			this.completed = false;
		}

		process() {
			if (this.masked.getProcessor().isComplited() !== this.getComplited()) {
				this.reverse();
			}

			return this;
		}

		reverse() {
			if ((this.completed = !this.completed)) {
				this.masked.applyCompleted();
				return this;
			}

			this.masked.applyUncompleted();
			return this;
		}

		getComplited() {
			return this.completed;
		}
	}

	class Processor {
		constructor(masked) {
			this.masked = masked;
			this.formatter = new Formatter(this.masked);

			this.input = new Input(this);
			this.caret = new Caret(this);
		}

		first() {
			this.getInput().setLastValue(this.getFormatter().getDefaultValue());
			this.getCaret().setVirtualPosition(this.getFormatter().getLastPosition());

			const corrector = new Corrector(this);
			corrector.process();

			if (corrector.existEditedPositions()) {
				this.getInput().setValue(corrector.getResult());
			}

			return this;
		}

		correct() {
			const corrector = new Corrector(this);
			corrector.process();

			this.getInput().setValue(corrector.getResult());
			this.getCaret().setPosition(corrector.getLastEditedPosition());

			return this;
		}

		reset() {
			this.getInput().setValue(this.getFormatter().getDefaultValue());
			this.getCaret().setPositionWithDelay(this.getFormatter().getFirstAvailablePosition());
			return this;
		}

		clear() {
			this.getInput().setValue("");
			return this;
		}

		isComplited() {
			return this.getFormatter().getFinalExpression().test(this.getInput().getValue());
		}

		isEmpty() {
			return (
				this.getInput().getValue() === "" ||
				this.getInput().getValue() === this.getFormatter().getDefaultValue()
			);
		}

		needReset() {
			return !this.isComplited() && this.isEmpty();
		}

		needClear() {
			if (this.getMasked().isAutoclearEnabled()) {
				return !this.isComplited();
			}

			return !this.isComplited() && this.isEmpty();
		}

		updateCache() {
			this.getInput().readValue();
			this.getCaret().readPosition();

			return this;
		}

		getInputNative() {
			return this.masked.getInput().get(0);
		}

		getFormatter() {
			return this.formatter;
		}

		getMasked() {
			return this.masked;
		}

		getCaret() {
			return this.caret;
		}

		getInput() {
			return this.input;
		}
	}

	class Corrector {
		constructor(processor) {
			this.processor = processor;
			this.initValues();
		}

		initValues() {
			this.mask = this.processor.getFormatter().getDefaultValueArray();
			this.value = this.processor.getInput().getValueArray();
			this.lastValue = this.processor.getInput().getLastValueArray();

			this.caretPosition = this.processor.getCaret().getPosition();
			this.caretLastPosition = this.processor.getCaret().getLastPosition();

			this.lastEditedPosition = this.caretLastPosition[0];
			this.updateEditedPositions();

			this.result = this.lastValue;
			return this;
		}

		updateEditedPositions() {
			this.editedPositions = [];

			$.each(this.lastValue, (index, char) => {
				if (this.processor.getFormatter().isAllowedCharInPosition(index, char)) {
					this.editedPositions[index] = char;
				}
			});

			return this;
		}

		process() {
			this.clearSelected();

			if (this.isInput()) {
				return this.input();
			}

			if (this.isBackspace()) {
				return this.backspace();
			}

			if (this.isDelete()) {
				return this.delete();
			}

			return this;
		}

		clearSelected() {
			$.each(this.getSelectedPositions(), (i, position) => {
				this.lastValue[position] = this.mask[position];
				this.result[position] = this.mask[position];
			});

			this.updateEditedPositions();
		}

		backspace() {
			const position = this.getPrevEditedPosition();
			if (position !== -1) {
				this.result[position] = this.mask[position];
				this.lastEditedPosition = position;
				return this;
			}

			const minPosition = this.processor.getFormatter().getFirstAvailablePosition();
			this.lastEditedPosition =
				this.lastEditedPosition > minPosition ? this.lastEditedPosition - 1 : minPosition;

			return this;
		}

		delete() {
			const position = this.getNextEditedPosition();
			if (position !== -1) {
				this.result[position] = this.mask[position];
			}

			return this;
		}

		input(ignored = 0) {
			const inputChars = this.getInputChars();

			if (this.isPaste() && !this.existEditedPositions()) {
				this.caretLastPosition[0] = 0;
			}

			$.each(inputChars, (index, char) => {
				if (!this.inputChar(index - ignored, char)) {
					ignored++;
				}
			});

			return this;
		}

		inputChar(index, char) {
			const position = this.caretLastPosition[0] + index;

			if (this.mask[position] === char) {
				this.lastEditedPosition = position + 1;
				return true;
			}

			const nearestAllowedPosition = this.getNearestAllowedPosition(position);
			if (nearestAllowedPosition !== -1) {
				if (
					this.processor
						.getFormatter()
						.isAllowedCharInPosition(nearestAllowedPosition, char)
				) {
					this.result[nearestAllowedPosition] = char;
					this.lastEditedPosition = nearestAllowedPosition + 1;
					this.editedPositions[nearestAllowedPosition] = char;

					return true;
				}
			}

			return false;
		}

		isInput() {
			return this.getInputChars().length > 0;
		}

		isPaste() {
			return this.getInputChars().length > 1;
		}

		isBackspace() {
			if (this.value.length < this.lastValue.length) {
				return this.caretPosition[0] !== this.caretLastPosition[0];
			}

			return false;
		}

		isDelete() {
			if (this.value.length < this.lastValue.length) {
				return this.caretPosition[0] === this.caretLastPosition[0];
			}

			return false;
		}

		existEditedPositions() {
			return this.editedPositions.length > 0;
		}

		getNearestAllowedPosition(position) {
			return this.processor
				.getFormatter()
				.getAllowedPositions()
				.findIndex((value, index) => {
					return (
						typeof value !== "undefined" &&
						index >= position &&
						typeof this.getEditedPositions()[index] === "undefined"
					);
				});
		}

		getNextEditedPosition() {
			return this.getEditedPositions().findIndex((value, index) => {
				return typeof value !== "undefined" && index >= this.lastEditedPosition;
			});
		}

		getPrevEditedPosition() {
			let position = this.getEditedPositions().length;
			while (position--) {
				if (typeof this.getEditedPositions()[position] === "undefined") {
					continue;
				}

				if (position < this.lastEditedPosition) {
					return position;
				}
			}

			return -1;
		}

		getSelectedPositions(result = []) {
			for (let i = 0; i < this.processor.getCaret().getDelta(); i++) {
				result.push(i + this.caretLastPosition[0]);
			}

			return result;
		}

		getInputChars() {
			return this.value.slice(this.caretLastPosition[0], this.caretPosition[0]);
		}

		getLastEditedPosition() {
			return this.lastEditedPosition;
		}

		getEditedPositions() {
			return this.editedPositions;
		}

		getResult() {
			return this.result.join("");
		}
	}

	class Formatter {
		constructor(masked) {
			this.masked = masked;
			this.initAllowedPositions().initFinalExpression().initDefaltValue();
		}

		initAllowedPositions() {
			this.allowedPositions = [];
			$.each(this.getMaskArray(), (index, value) => {
				if (this.isDefinition(value)) {
					this.allowedPositions[index] = value;
				}
			});

			return this;
		}

		initFinalExpression(expression = ["^"]) {
			$.each(this.getMaskArray(), (index, value) => {
				if (this.isDefinition(value)) {
					expression.push(this.getDefinitionExpression(value));
					return true;
				}

				expression.push(this.addSlashesForRegexp(value));
			});

			expression.push("$");
			this.finalExpression = new RegExp(expression.join(""));

			return this;
		}

		initDefaltValue(result = []) {
			$.each(this.getMaskArray(), (index, value) => {
				result[index] = this.isAllowedPosition(index)
					? this.getMasked().getPlaceholder()
					: value;
			});

			this.defaultValue = result.join("");
			return this;
		}

		isAllowedCharInPosition(position, char) {
			if (this.isAllowedPosition(position)) {
				return new RegExp(
					this.getDefinitionExpression(this.getAllowedPositions()[position])
				).test(char);
			}

			return false;
		}

		isAllowedPosition(position) {
			return typeof this.getAllowedPositions()[position] !== "undefined";
		}

		isDefinition(char) {
			return typeof this.getMasked().getDefinitions()[char] !== "undefined";
		}

		addSlashesForRegexp(value) {
			return value
				.replace(/\[/g, "\\[")
				.replace(/\]/g, "\\]")
				.replace(/\\/g, "\\")
				.replace(/\^/g, "\\^")
				.replace(/\$/g, "\\$")
				.replace(/\./g, "\\.")
				.replace(/\|/g, "\\|")
				.replace(/\?/g, "\\?")
				.replace(/\*/g, "\\*")
				.replace(/\+/g, "\\+")
				.replace(/\(/g, "\\(")
				.replace(/\)/g, "\\)");
		}

		getFirstAvailablePosition() {
			return this.allowedPositions.findIndex((index) => {
				return index;
			});
		}

		getDefinitionExpression(char) {
			return this.getMasked().getDefinitions()[char];
		}

		getLastPosition() {
			return this.getDefaultValueArray().length;
		}

		getFinalExpression() {
			return this.finalExpression;
		}

		getAllowedPositions() {
			return this.allowedPositions;
		}

		getDefaultValueArray() {
			return this.getDefaultValue().split("");
		}

		getDefaultValue() {
			return this.defaultValue;
		}

		getMaskArray() {
			return this.getMasked().getMask().split("");
		}

		getMasked() {
			return this.masked;
		}
	}

	class Input {
		constructor(processor) {
			this.processor = processor;
			this.target = this.processor.getMasked().getInput();
			this.readValue();
		}

		readValue() {
			this.value = this.updateLastValue().getTarget().val();
			return this;
		}

		updateLastValue() {
			this.setLastValue(this.getValue());
			return this;
		}

		setLastValue(value) {
			if (typeof value !== "undefined") {
				this.lastValue = value;
				return this;
			}

			this.lastValue = "";
			return this;
		}

		setValue(value) {
			this.updateLastValue();
			this.value = value;

			this.getTarget().val(value);
			return this;
		}

		getTarget() {
			return this.target;
		}

		getLastValueArray() {
			return this.lastValue.split("");
		}

		getLastValue() {
			return this.lastValue;
		}

		getValueArray() {
			return this.value.split("");
		}

		getValue() {
			return this.value;
		}
	}

	class Caret {
		constructor(processor) {
			this.processor = processor;
			this.readPosition();
		}

		readPosition() {
			this.updateLastPosition();
			this.position = [
				this.processor.getInputNative().selectionStart,
				this.processor.getInputNative().selectionEnd
			];
		}

		updateLastPosition() {
			if (typeof this.position !== "undefined") {
				this.lastPosition = this.position;
				return this;
			}

			this.lastPosition = [0, 0];
			return this;
		}

		setVirtualPosition(position) {
			this.position = position;
		}

		setPosition(position) {
			this.updateLastPosition();
			this.position = [position, position];
			this.processor.getInputNative().setSelectionRange(position, position);
		}

		setPositionWithDelay(position) {
			setTimeout(() => {
				this.setPosition(position);
			}, 0);
		}

		getDelta() {
			return this.getLastPosition()[1] - this.getLastPosition()[0];
		}

		getLastPosition() {
			return this.lastPosition;
		}

		getPosition() {
			return this.position;
		}
	}
})($);
