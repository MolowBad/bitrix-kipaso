//global vars
var DwBasket = {};

//jquery
$(function () {
	//modern mode
	"use strict";

	//constants for fast customization
	const classNameCluster = {
		//main
		order: "DwBasketOrder",
		basket: "DwBasket",

		//select fields
		personType: "personSelect",
		personTypeModules: "personTypeModules",

		//stores
		storesSwitch: "storeSelect",
		storesContainer: "storeSelectContainer",

		//form
		orderForm: "orderForm",
		orderFormActive: "orderForm.active",

		//checkbox fields
		budgetContainer: "payFromBudget",
		budget: "budgetSwitch",

		//input fields
		quantityField: "qty",

		//extra services
		extraService: "extraServiceSwitch",
		extraServiceItem: "extraServicesItem",
		extraServiceItems: "extraServiceItems",
		extraServiceItemSum: "extraServiceItemSum",
		extraServiceItemContainer: "extraServicesItemContainer",

		//delivery
		delivery: "deliverySelect",
		deliverySum: "deliverySum",
		deliveryProps: "deliveryProps",
		deliveryDescription: "deliveryDescription",
		deliveryPeriod: "deliveryPeriod",
		deliveryPeriodDescription: "deliveryPeriodDescription",
		deliveryModulesContainer: "deliveryModulesContainer",
		deliveryModulesContainerDesc: "deliveryModulesContainerDesc",

		//paysystem
		paysystem: "paySelect",
		paysystemProps: "payProps",

		//location
		locationField: "location",
		locationSwitchLink: "locationSwitchLink",
		locationSwitchContainer: "locationSwitchContainer",

		//coupon
		couponButton: "couponActivate",
		couponField: "couponField",

		//confirm
		confirmForm: "confirmForm",
		confirmSend: "confirmSend",
		confirmField: "confirmCode",
		confirmTimer: "confirmTimer",
		confirmReplay: "confirmReplay",
		confirmErrors: "confirmErrors",
		confirmErrorItem: "confirmErrorItem",
		confirmSubstrate: "confirmSubstrate",

		//link
		remove: "delete",
		minus: "minus",
		plus: "plus",

		//basket items
		productItemList: "productList",
		productItem: "parent",

		//product fields
		productPrice: "priceContainer",
		productDiscount: "discountContainer",

		//properties
		propertyItem: "propItem",

		//special
		dropDownSelected: "dropDownSelected",
		clearAll: "clearAllBasketItems",
		giftContainer: "giftContainer",
		dropDownItems: "dropDownItems",
		dropDownItem: "dropDownItem",
		minPayment: "minimumPayment",
		orderComment: "orderComment",
		fastBuy: "fastBayContainer",
		dropDownActive: "selected",
		dropDownOpened: "opened",
		countItems: "countItems",
		orderAreas: "orderAreas",
		orderMove: "orderMove",
		basketSum: "basketSum",
		goToOrder: "goToOrder",
		orderSum: "orderSum",
		propActive: "active",
		propHidden: "hidden",
		dropDown: "dropDown",

		//erros
		errorWindowClose: "basketErrorClose",
		errorWindowMessage: "errorMessage",
		errorWindow: "basketError",
		error: "error",

		//new order
		orderMake: "orderMake"
	};

	//error form ids
	const errorIds = {
		maxQuantity: "error1",
		orderErrors: "error2",
		couponError: "error3"
	};

	//other constants
	const timeoutDelay = 400;

	//vars
	var timeoutId = 0;
	var deliveryId = 0;
	var compilation = {};

	//application object map
	var application = {
		calculate: {},
		binding: {
			keypress: {},
			keydown: {},
			mouseup: {},
			change: {},
			submit: {},
			input: {},
			click: {},
			focus: {},
			keyup: {},
			blur: {}
		},
		event: {},
		tools: {},
		commit: {
			basket: {},
			location: {}
		},
		flags: {
			location: {
				open: false
			},
			dropDown: {
				open: false
			}
		},
		check: {},
		price: {},
		ajax: {},
		push: {},
		pull: {}
	};

	//binding functions

	//click events
	application.binding.click.stop = function (event) {
		return event.stopImmediatePropagation();
	};

	application.binding.click.closeout = function (event) {
		//location
		if (application.flags.location.open === true) {
			application.tools.resetLocation(event);
		}

		//dropdown select
		if (application.flags.dropDown.open === true) {
			application.tools.closeDropDown();
		}
	};

	application.binding.click.openDropDown = function (event) {
		//jquery vars
		var $this = $(this);
		var $dropContainer = $this.siblings("." + classNameCluster.dropDownItems);
		var $dropItems = $dropContainer.find("." + classNameCluster.dropDownItem);

		//check opened
		if (!$dropContainer.hasClass(classNameCluster.dropDownOpened)) {
			//show
			$dropContainer.addClass(classNameCluster.dropDownOpened);

			//set opened flag
			application.flags.dropDown.open = true;
		}

		//close
		else {
			application.tools.closeDropDown();
		}

		//block actions
		return event.preventDefault();
	};

	application.binding.click.selectDropDown = function (event) {
		//jquery vars
		var $this = $(this);

		//vars
		var $dropContainer = $this.parents("." + classNameCluster.dropDown);
		var $dropItems = $dropContainer.find("." + classNameCluster.dropDownItem);
		var $selectedContainer = $dropContainer.find("." + classNameCluster.dropDownSelected);
		var $nativeElement = $dropContainer.find("select");

		//other
		var value = $this.data("value");
		var selected = $this.hasClass(classNameCluster.dropDownActive);
		var triggerId = $dropContainer.data("trigger-id");

		//reset
		$dropItems.removeClass(classNameCluster.dropDownActive);

		//set selected
		$this.addClass(classNameCluster.dropDownActive);

		//change selected html
		$selectedContainer.html($this.html());

		//show
		$dropContainer
			.find("." + classNameCluster.dropDownItems)
			.removeClass(classNameCluster.dropDownOpened);

		//set opened flag
		application.flags.dropDown.open = false;

		//check selected
		if (selected == false) {
			//check data-trigger for event & native change
			if (typeof $nativeElement != "undefined" && $nativeElement.length > 0) {
				//change selected option
				$nativeElement.find("option").prop("selected", false);
				$nativeElement.find('option[value="' + value + '"]').prop("selected", true);

				//activate event
				$nativeElement.trigger("change");
			}

			//activate event
			$dropContainer.trigger("change");
		}

		//block actions
		return event.preventDefault();
	};

	//set sms confirm
	application.binding.click.confirmSend = function (event) {
		//jquery vars
		var $this = $(this);
		var $confirmErrorContainer = $("." + classNameCluster.confirmErrors);
		var $confirmReplay = $("." + classNameCluster.confirmReplay);
		var $confirmField = $("." + classNameCluster.confirmField);
		var $confirmForm = $("." + classNameCluster.confirmForm);

		//other
		var sendObject = new FormData();
		var smsCode = $confirmField.val();
		var orderId = $confirmField.data("order-id");
		var successText = $this.data("success");

		//push request params
		sendObject.append("siteId", siteId);
		sendObject.append("orderId", orderId);
		sendObject.append("actionType", "smsConfirm");

		//check filling & length
		if (typeof smsCode != "undefined" && smsCode != "" && smsCode.length == 4) {
			//sms code
			sendObject.append("smsCode", smsCode);

			//push arParams
			sendObject = application.push.params(sendObject);

			//remove error class
			application.tools.removeError($this);
			application.tools.removeError($confirmForm);

			//clear error container
			application.tools.clear($confirmErrorContainer);

			//hide old errors
			$confirmErrorContainer.removeClass("opened");

			//start loader
			application.tools.launchLoader($this);

			//send data
			application.ajax.sendData(
				ajaxDir + "/ajax.php",
				sendObject,
				"post",
				"json",
				dataProcessing,
				false
			);

			//proccesing data after request
			function dataProcessing(jsonData) {
				//check state
				if (jsonData["status"] === true) {
					//set success state
					$confirmForm.addClass("success");

					//hide replay button
					application.tools.hideByFlag($confirmReplay, true);

					//change button text
					$this.text(successText);
				}

				//check errors
				else {
					//check exist error messages
					if (jsonData["error"] === true && typeof jsonData["errors"] == "object") {
						//push errors to container
						$.each(jsonData["errors"], function () {
							if (typeof this == "string") {
								$confirmErrorContainer.append(
									$("<div/>", { class: classNameCluster.confirmErrorItem }).html(
										this
									)
								);
							}
						});

						//show error container
						$confirmErrorContainer.addClass("opened");

						//add error class
						application.tools.addError($this);
						application.tools.addError($confirmForm);

						//push to console
						console.error(jsonData);
					}
				}

				//remove loader
				application.tools.stopLoader($this);
			}
		}

		//block actions
		return event.preventDefault();
	};

	//replay sms code
	application.binding.click.confirmReplay = function () {
		//jquery vars
		var $this = $(this);
		var $confirmErrorContainer = $("." + classNameCluster.confirmErrors);

		//other
		var sendObject = new FormData();
		var orderId = $this.data("order-id");

		//push request params
		sendObject.append("siteId", siteId);
		sendObject.append("orderId", orderId);
		sendObject.append("actionType", "smsReplay");

		//clear error container
		application.tools.clear($confirmErrorContainer);

		//hide old errors
		$confirmErrorContainer.removeClass("opened");

		//start loader
		application.tools.launchLoader($this);

		//send data
		application.ajax.sendData(
			ajaxDir + "/ajax.php",
			sendObject,
			"post",
			"json",
			dataProcessing,
			false
		);

		//proccesing data after request
		function dataProcessing(jsonData) {
			//check state
			if (jsonData["status"] === true) {
				//set new timer
				application.tools.startTimer(
					"." + classNameCluster.confirmTimer,
					"." + classNameCluster.confirmReplay
				);

				//set success state
				$this.addClass("success");
				$this.removeClass("ready");
			}

			//check errors
			else {
				//check exist error messages
				if (jsonData["error"] === true && typeof jsonData["errors"] == "object") {
					//push errors to container
					$.each(jsonData["errors"], function () {
						if (typeof this == "string") {
							$confirmErrorContainer.append(
								$("<div/>", { class: classNameCluster.confirmErrorItem }).html(this)
							);
						}
					});

					//show error container
					$confirmErrorContainer.addClass("opened");

					//push to console
					console.error(jsonData);
				}
			}

			//remove loader
			application.tools.stopLoader($this);
		}

		//block actions
		return event.preventDefault();
	};

	//create order
	application.binding.click.orderMake = function (event) {
		//jquery vars
		var $this = $(this);

		//other
		var sendObject = new FormData();

		//push request params
		sendObject.append("actionType", "orderMake");
		sendObject.append("siteId", siteId);

		//get orderFields
		var $orderFields = application.pull.getOrderFields();

		//check required fields
		if (application.check.required($orderFields)) {
			//push order fields values
			sendObject = application.push.serializeFields($orderFields, sendObject);

			//push order comment
			sendObject = application.push.orderComment(sendObject);

			//push basket fields values
			sendObject = application.push.fields(sendObject);

			//push arParams
			sendObject = application.push.params(sendObject);

			//start loader
			application.tools.launchLoader($this);

			//send data
			application.ajax.sendData(
				ajaxDir + "/ajax.php",
				sendObject,
				"post",
				"json",
				dataProcessing,
				false
			);

			//proccesing data after request
			function dataProcessing(jsonData) {
				//check state
				if (
					jsonData["status"] === true &&
					typeof jsonData["orderResult"]["orderId"] != undefined
				) {
					//metrica
					if (
						typeof globalSettings != "undefined" &&
						typeof globalSettings["TEMPLATE_METRICA_ORDER"] != "undefined" &&
						typeof globalSettings["TEMPLATE_METRICA_ID"] != "undefined" &&
						typeof window["yaCounter" + globalSettings["TEMPLATE_METRICA_ID"]] !=
							"undefined"
					) {
						window["yaCounter" + globalSettings["TEMPLATE_METRICA_ID"]].reachGoal(
							globalSettings["TEMPLATE_METRICA_ORDER"]
						);
					}

					//redirect
					window.location.href = application.tools.addParamToUrl({
						orderId: jsonData["orderResult"]["orderId"]
					});
				}

				//check errors
				else {
					if (jsonData["error"] === true) {
						application.tools.addError($this);
						application.tools.pushAjaxErrors(jsonData);
						console.error(jsonData);
					}
				}

				//remove loader
				application.tools.stopLoader($this);
			}
		}

		return event.preventDefault();
	};

	application.binding.click.setCoupon = function (event) {
		//jquery vars
		var $couponField = $("." + classNameCluster.couponField);
		var $this = $(this);

		//other
		var couponValue = $couponField.val();

		//check empty
		if (couponValue != "undefined" && couponValue != "") {
			//create formData
			var sendObject = new FormData();

			//push request params
			sendObject.append("actionType", "setCoupon");
			sendObject.append("coupon", couponValue);
			sendObject.append("siteId", siteId);

			//start loader
			application.tools.launchLoader($this);

			//send data
			application.ajax.sendData(
				ajaxDir + "/ajax.php",
				sendObject,
				"post",
				"json",
				dataProcessing,
				false
			);

			//proccesing data after request
			function dataProcessing(jsonData) {
				//check state
				if (jsonData["status"] === true && jsonData["success"] === true) {
					//page reload
					window.location.reload();
				}

				//check errors
				else {
					if (jsonData["error"] === true) {
						//set errors
						application.tools.addError($couponField);
						application.tools.pushAjaxErrors(jsonData, "couponError");
						console.error(jsonData);
					}
				}

				//remove loader
				application.tools.stopLoader($this);
			}
		}

		//set error
		else {
			application.tools.addError($couponField);
		}

		//block actions
		return event.preventDefault();
	};

	application.binding.click.clearAll = function (event) {
		//jquery vars
		var $this = $(this);

		//create formData
		var sendObject = new FormData();

		//push request params
		sendObject.append("actionType", "clearAll");
		sendObject.append("siteId", siteId);

		//start loader
		application.tools.launchLoader($this);

		//send data
		application.ajax.sendData(
			ajaxDir + "/ajax.php",
			sendObject,
			"post",
			"json",
			dataProcessing,
			false
		);

		//proccesing data after request
		function dataProcessing(jsonData) {
			//check state
			if (jsonData["status"] === true) {
				//page reload
				window.location.reload();
			}

			//check errors
			else {
				if (jsonData["error"] === true) {
					application.tools.pushAjaxErrors(jsonData);
					console.error(jsonData);
				}
			}

			//remove loader
			application.tools.stopLoader($this);
		}

		//block actions
		return event.preventDefault();
	};

	application.binding.click.deleteItem = function (event) {
		//jquery vars
		var $this = $(this);

		//other
		var basketItemId = $this.data("id");
		var sendObject = new FormData();

		//push request params
		sendObject.append("actionType", "removeItem");
		sendObject.append("basketId", basketItemId);
		sendObject.append("siteId", siteId);

		//push arParams
		sendObject = application.push.params(sendObject);

		//push basket fields values
		sendObject = application.push.fields(sendObject);

		//start loader
		application.tools.launchLoader($this);

		//send data
		application.ajax.sendData(
			ajaxDir + "/ajax.php",
			sendObject,
			"post",
			"json",
			dataProcessing,
			false
		);

		//proccesing data after request
		function dataProcessing(jsonData) {
			//check state
			if (jsonData["status"] === true) {
				//reloading page if not find items
				if (
					$this
						.parents("." + classNameCluster.productItemList)
						.find("." + classNameCluster.productItem).length <= 1
				) {
					//page reload
					window.location.reload();
				} else {
					//delete dom item
					$this.parents("." + classNameCluster.productItem).remove();
					//commit result data
					application.commit.basket.proccesing(jsonData);
				}
			}

			//check errors
			else {
				if (jsonData["error"] === true) {
					application.tools.pushAjaxErrors(jsonData);
					console.error(jsonData);
				}
			}
		}

		//block actions
		return event.preventDefault();
	};

	application.binding.click.quantityMinus = function (event) {
		//jquery vars
		var $this = $(this);
		var $basketItem = $this.parents("." + classNameCluster.productItem);
		var $quantityField = $basketItem.find("." + classNameCluster.quantityField);

		//other
		var measureRatio = parseFloat($quantityField.data("ratio"));
		var currentQuantity = parseFloat($quantityField.val());

		//need changing
		if (currentQuantity > measureRatio) {
			//pre calculate quantity
			var preQuantity = currentQuantity - measureRatio;

			//set value
			$quantityField.val(+preQuantity.toFixed(5));
			$quantityField.trigger("change");
		}

		//block actions
		return event.preventDefault();
	};

	application.binding.click.quantityPlus = function (event) {
		//jquery vars
		var $this = $(this);
		var $basketItem = $this.parents("." + classNameCluster.productItem);
		var $quantityField = $basketItem.find("." + classNameCluster.quantityField);

		//other
		var measureRatio = parseFloat($quantityField.data("ratio"));
		var currentQuantity = parseFloat($quantityField.val());

		//pre calculate quantity
		var preQuantity = currentQuantity + measureRatio;

		//set value
		$quantityField.val(+preQuantity.toFixed(5));
		$quantityField.trigger("change");

		//block actions
		return event.preventDefault();
	};

	application.binding.click.locationLink = function (event) {
		//jquery vars
		var $this = $(this);
		var $locationContainer = $this.parents("." + classNameCluster.locationSwitchContainer);
		var $locationField = $locationContainer.siblings("." + classNameCluster.locationField);

		//other
		var locationName = $this.data("path");
		var locationId = $this.data("id");

		//set values
		$locationField.val(locationName);
		$locationField.data("location", locationId);

		//close
		application.tools.closeLocationDropdown($locationContainer);

		//flush basket
		application.commit.basket.flush();

		//block actions
		return event.preventDefault();
	};

	application.binding.click.orderMove = function () {
		//vars
		var $order = $("." + classNameCluster.order);
		var $minPayment = $("." + classNameCluster.minPayment);

		//other
		var blockActions = true;

		//check object
		if (typeof $order === "object" && $order.is(":visible")) {
			application.tools.scrollTo($order);
		}

		//check min payment to scroll
		else {
			//check object
			if (typeof $minPayment === "object" && !$.isEmptyObject($minPayment)) {
				//check for available
				if ($minPayment.is(":visible")) {
					application.tools.scrollTo($minPayment);
				} else {
					blockActions = false;
				}
			}
		}

		//block actions
		if (blockActions === true) {
			return event.preventDefault();
		}
	};

	application.binding.click.closeErrorWindow = function () {
		//jquery vars
		var $this = $(this);
		var $errorWindow = $this.parents("." + classNameCluster.errorWindow);

		//hide
		application.tools.closeWindow($errorWindow);

		//block actions
		return event.preventDefault();
	};

	//submit events
	application.binding.submit.confirmForm = function (event) {
		//emulation send button click
		$("." + classNameCluster.confirmSend).trigger("click");

		//block actions
		return event.preventDefault();
	};

	//keyup events
	application.binding.keyup.location = function (event) {
		//remove previous
		clearTimeout(timeoutId);

		//realtime calc
		timeoutId = setTimeout(application.commit.location.pushComponent, timeoutDelay);
	};

	//on input events
	application.binding.input.confirm = function (event) {
		//jquery vars
		var $this = $(this);

		//other
		var currentValue = $this.val();

		//clear not allowed
		currentValue = currentValue.replace(/[^0-9]/gim, "").substr(0, 4);

		//reset input value
		$this.val(currentValue);

		//check lenght
		if (typeof currentValue != "undefined" && currentValue.length <= 4) {
			//get substrate field
			var $substrate = $("." + classNameCluster.confirmSubstrate);

			//supplement zero
			while (currentValue.length < 4) {
				currentValue += "0";
			}
		}

		//write
		$substrate.html(currentValue);
	};

	//keypress events
	application.binding.keypress.quantity = function (event) {
		//available characters
		var allowedChars = [44, 46, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57];

		//check which
		if (allowedChars.indexOf(event.which) === -1) {
			//block actions
			return event.preventDefault();
		}
	};

	//focus events
	application.binding.focus.location = function (event) {
		//jquery vars
		var $this = $(this);

		//other
		var currentValue = $this.val().toString();
		var currentId = $this.data("location");

		//save last value
		$this.data("last-value", currentValue);
		$this.data("last-id", currentId);

		//reset
		$this.val("");
		$this.data("location", "");
	};

	application.binding.focus.coupon = function (event) {
		//jquery vars
		var $this = $(this);

		//clear errors
		application.tools.removeError($this);
	};

	//blur events
	application.binding.blur.location = function (event) {
		if (application.flags.location.open === false) {
			application.tools.resetLocation(event);
		}

		return false;
	};

	//change events
	application.binding.change.quantity = function (event) {
		//jquery vars
		var $this = $(this);

		//other
		var basketItemId = $this.data("id");
		var maxAmount = parseFloat($this.data("max-quantity"));
		var measureRatio = parseFloat($this.data("ratio"));
		var currentQuantity = parseFloat($this.val());
		var multiplicity = +(currentQuantity / measureRatio).toFixed(5);
		var defQuantity = currentQuantity;

		//reset errors
		application.tools.removeError($this);

		//need changing
		//check max amount
		if (typeof maxAmount !== undefined && currentQuantity > maxAmount) {
			//set max value
			currentQuantity = maxAmount;

			//commit
			$this.val(currentQuantity);

			//set error
			application.tools.addError($this);

			//show error window flag 1
			application.tools.openWindow(errorIds.maxQuantity);
		}

		//check nan & min value
		else if (isNaN(currentQuantity) || currentQuantity < measureRatio) {
			//set min value
			currentQuantity = measureRatio;

			//commit
			$this.val(currentQuantity);
		}

		//check multiple of number
		else if ((multiplicity ^ 0) !== multiplicity) {
			//get near quantity
			var currentQuantity = Math.ceil(currentQuantity / measureRatio) * measureRatio;

			//commit
			$this.val(currentQuantity);
		}

		//
		else {
			//save value
			$this.data("last-value", currentQuantity);
			$this.val(currentQuantity);
		}

		//remove previous
		clearTimeout(timeoutId);

		//realtime calc
		timeoutId = setTimeout(function () {
			application.commit.basket.updateProduct(basketItemId, currentQuantity);
		}, timeoutDelay);
	};

	application.binding.change.person = function (event) {
		//jquery vars
		var $this = $(this);
		var $orderForm = $("." + classNameCluster.orderForm);
		var $orderAreas = $("." + classNameCluster.orderAreas);
		var $personTypeModules = $("." + classNameCluster.personTypeModules);

		//other
		var personTypeId = parseInt($this.val());

		//change state
		$personTypeModules.filter('[data-id="' + personTypeId + '"]').prop("checked", true);

		//reset delivery
		deliveryId = 0;

		//hide all areas
		application.tools.hideByFlag($orderAreas, false, "active");

		//show actual areas
		application.tools.hideByFlag(
			$orderAreas.filter('[data-person-id="' + personTypeId + '"]'),
			true,
			"active"
		);

		//remove active class for order form
		application.tools.hideByFlag($orderForm, false, "active");

		//select active order form
		application.tools.hideByFlag(
			$orderForm.filter('[data-id="' + personTypeId + '"]'),
			true,
			"active"
		);

		//flush
		application.commit.basket.flush();
	};

	application.binding.change.delivery = function (event) {
		application.commit.basket.flush();
	};

	application.binding.change.paysystem = function (event) {
		application.commit.basket.flush();
	};

	application.binding.change.budget = function (event) {
		application.commit.basket.realtime();
	};

	application.binding.change.extraService = function (event) {
		//jquery vars
		var $this = $(this);
		var $basket = $("." + classNameCluster.basket);
		var $deliveryField = $basket.find("." + classNameCluster.delivery).find("option:selected");

		//get input type
		var type = $this.attr("type");
		var tagName = $this.prop("tagName").toLowerCase();

		//get values
		var values = {
			current: parseFloat($this.val()),
			default: parseFloat($this.data("default")),
			price: parseFloat($this.data("price")),
			last: parseFloat($this.data("last"))
		};

		//delivery info
		var delivery = {
			price: parseFloat($deliveryField.data("price")),
			name: $deliveryField.data("name")
		};

		//special
		var final = {},
			last = {};

		//check type
		if (type == "text") {
			//jquery vars
			var $sumContainer = $this
				.parents("." + classNameCluster.extraServiceItem)
				.find("." + classNameCluster.extraServiceItemSum);

			//check nan & min value
			if (isNaN(values.current) || values.current < 0) {
				values.current = values.default;
			}

			//calculate final sum
			final.sum = values.current * values.price;

			//calculate last sum
			last.sum = values.last * values.price;

			//calculate final delivery sum
			delivery.sum = delivery.price - last.sum + final.sum;

			//update sum container
			$sumContainer.html(application.price.format(final.sum, false, false, false, true));

			//update quantity
			$this.val(values.current).data("last", values.current);
		} else if (type == "checkbox") {
			delivery.sum = $this.is(":checked")
				? delivery.price + values.price
				: delivery.price - values.price;
		} else if (tagName == "select") {
			//get price
			values.current = parseFloat($this.find("option:selected").data("price"));

			//calculate total delivery sum
			delivery.sum = delivery.price - values.last + values.current;

			//update last
			$this.data("last", values.current);
		}

		//flush basket
		application.commit.basket.flush();
	};

	//commit functions

	//basket
	application.commit.basket.updateProduct = function (basketId, quantity) {
		if (typeof basketId === "number" && typeof quantity === "number") {
			//jquery vars
			var $self = $("." + classNameCluster.basket);

			//other
			var sendObject = new FormData();

			//push request params
			sendObject.append("actionType", "updateItem");
			sendObject.append("basketId", basketId);
			sendObject.append("quantity", quantity);
			sendObject.append("siteId", siteId);

			//push arParams
			sendObject = application.push.params(sendObject);

			//push basket fields values
			sendObject = application.push.fields(sendObject);

			//start loader
			application.tools.launchLoader($self, "wait");

			//send data
			application.ajax.sendData(
				ajaxDir + "/ajax.php",
				sendObject,
				"post",
				"json",
				dataProcessing,
				false
			);

			//proccesing data after request
			function dataProcessing(jsonData) {
				//check state
				if (jsonData["status"] === true) {
					//commit result data
					application.commit.basket.proccesing(jsonData);
				}

				//check errors
				else {
					if (jsonData["error"] === true) {
						application.tools.pushAjaxErrors(jsonData);
						console.error(jsonData);
					}
				}

				//remove loader
				application.tools.stopLoader($self, "wait");
			}
		} else {
			console.error("check transmitted data");
		}
	};

	application.commit.basket.flush = function () {
		//jquery vars
		var $self = $("." + classNameCluster.basket);

		//other
		var sendObject = new FormData();

		//push request params
		sendObject.append("actionType", "getCompilation");
		sendObject.append("siteId", siteId);

		//push basket fields values
		sendObject = application.push.fields(sendObject);

		//push arParams
		sendObject = application.push.params(sendObject);

		//start loader
		application.tools.launchLoader($self, "wait");

		//send data
		application.ajax.sendData(
			ajaxDir + "/ajax.php",
			sendObject,
			"post",
			"json",
			dataProcessing,
			false
		);

		//proccesing data after request
		function dataProcessing(jsonData) {
			//check state
			if (jsonData["status"] === true) {
				//commit result data
				application.commit.basket.proccesing(jsonData);
			}

			//check errors
			else {
				if (jsonData["error"] === true) {
					application.tools.pushAjaxErrors(jsonData);
					console.error(jsonData);
				}
			}

			//remove loader
			application.tools.stopLoader($self, "wait");
		}
	};

	application.commit.basket.proccesing = function (jsonData) {
		//set global compilation for fast access
		application.commit.basket.setCompilation(jsonData);

		//commit other
		application.commit.basket.items();
		application.commit.basket.deliveries();
		application.commit.basket.paysystems();
		application.commit.basket.minOrderPrice();

		//gifts
		application.commit.basket.gifts(jsonData);

		//update basket fields
		application.commit.basket.realtime();

		//event
		application.event.trigger("processing", jsonData);
	};

	application.commit.basket.items = function () {
		//check empty
		if (typeof compilation["items"] === "object" && !$.isEmptyObject(compilation["items"])) {
			//get items data from last compilation data
			var basketItems = compilation["items"];

			//get basket items DOM
			var $basketItems = $("." + classNameCluster.productItem);

			//each data
			$.each(basketItems, function (currentIndex, basketItem) {
				//find current product
				var $basketItem = $basketItems.filter(
					'[data-id="' + basketItem["PRODUCT_ID"] + '"]'
				);
				//find price container
				var $basketItemPrice = $basketItem.find("." + classNameCluster.productPrice);
				//find discount container
				var $basketItemDiscount = $basketItem
					.find("." + classNameCluster.productDiscount)
					.addClass("hidden");

				//commit price
				$basketItemPrice.html(basketItem["PRICE_FORMATED"]);
				$basketItemPrice.data("price", basketItem["PRICE"]);

				//commit discount price
				if (basketItem["DISCOUNT"] > 0) {
					$basketItemDiscount
						.html(basketItem["BASE_PRICE_FORMATED"])
						.removeClass("hidden");
				}
			});
		}
	};

	application.commit.basket.deliveries = function () {
		//check empty
		if (
			typeof compilation["order"]["DELIVERIES"] === "object" &&
			!$.isEmptyObject(compilation["order"]["DELIVERIES"])
		) {
			//jquery vars
			var $this = $("." + classNameCluster.propActive + " ." + classNameCluster.delivery);

			//get dropdown container
			var $dropDown = $this.parents("." + classNameCluster.dropDown);
			var $dropDownItems = $dropDown.find("." + classNameCluster.dropDownItems);
			var $dropDownSelected = $dropDown.find("." + classNameCluster.dropDownSelected);
			var $dropDownItemFirst = false;

			//other
			var currentId = parseInt($this.val());
			var selectedId = 0;

			//get deliveries from last compilation data
			var deliveries = compilation["order"]["DELIVERIES"];

			//get delivery sorted indexes
			var sortDeliveries = application.tools.sortByIndex(deliveries);

			//get last delivery id
			deliveryId = application.pull.getLastDeliveryId();

			//clear
			application.tools.clear($this);
			application.tools.clear($dropDownItems);

			//each data
			$.each(sortDeliveries, function (currentIndex, nextDeliveryId) {
				//get next delivery
				var nextDelivery = deliveries[nextDeliveryId];

				//create next delivery item
				var $deliveryItem = $("<option/>").val(nextDelivery["ID"]);
				var $dropDownItem = $("<div/>", { class: classNameCluster.dropDownItem }).data(
					"value",
					nextDelivery["ID"]
				);

				//set data attrs (price, name)
				$deliveryItem.data({
					price: nextDelivery["PRICE"],
					name: nextDelivery["NAME"]
				});

				//set title
				$deliveryItem.html(nextDelivery["NAME"] + " " + nextDelivery["PRICE_FORMATED"]);
				$dropDownItem.html(nextDelivery["NAME"] + " " + nextDelivery["PRICE_FORMATED"]);

				//set selected
				if (currentId == nextDelivery["ID"]) {
					//set option selected
					$deliveryItem.attr("selected", "selected");

					//set selected in dropdown
					$dropDownItem.addClass(classNameCluster.dropDownActive);

					//change title in dropdown selected item
					$dropDownSelected.html(
						nextDelivery["NAME"] + " " + nextDelivery["PRICE_FORMATED"]
					);

					//write selected id
					selectedId = nextDelivery["ID"];
				}

				//save first item
				if ($dropDownItemFirst == false) {
					$dropDownItemFirst = $dropDownItem;
				}

				//push
				$this.append($deliveryItem);
				$dropDownItems.append($dropDownItem);
			});

			//refresh var
			currentId = parseInt($this.val());

			//check selected id
			if (selectedId == 0 && currentId != "") {
				//get delivery from result
				var currentDelivery = deliveries[currentId];

				//check current delivery
				if (typeof currentDelivery != "undefined" && !$.isEmptyObject(currentDelivery)) {
					//add selected class
					$dropDownItemFirst.addClass(classNameCluster.dropDownActive);

					//set title
					$dropDownSelected.html(
						currentDelivery["NAME"] + " " + currentDelivery["PRICE_FORMATED"]
					);
				}
			}

			//processing
			application.commit.basket.deliveryStores(currentId);
			application.commit.basket.deliveryProperties(currentId);

			//check delivery id change
			if (deliveryId != currentId) {
				//need refresh extra services
				application.commit.basket.deliveryServices(currentId);

				//refresh delivery period
				//marketplace modules must append html content into delivery period
				application.commit.basket.deliveryPeriod(currentId);

				//refresh delivery desctiption
				application.commit.basket.deliveryDescription(currentId);
			}

			return (deliveryId = currentId);
		}
	};

	application.commit.basket.deliveryStores = function (deliveryId) {
		//check types
		if (
			typeof deliveryId !== "number" ||
			isNaN(deliveryId) ||
			typeof compilation["order"]["DELIVERIES"] !== "object" ||
			typeof compilation["stores"] !== "object"
		) {
			return false;
		}

		//jquery vars
		var $storesContainer = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.storesContainer
		);
		var $storesSwitch = $storesContainer.find("." + classNameCluster.storesSwitch);

		//get dropdown container
		var $dropDown = $storesSwitch.parents("." + classNameCluster.dropDown);
		var $dropDownItems = $dropDown.find("." + classNameCluster.dropDownItems);
		var $dropDownSelected = $dropDown.find("." + classNameCluster.dropDownSelected);
		var $dropDownItemFirst = false;

		//other
		var hideContainer = true;
		var selectedId = false;
		var currentId = parseInt($storesSwitch.val());

		//clear
		application.tools.clear($storesSwitch);
		application.tools.clear($dropDownItems);

		//get deliveries from last compilation data
		var deliveriesData = compilation["order"]["DELIVERIES"];
		var storesData = compilation["stores"];

		//check stores
		if (
			typeof deliveriesData[deliveryId]["STORES"] === "object" &&
			!$.isEmptyObject(deliveriesData[deliveryId]["STORES"])
		) {
			//each stores
			$.each(storesData, function (index, nextStore) {
				//check exist
				if ($.inArray(nextStore["ID"], deliveriesData[deliveryId]["STORES"]) != -1) {
					//create next store item
					var $storeItem = $("<option/>").val(nextStore["ID"]);
					var $dropDownItem = $("<div/>", { class: classNameCluster.dropDownItem }).data(
						"value",
						nextStore["ID"]
					);

					//set title
					$storeItem.html(
						nextStore["TITLE"] +
							" - " +
							nextStore["ADDRESS"] +
							" - " +
							nextStore["PRODUCTS_STATUS"]
					);
					$dropDownItem.html(
						nextStore["TITLE"] +
							" - " +
							nextStore["ADDRESS"] +
							" - " +
							nextStore["PRODUCTS_STATUS"]
					);

					//set selected
					if (currentId == nextStore["ID"]) {
						//set option selected
						$storeItem.attr("selected", "selected");

						//set selected in dropdown
						$dropDownItem.addClass(classNameCluster.dropDownActive);

						//change title in dropdown selected item
						$dropDownSelected.html(
							nextStore["TITLE"] +
								" - " +
								nextStore["ADDRESS"] +
								" - " +
								nextStore["PRODUCTS_STATUS"]
						);

						//write selected id
						selectedId = nextStore["ID"];
					}

					//save first item
					if ($dropDownItemFirst == false) {
						$dropDownItemFirst = $dropDownItem;
					}

					//push
					$storesSwitch.append($storeItem);
					$dropDownItems.append($dropDownItem);

					//set flag (no hide)
					hideContainer = false;
				}
			});

			//refresh var
			currentId = parseInt($storesSwitch.val());

			//check selected id
			if (selectedId == 0 && currentId != "") {
				//get delivery from result
				var currentStore = storesData[currentId];

				//add selected class
				$dropDownItemFirst.addClass(classNameCluster.dropDownActive);

				//set title
				$dropDownSelected.html(
					currentStore["TITLE"] +
						" - " +
						currentStore["ADDRESS"] +
						" - " +
						currentStore["PRODUCTS_STATUS"]
				);
			}
		}

		//hide if not exist stores
		application.tools.hideByFlag($storesContainer, hideContainer);
	};

	application.commit.basket.deliveryServices = function (deliveryId) {
		//check types
		if (typeof deliveryId !== "number" || isNaN(deliveryId) == true || deliveryId <= 0) {
			return false;
		}

		//check extra service html
		if (
			typeof compilation["order"]["DELIVERIES"][deliveryId]["EXTRA_SERVICES_HTML"] !=
			"undefined"
		) {
			var extraServiceHTML =
				compilation["order"]["DELIVERIES"][deliveryId]["EXTRA_SERVICES_HTML"];
		}

		//jquery vars
		var $serviceItems = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.extraServiceItems
		);

		//other
		var serviceDisabled = typeof extraServiceHTML == "undefined" || extraServiceHTML == "";

		//hide / display extra services container
		application.tools.hideByFlag($serviceItems, serviceDisabled);

		//push html
		serviceDisabled === false
			? $serviceItems.html(extraServiceHTML)
			: application.tools.clear($serviceItems);
	};

	application.commit.basket.deliveryProperties = function (deliveryId) {
		//check types
		if (
			typeof deliveryId !== "number" ||
			typeof compilation["order"]["PROPERTIES"] !== "object"
		) {
			return false;
		}

		//jquery vars
		var $deliveryProperties = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.deliveryProps
		);

		//get properties from last compilation data
		var orderProperties = compilation["order"]["PROPERTIES"]["PROPERTIES"];

		//hide all delivery properties
		application.tools.hideByFlag($deliveryProperties, true);

		//show properties from current delivery
		$.each(orderProperties, function () {
			if (typeof this["RELATION"] === "object" && !$.isEmptyObject(this["RELATION"])) {
				if (
					typeof this["DELIVERY_RELATION"] !== undefined &&
					this["DELIVERY_RELATION"] === "Y"
				) {
					application.tools.hideByFlag(
						$deliveryProperties.filter('[data-property-id="' + this["ID"] + '"]')
					);
				}
			}
		});
	};

	application.commit.basket.deliveryPeriod = function (deliveryId) {
		//check types
		if (
			typeof deliveryId !== "number" ||
			isNaN(deliveryId) ||
			typeof compilation["order"]["DELIVERIES"] !== "object"
		) {
			return false;
		}

		//jquery vars
		var $deliveryPeriod = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.deliveryPeriod
		);
		var $deliveryPeriodDescription = $deliveryPeriod.find(
			"." + classNameCluster.deliveryPeriodDescription
		);

		//other
		var periodDescription =
			compilation["order"]["DELIVERIES"][deliveryId]["PERIOD_DESCRIPTION"];

		//clear
		application.tools.clear($deliveryPeriodDescription);

		//get current delivery info
		if (typeof compilation["order"]["DELIVERIES"][deliveryId] === "object") {
			if (periodDescription != "") {
				$deliveryPeriodDescription.html(periodDescription);
			}
		}

		//set visible/unvisible peroiod container
		application.tools.hideByFlag($deliveryPeriod, periodDescription == "");
	};

	application.commit.basket.deliveryDescription = function (deliveryId) {
		//check types
		if (
			typeof deliveryId !== "number" ||
			isNaN(deliveryId) ||
			typeof compilation["order"]["DELIVERIES"] !== "object"
		) {
			return false;
		}

		//jquery vars
		var $deliveryDescription = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.deliveryDescription
		);
		var $deliveryModulesContainer = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.deliveryModulesContainer
		);
		var $deliveryModulesContainerDesc = $deliveryModulesContainer.find(
			"." + classNameCluster.deliveryModulesContainerDesc
		);

		//other
		var description = compilation["order"]["DELIVERIES"][deliveryId]["DESCRIPTION"];

		//clear
		application.tools.clear($deliveryDescription);
		application.tools.clear($deliveryModulesContainer);

		//append to modules container description block
		$deliveryModulesContainer.append($deliveryModulesContainerDesc);

		//get current delivery info
		if (typeof compilation["order"]["DELIVERIES"][deliveryId] === "object") {
			if (description != "") {
				$deliveryDescription.html(description);
			}
		}

		//set visible/unvisible peroiod container
		application.tools.hideByFlag($deliveryDescription, description == "");
	};

	application.commit.basket.gifts = function (jdata) {
		//jquery vars
		var $giftContainer = $("." + classNameCluster.giftContainer);

		//get & check gift component
		var giftComponent = typeof jdata["gifts"] !== "undefined" ? jdata["gifts"] : "";

		//push gifts component
		$giftContainer.html(giftComponent);
	};

	application.commit.basket.paysystems = function () {
		//check empty
		if (
			typeof compilation["order"]["PAYSYSTEMS"] !== "object" ||
			$.isEmptyObject(compilation["order"]["PAYSYSTEMS"])
		) {
			//for display error message
			compilation["order"]["PAYSYSTEMS"] = {
				0: {
					ID: 0,
					NAME: basketLang["empty-paysystems"]
				}
			};
		}

		//jquery vars
		var $this = $("." + classNameCluster.propActive + " ." + classNameCluster.paysystem);

		//get dropdown container
		var $dropDown = $this.parents("." + classNameCluster.dropDown);
		var $dropDownItems = $dropDown.find("." + classNameCluster.dropDownItems);
		var $dropDownSelected = $dropDown.find("." + classNameCluster.dropDownSelected);
		var $dropDownItemFirst = false;

		//other
		var currentId = parseInt($this.val());
		var selectedId = 0;

		//get paysystems from last compilation data
		var paysystems = compilation["order"]["PAYSYSTEMS"];

		//get paysystems sorted indexes
		var sortPaysystems = application.tools.sortByIndex(paysystems);

		//clear
		application.tools.clear($this);
		application.tools.clear($dropDownItems);

		//each data
		$.each(sortPaysystems, function (currentIndex, nextPaysystemId) {
			//get next paysystem
			var nextPaysystem = paysystems[nextPaysystemId];

			//create next delivery item
			var $paysystemItem = $("<option/>").val(nextPaysystem["ID"]);
			var $dropDownItem = $("<div/>", { class: classNameCluster.dropDownItem }).data(
				"value",
				nextPaysystem["ID"]
			);

			//set title
			$paysystemItem.html(nextPaysystem["NAME"]);
			$dropDownItem.html(nextPaysystem["NAME"]);

			//set selected
			if (currentId == nextPaysystem["ID"]) {
				//set option selected
				$paysystemItem.attr("selected", "selected");

				//set selected in dropdown
				$dropDownItem.addClass(classNameCluster.dropDownActive);

				//change title in dropdown selected item
				$dropDownSelected.html(nextPaysystem["NAME"]);

				//write selected id
				selectedId = nextPaysystem["ID"];
			}

			//save first item
			if ($dropDownItemFirst == false) {
				$dropDownItemFirst = $dropDownItem;
			}

			//push
			$this.append($paysystemItem);
			$dropDownItems.append($dropDownItem);
		});

		//refresh var
		currentId = parseInt($this.val());

		//check selected id
		if (selectedId == 0 && currentId != "") {
			//get delivery from result
			var currentPaysystem = paysystems[currentId];

			//check current paysystem
			if (typeof currentPaysystem != "undefined" && !$.isEmptyObject(currentPaysystem)) {
				//add selected class
				$dropDownItemFirst.addClass(classNameCluster.dropDownActive);

				//set title
				$dropDownSelected.html(currentPaysystem["NAME"]);
			}
		}

		//processing
		application.commit.basket.payProperties();
		application.commit.basket.payInner();
	};

	application.commit.basket.payProperties = function () {
		//check types
		if (typeof compilation["order"]["PROPERTIES"] !== "object") {
			return false;
		}

		//jquery vars
		var $paysystemProperties = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.paysystemProps
		);

		//get properties from last compilation data
		var orderProperties = compilation["order"]["PROPERTIES"]["PROPERTIES"];

		//hide all delivery properties
		application.tools.hideByFlag($paysystemProperties, true);

		//show properties from current delivery
		$.each(orderProperties, function () {
			if (typeof this["RELATION"] === "object" && !$.isEmptyObject(this["RELATION"])) {
				if (
					typeof this["PAYSYSTEM_RELATION"] !== undefined &&
					this["PAYSYSTEM_RELATION"] === "Y"
				) {
					application.tools.hideByFlag(
						$paysystemProperties.filter('[data-property-id="' + this["ID"] + '"]')
					);
				}
			}
		});
	};

	application.commit.basket.payInner = function () {
		//jquery vars
		var $innerPaymentContainer = $("." + classNameCluster.budgetContainer);
		var $innerPayment = $("." + classNameCluster.budget);

		//other
		var flags = {
			container: true
		};

		//check empty
		if (!$.isEmptyObject(compilation["order"]["INNER_PAYMENT"])) {
			//set flag
			flags.container = false;

			//max amount to write off
			if ($.isEmptyObject(compilation["order"]["INNER_PAYMENT"]["RANGE"]["MAX"])) {
				$innerPayment.data("max", compilation["order"]["INNER_PAYMENT"]["RANGE"]["MAX"]);
			}
		}

		//reset
		if (flags.container === true) {
			$innerPayment.removeAttr("checked");
		}

		//hide / show inner payment container
		application.tools.hideByFlag($innerPaymentContainer, flags.container);
	};

	application.commit.basket.minOrderPrice = function () {
		//check empty
		if (typeof compilation["min_order_amount"] !== "undefined") {
			//jquery vars
			var $order = $("." + classNameCluster.order);
			var $fastBuy = $("." + classNameCluster.fastBuy);
			var $goToOrder = $("." + classNameCluster.goToOrder);
			var $minPayment = $("." + classNameCluster.minPayment);

			//hide / show blocks
			application.tools.hideByFlag($order, !compilation["min_order_amount"]);
			application.tools.hideByFlag($fastBuy, !compilation["min_order_amount"]);
			application.tools.hideByFlag($goToOrder, !compilation["min_order_amount"]);
			application.tools.hideByFlag($minPayment, compilation["min_order_amount"]);
		}
	};

	//realtime calculate sum & tools fields
	application.commit.basket.realtime = function () {
		//object map
		var self = {
			fields: {},
			calculate: {}
		};

		//get fields
		self.fields.basket = $("." + classNameCluster.basket);
		self.fields.budget = self.fields.basket.find(
			"." + classNameCluster.propActive + " ." + classNameCluster.budget
		);
		self.fields.delivery = self.fields.basket.find(
			"." + classNameCluster.propActive + " ." + classNameCluster.delivery
		);
		self.fields.basketSum = self.fields.basket.find("." + classNameCluster.basketSum);
		self.fields.deliverySum = self.fields.basket.find("." + classNameCluster.deliverySum);
		self.fields.basketItems = self.fields.basket.find("." + classNameCluster.productItem);
		self.fields.orderSumContainer = self.fields.basket.find("." + classNameCluster.orderSum);
		self.fields.countItemsContainer = self.fields.basket.find(
			"." + classNameCluster.countItems
		);

		//calculate
		self.calculate.amount = application.calculate.getAmountProducts(self.fields.basketItems);
		self.calculate.basketSum = application.calculate.getBasketSum(self.fields.basketItems);
		self.calculate.orderSum = application.calculate.getOrderSum(
			self.fields.basketItems,
			self.fields.delivery,
			self.fields.budget,
			self.calculate.basketSum
		);
		self.calculate.deliverySum = application.calculate.getDeliverySum(self.fields.delivery);

		//push values
		self.fields.countItemsContainer.html(self.calculate.amount);
		self.fields.orderSumContainer.html(
			application.price.format(self.calculate.orderSum, false, false, false, true)
		);
		self.fields.deliverySum.html(
			application.price.format(self.calculate.deliverySum, false, false, false, true)
		);
		self.fields.basketSum.html(
			application.price.format(self.calculate.basketSum, false, false, false, true)
		);
	};

	application.commit.basket.setCompilation = function (jsonData) {
		if (typeof jsonData === "object" && !$.isEmptyObject(jsonData)) {
			if (typeof jsonData["compilation"] === "object" && !$.isEmptyObject(jsonData)) {
				return (compilation = jsonData["compilation"]);
			}
		}

		return (compilation = {});
	};

	//location
	application.commit.location.pushComponent = function () {
		//jquery vars
		var $this = $("." + classNameCluster.propActive + " ." + classNameCluster.locationField);
		var $swichContainer = $this.siblings("." + classNameCluster.locationSwitchContainer);

		//other
		var currentValue = $this.val().toString();
		var sendObject = new FormData();

		//check filling
		if (currentValue == "") {
			return false;
		}

		//push request params
		sendObject.append("actionType", "pushLocation");
		sendObject.append("value", currentValue);
		sendObject.append("siteId", siteId);

		//start loader
		application.tools.launchLoader($this);

		//send data
		application.ajax.sendData(
			ajaxDir + "/ajax.php",
			sendObject,
			"post",
			"json",
			dataProcessing,
			false
		);

		//proccesing data after request
		function dataProcessing(jsonData) {
			//check state
			if (typeof jsonData["component"] == "string" && jsonData["component"] != "") {
				//set flag
				application.flags.location.open = true;

				//push component data
				$swichContainer.html(jsonData["component"]);
			}

			//check errors
			else {
				if (jsonData["error"] === true) {
					console.error(jsonData);
				}
			}

			//remove loader
			application.tools.stopLoader($this);
		}
	};

	//calculate all order sum
	application.calculate.getOrderSum = function (
		$basketItems,
		$deliveryField,
		$budgetSwitch,
		totalSum
	) {
		//check total sum
		if (typeof totalSum == "undefined") {
			totalSum = 0;
		}

		//check delivery
		if (typeof $deliveryField === "object") {
			totalSum += application.calculate.getDeliverySum($deliveryField);
		}

		//check budget
		if (typeof $budgetSwitch === "object") {
			totalSum -= application.calculate.getBudgetMaxSum($budgetSwitch);
		}

		return totalSum >= 0 ? totalSum : 0;
	};

	//calculate products sum
	application.calculate.getBasketSum = function ($basketItems) {
		//var
		var totalSum = 0;

		//check empty
		if (typeof $basketItems === "object") {
			//get price containers
			var $priceFields = $basketItems.find("." + classNameCluster.productPrice);

			//get quantity containers
			var $quantityFields = $basketItems.find("." + classNameCluster.quantityField);

			//calculate
			$priceFields.each(function (currentIndex) {
				//get values
				var currentPrice = parseFloat($(this).data("price"));
				var currentQuantity = parseFloat($quantityFields.eq(currentIndex).val());

				//calculate
				totalSum += currentPrice * currentQuantity;
			});
		}

		return totalSum;
	};

	//calculate number of units products
	application.calculate.getAmountProducts = function ($basketItems, countItems) {
		//check count items
		if (typeof countItems == "undefined") {
			countItems = 0;
		}

		//check empty
		if (typeof $basketItems === "object" && !$.isEmptyObject($basketItems)) {
			//get quantity containers
			var $quantityFields = $basketItems.find("." + classNameCluster.quantityField);

			//calculate
			$quantityFields.each(function () {
				countItems += parseFloat($(this).val());
			});
		}

		return countItems;
	};

	//delivery sum
	application.calculate.getDeliverySum = function ($deliveryField) {
		//check empty
		if (typeof $deliveryField === "object" && !$.isEmptyObject($deliveryField)) {
			var getDeliverySum = parseFloat($deliveryField.find("option:selected").data("price"));
			if (!isNaN(getDeliverySum) && getDeliverySum > 0) {
				return getDeliverySum;
			}
		}

		return 0;
	};

	application.calculate.getBudgetMaxSum = function ($budgetSwitch) {
		//check object
		if (typeof $budgetSwitch === "object" && !$.isEmptyObject($budgetSwitch)) {
			//check state
			if ($budgetSwitch.is(":checked")) {
				//get balance
				var accountBalance = parseFloat($budgetSwitch.data("account-balance"));
				var maxSpend = parseFloat($budgetSwitch.data("max"));

				//check max spend
				if (!isNaN(maxSpend) && maxSpend !== undefined) {
					if (accountBalance > maxSpend) {
						accountBalance = maxSpend;
					}
				}

				//check balance
				if (accountBalance > 0) {
					return accountBalance;
				}
			}
		}

		return 0;
	};

	//push functions

	application.push.serializeFields = function ($orderFields, sendObject) {
		//check send object
		if (typeof sendObject == "undefined") {
			sendObject = {};
		}

		//check transmitted
		if (typeof $orderFields !== "undefined" && !$.isEmptyObject($orderFields)) {
			//collect
			$.each($orderFields, function () {
				//get field
				var $this = $(this);

				//field data
				var field = {
					multiple: $this.prop("multiple"),
					tagName: $this.prop("tagName").toLowerCase(),
					checked: $this.prop("checked"),
					type: $this.attr("type"),
					name: $this.attr("name").toLowerCase(),
					id: $this.data("id"),
					val: $this.val()
				};

				if (field.id === "undefined") {
					console.error("undefined id");
				}

				//processing
				if (field.tagName == "input") {
					//text & checkbox (radio)
					if (
						field.type == "text" ||
						field.type == "date" ||
						(field.type == "number" && field.val != "") ||
						field.type == "checkbox" ||
						(field.type == "radio" && field.checked == true)
					) {
						sendObject.append("properties[" + field.id + "]", field.val);
					}
					//file
					else if (field.type == "file" && field.val != "") {
						//check multi file
						if ($this[0].files.length > 1) {
							$.each($this[0].files, function () {
								sendObject.append("properties[" + field.id + "][]", this);
							});
						} //one
						else {
							sendObject.append("properties[" + field.id + "]", $this[0].files[0]);
						}
					}
				}

				//textarea
				else if (field.tagName == "textarea" && field.val != "") {
					sendObject.append("properties[" + field.id + "]", field.val);
				}

				//select
				else if (field.tagName == "select") {
					//multiselect
					if (field.multiple == true) {
						//get selected options
						var $options = $this.find("option:selected");

						//check count
						if ($options.length > 0) {
							//push values
							$.each($options, function () {
								sendObject.append("properties[" + field.id + "][]", $(this).val());
							});
						}
					}

					//one
					else {
						sendObject.append("properties[" + field.id + "]", field.val);
					}
				}
			});
		}

		return sendObject;
	};

	application.push.fields = function (sendObject) {
		//push request params
		sendObject = application.push.formData(
			sendObject,
			application.pull.getExtraServices(),
			"extraServices"
		);
		sendObject = application.push.formData(
			sendObject,
			application.pull.getPersonTypeId(),
			"personTypeId"
		);
		sendObject = application.push.formData(
			sendObject,
			application.pull.getInnerPayment(),
			"innerPayment"
		);
		sendObject = application.push.formData(
			sendObject,
			application.pull.getPaysystemId(),
			"paysystemId"
		);
		sendObject = application.push.formData(
			sendObject,
			application.pull.getDeliveryId(),
			"deliveryId"
		);
		sendObject = application.push.formData(
			sendObject,
			application.pull.getLocationId(),
			"locationId"
		);
		sendObject = application.push.formData(sendObject, application.pull.getStoreId(), "store");

		return sendObject;
	};

	application.push.params = function (sendObject) {
		//append component params
		if (typeof basketParams === "object") {
			sendObject = application.push.formData(sendObject, basketParams, "params");
		}

		return sendObject;
	};

	application.push.orderComment = function (sendObject) {
		//get comment field
		var $comment = $("." + classNameCluster.propActive + " ." + classNameCluster.orderComment);

		//check object
		if (typeof $comment === "object" && !$.isEmptyObject($comment)) {
			var commentValue = $comment.val();

			//check empty
			if (commentValue != undefined && commentValue != "") {
				sendObject = application.push.formData(
					sendObject,
					commentValue.toString(),
					"comment"
				);
			}
		}

		return sendObject;
	};

	application.push.formData = function (sendObject, values, name) {
		//sendObject(formData)
		//multi
		if (typeof values == "object") {
			//each values
			for (var index in values) {
				//recursion
				if (typeof values[index] == "object") {
					application.push.formData(sendObject, values[index], name + "[" + index + "]");
				}

				//one
				else {
					sendObject.append(name + "[" + index + "]", values[index]);
				}
			}
		}

		//one
		else {
			sendObject.append(name, values);
		}

		return sendObject;
	};

	//pull functions

	application.pull.getOrderFields = function () {
		//main data
		var $orderFields = $([]);

		//jquery vars
		var $self = $("." + classNameCluster.basket);

		//write
		$orderFields.push.apply(
			$orderFields,
			$self.find(
				"." +
					classNameCluster.propActive +
					" ." +
					classNameCluster.propertyItem +
					':not(".' +
					classNameCluster.propHidden +
					'") input'
			)
		);
		$orderFields.push.apply(
			$orderFields,
			$self.find(
				"." +
					classNameCluster.propActive +
					" ." +
					classNameCluster.propertyItem +
					':not(".' +
					classNameCluster.propHidden +
					'") select'
			)
		);
		$orderFields.push.apply(
			$orderFields,
			$self.find(
				"." +
					classNameCluster.propActive +
					" ." +
					classNameCluster.propertyItem +
					':not(".' +
					classNameCluster.propHidden +
					'") textarea'
			)
		);

		return $orderFields;
	};

	application.pull.getLocationId = function () {
		//jquery vars
		var $locationField = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.locationField
		);

		//other
		var locationId = parseFloat($locationField.data("location"));

		//check empty
		if (!isNaN(locationId)) {
			return locationId;
		}

		return 0;
	};

	application.pull.getStoreId = function () {
		return application.tools.getFieldValue(
			"." +
				classNameCluster.propActive +
				" ." +
				classNameCluster.storesContainer +
				':not(".' +
				classNameCluster.propHidden +
				'") .' +
				classNameCluster.storesSwitch
		);
	};

	application.pull.getInnerPayment = function () {
		return application.tools.getFieldValue(
			"." +
				classNameCluster.propActive +
				" ." +
				classNameCluster.budgetContainer +
				':not(".' +
				classNameCluster.propHidden +
				'") .' +
				classNameCluster.budget
		);
	};

	application.pull.getPaysystemId = function () {
		return application.tools.getFieldValue(
			"." + classNameCluster.propActive + " ." + classNameCluster.paysystem
		);
	};

	application.pull.getDeliveryId = function () {
		return application.tools.getFieldValue(
			"." + classNameCluster.propActive + " ." + classNameCluster.delivery
		);
	};

	application.pull.getPersonTypeId = function () {
		return application.tools.getFieldValue("." + classNameCluster.personType);
	};

	application.pull.getExtraServices = function () {
		//jquery vars
		var $serviceFieldsContainers = $(
			"." +
				classNameCluster.propActive +
				" ." +
				classNameCluster.extraServiceItemContainer +
				':not(".' +
				classNameCluster.propHidden +
				'")'
		);
		var $serviceFields = $serviceFieldsContainers.find("." + classNameCluster.extraService);
		var $deliveryField = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.delivery
		);

		//other
		var extraServices = [];
		var currentDeliveryId = application.pull.getDeliveryId();
		var deliverylastId = application.pull.getLastDeliveryId();

		//check empty
		if (typeof $serviceFields === "object" && $serviceFields.length > 0) {
			//get values
			$.each($serviceFields, function () {
				//get jquery object
				var $item = $(this);

				//get field data
				var field = {
					value: application.tools.getFieldValue($item, false),
					id: $item.data("id")
				};

				//push
				if (field.value != "") {
					extraServices.push(field);
				}
			});
		}

		return extraServices;
	};

	application.pull.getLastDeliveryId = function () {
		//check filling
		if (deliveryId == 0) {
			//jquery vars
			var $deliveryField = $(
				"." + classNameCluster.propActive + " ." + classNameCluster.delivery
			);

			//get default delivery id
			if (typeof $deliveryField === "object" && !$.isEmptyObject($deliveryField)) {
				deliveryId = parseInt($deliveryField.data("default"));
			}
		}

		return deliveryId;
	};

	//check functions
	application.check.required = function ($orderFields) {
		//vars
		var control = true;

		//check transmitted
		if (typeof $orderFields === "undefined" && !$.isEmptyObject($orderFields)) {
			var $orderFields = application.pull.getOrderFields();
		}

		//check required
		$.each($orderFields, function () {
			//get jquery object
			var $this = $(this);

			//check data
			if ($this.data("required") == "Y") {
				//check filling
				if (!application.check.filling($this)) {
					//add error marker
					application.tools.addError($this);

					//check control / scroll
					if (control === true) {
						control = application.tools.scrollTo($this);
					}
				}
			}
		});

		return control;
	};

	//check functions
	application.check.filling = function ($field) {
		//check transmitted
		if (typeof $field !== "undefined" && !$.isEmptyObject($field)) {
			//field data
			var field = {
				tagName: $field.prop("tagName").toLowerCase(),
				checked: $field.prop("checked"),
				mail: $field.data("mail"),
				type: $field.attr("type"),
				name: $field.attr("name"),
				val: $field.val()
			};

			//check email
			if (field.mail != "undefined" && field.mail == "Y") {
				if (!application.tools.validateEmail(field.val)) {
					return false;
				}
			}

			//check filling
			if (
				(field.type == "checkbox" && field.checked == true) ||
				(field.tagName == "textarea" && field.val != "") ||
				(field.type == "number" && field.val != "") ||
				(field.type == "text" && field.val != "") ||
				(field.type == "date" && field.val != "") ||
				(field.type == "file" && field.val != "")
			) {
				return true;
			}
		}

		return false;
	};

	//price functions
	application.price.format = function (price, decimals, decPoint, thousands, langFormat) {
		//set default values
		decimals = typeof decimals != "undefined" ? decimals : 2;
		decPoint = typeof decPoint != "undefined" ? decPoint : ".";
		thousands = typeof thousands != "undefined" ? thousands : " ";
		langFormat = typeof langFormat != "undefined" ? langFormat : false;

		//check empty
		if (typeof price !== "undefined" && price !== null && price !== "") {
			//check global vars
			if (decPoint === false && siteCurrency["DEC_POINT"] !== undefined) {
				decPoint = siteCurrency["DEC_POINT"];
			}

			if (decimals === false && siteCurrency["DECIMALS"] !== undefined) {
				decimals = siteCurrency["DECIMALS"];
			}

			if (thousands === false && siteCurrency["THOUSANDS_VARIANT"] !== undefined) {
				thousands = application.price.thousandsVariant(siteCurrency["THOUSANDS_VARIANT"]);
			}

			//vars
			var fsplit = price.toString().split(".");
			var whole = fsplit[0].replace(/(\d)(?=(\d\d\d)+([^\d]|$))/g, "$1" + thousands);
			var rest = "";

			//check rest
			if (fsplit[1] !== undefined && decimals > 0) {
				rest = decPoint + fsplit[1].substr(0, decimals);
			}

			if (langFormat === true) {
				return application.price.langFormat(whole + rest);
			} else {
				return whole + rest;
			}
		}

		return false;
	};

	application.price.langFormat = function (price) {
		return siteCurrency["FORMAT_STRING"].toString().replace("#", price);
	};

	application.price.thousandsVariant = function (thousands) {
		thousands = typeof thousands != "undefined" ? thousands : "N";
		return siteCurrency["SEPARATORS"][thousands];
	};

	//ajax functions
	application.ajax.sendData = function (
		url,
		data,
		type,
		dataType,
		success,
		forEngageloader,
		contentType
	) {
		//set default values
		forEngageloader = typeof forEngageloader != "undefined" ? forEngageloader : false;
		contentType = typeof contentType != "undefined" ? contentType : false;

		//send request
		$.ajax({
			contentType: contentType,
			dataType: dataType,
			processData: false,
			cache: false,
			url: url,
			type: type,
			data: data,
			success: success,
			beforeSend: function (jqXHR, settings) {
				application.tools.launchLoader(forEngageloader);
			},
			complete: function (jqXHR, textStatus) {
				application.tools.stopLoader(forEngageloader);
			},
			error: function (jqXHR, textStatus, errorThrown) {
				console.error({ httpResponse: jqXHR.responseText, status: jqXHR.statusText });
				console.error(jqXHR, textStatus, errorThrown);
			}
		});
	};

	//event functions
	application.event.trigger = function (eventName, eventData) {
		//set default values
		eventName = typeof eventName != "undefined" ? eventName : "";
		eventData = typeof eventData != "undefined" ? eventData : [];

		//check name
		if (eventName != "") {
			$(document).trigger("DwBasket." + eventName, eventData);
		}
	};

	//tools functions
	application.tools.resetLocation = function (event) {
		//jquery vars
		var $locationField = $(
			"." + classNameCluster.propActive + " ." + classNameCluster.locationField
		);
		var $locationContainer = $locationField.siblings(
			"." + classNameCluster.locationSwitchContainer
		);

		//other
		var currentValue = $locationField.val().toString();
		var currentId = $locationField.data("location").toString();

		//close dropdown
		application.tools.closeLocationDropdown($locationContainer);

		//check filling
		if (currentValue == "" || currentId == "") {
			//get last values
			var lastId = $locationField.data("last-id");
			var lastValue = $locationField.data("last-value");

			//white last values
			$locationField.data("location", lastId);
			$locationField.val(lastValue);
		}
	};

	application.tools.closeLocationDropdown = function ($container) {
		//set default
		$container = typeof $container != "undefined" ? $container : null;

		//check object
		if (typeof $container === "object") {
			//check flag
			if (application.flags.location.open === true) {
				//set flag
				application.flags.location.open = false;

				//clear
				application.tools.clear($container);
			}
		}
	};

	application.tools.getFieldValue = function (item, onlyChecked) {
		//set default
		onlyChecked = typeof onlyChecked != "undefined" ? onlyChecked : true;

		//check empty
		if (
			(typeof item === "string" && item !== "") ||
			(typeof item === "object" && item.length > 0)
		) {
			//jquery vars
			var $field = typeof item === "object" ? item : $(item);

			//check
			if (typeof $field === "object" && $field.length > 0) {
				//other
				var field = {
					tagName: $field.prop("tagName").toLowerCase(),
					checked: $field.prop("checked"),
					type: $field.attr("type"),
					val: $field.val()
				};

				//select
				if (
					field.tagName == "select" &&
					field.val !== undefined &&
					field.val !== null &&
					field.val !== ""
				) {
					return field.val.toString();
				}

				//checkbox / radio
				else if (
					field.tagName == "input" &&
					typeof field.type !== undefined &&
					(field.type == "checkbox" || field.type == "radio")
				) {
					if (field.checked == true) {
						return field.val.toString();
					} else if (onlyChecked === false) {
						return "N";
					}
				}

				//text / number
				else if (
					field.tagName == "input" &&
					typeof field.type !== undefined &&
					(field.type == "text" || field.type == "number")
				) {
					if (field.val != "") {
						return field.val.toString();
					}
				}
			}
		}

		return 0;
	};

	application.tools.startTimer = function (selector, readySelector, seconds) {
		//set default
		readySelector = typeof readySelector != "undefined" ? readySelector : false;
		seconds = typeof seconds != "undefined" ? seconds : 60;

		//check empty
		if (typeof selector != "undefined" && selector != "") {
			//jquery vars
			var $timer = $(selector);

			//show timer
			application.tools.hideByFlag($timer, false);

			//other
			var dates = {
				current: new Date(),
				end: new Date(Date.now() + seconds * 1000)
			};

			//is timer discovered
			if (typeof $timer == "object" && !$.isEmptyObject($timer)) {
				//check second settings
				if (typeof seconds == "number" && seconds > 0) {
					//indication
					var timerId = setInterval(function () {
						//set current date
						dates.current = new Date();

						//change html
						if (dates.end.getTime() > dates.current.getTime()) {
							//calculate
							dates.remaining = new Date(
								dates.end.getTime() - dates.current.getTime()
							);
							dates.minutes = Math.floor((dates.remaining / 1000 / 60) % 60);
							dates.seconds = Math.floor((dates.remaining / 1000) % 60);

							//push
							$timer.text(dates.minutes + ":" + dates.seconds);
						}

						//off
						else {
							//hide timer
							application.tools.hideByFlag($timer, true);

							//block indication
							clearInterval(timerId);

							//set ready
							if (readySelector != false) {
								$(readySelector).addClass("ready");
							}
						}
					}, 900);
				}
			}
		}
	};

	application.tools.clear = function ($item) {
		if (typeof $item === "object") {
			return $item.html("");
		}
	};

	application.tools.hideByFlag = function ($item, flag, className) {
		//set default
		flag = typeof flag != "undefined" ? flag : false;
		className = typeof className != "undefined" ? className : "hidden";

		//check object
		if (typeof $item === "object") {
			return flag === true ? $item.addClass(className) : $item.removeClass(className);
		}
	};

	application.tools.launchLoader = function ($item, loaderClassName) {
		//set default
		loaderClassName = typeof loaderClassName != "undefined" ? loaderClassName : "loading";

		//check transmitted item
		if (typeof $item === "object") {
			return $item.addClass(loaderClassName);
		}
	};

	application.tools.stopLoader = function ($item, loaderClassName) {
		//set default
		loaderClassName = typeof loaderClassName != "undefined" ? loaderClassName : "loading";

		//check transmitted item
		if (typeof $item === "object") {
			return $item.removeClass(loaderClassName);
		}
	};

	//errors
	application.tools.addError = function ($item, errorClassName) {
		//set default
		errorClassName =
			typeof errorClassName != "undefined" ? errorClassName : classNameCluster.error;

		//check transmitted item
		if (typeof $item === "object") {
			return $item.addClass(errorClassName);
		}
	};

	application.tools.removeError = function ($item, errorClassName) {
		//set default
		errorClassName =
			typeof errorClassName != "undefined" ? errorClassName : classNameCluster.error;

		//check transmitted item
		if (typeof $item === "object") {
			return $item.removeClass(errorClassName);
		}
	};

	application.tools.addParamToUrl = function (params) {
		//set default
		params = typeof params != "undefined" ? params : {};

		//get current url
		var url = window.location.href;
		var separator = url.indexOf("?") === -1 ? "?" : "&";

		//check empty
		if (typeof params === "object" && !$.isEmptyObject(params)) {
			//append params
			$.each(params, function (param, value) {
				url = url + separator + param + "=" + value;
				separator = url.indexOf("?") === -1 ? "?" : "&";
			});
		}

		return url;
	};

	application.tools.validateEmail = function (email) {
		//vars
		var regularEx =
			/[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;

		//check empty
		if (typeof email === "string" && email != "") {
			return regularEx.test(String(email).toLowerCase());
		}

		return false;
	};

	application.tools.scrollTo = function ($item) {
		if (typeof $item === "object") {
			$([document.documentElement, document.body]).animate(
				{
					scrollTop: $item.offset().top - 300
				},
				600
			);
		}

		return false;
	};

	application.tools.pushAjaxErrors = function (errorData, errorId) {
		//set default
		errorData = typeof errorData != "undefined" ? errorData : {};
		errorId = typeof errorId != "undefined" ? errorId : "orderErrors";

		//check empty
		if (typeof errorData === "object" && !$.isEmptyObject(errorData)) {
			//jquery vars
			var $errorWindow = $("." + errorIds[errorId]);
			var $windowMessage = $errorWindow.find("." + classNameCluster.errorWindowMessage);

			//confirm
			if (!$.isEmptyObject($errorWindow) && !$.isEmptyObject($windowMessage)) {
				//push errors
				$windowMessage.html(readError(errorData));

				//show
				application.tools.openWindow(errorIds[errorId]);
			}
		}

		//recursive
		function readError(data, concated) {
			//set default
			concated = typeof concated != "undefined" ? concated : "";

			//vars
			var delimiter = " &bull; ";

			//one
			if (typeof data == "string" && data != "") {
				concated += (concated != "" ? delimiter : "") + data;
			}

			//multi
			else if (typeof data == "object") {
				$.each(data, function () {
					concated = readError(this, concated);
				});
			}

			return concated;
		}

		return false;
	};

	application.tools.sortByIndex = function (data) {
		//vars
		var sortedItems = {};

		//check data
		if (typeof data != "undefined" && !$.isEmptyObject(data)) {
			$.each(data, function (currentIndex, nextDataItem) {
				if (typeof nextDataItem["INDEX"] != "undefined") {
					sortedItems[nextDataItem["INDEX"]] = nextDataItem["ID"];
				}
			});
		}

		return sortedItems;
	};

	//openWindow
	application.tools.openWindow = function (errorClass) {
		if (typeof errorClass != "undefined" && errorClass != "") {
			var $errorWindow = $("." + errorClass);
			if (typeof $errorWindow === "object" && !$.isEmptyObject($errorWindow)) {
				$errorWindow.css({ display: "block" });
			}
		}
	};

	application.tools.closeWindow = function ($errorWindow) {
		if (typeof $errorWindow === "object" && !$.isEmptyObject($errorWindow)) {
			$errorWindow.css({ display: "none" });
		}
	};

	application.tools.getNamesFromCluster = function () {
		return classNameCluster;
	};

	application.tools.closeDropDown = function () {
		//check opened items
		if (application.flags.dropDown.open === true) {
			//hide
			$("." + classNameCluster.dropDownItems).removeClass(classNameCluster.dropDownOpened);

			//set opened flag
			application.flags.dropDown.open = false;
		}
	};

	//write to global var
	DwBasket = application;

	//jquery binding functions
	//you must edit constants for fast customization

	//click
	$(document).on(
		"click",
		"." + classNameCluster.order + " ." + classNameCluster.dropDownSelected,
		application.binding.click.openDropDown
	);
	$(document).on(
		"click",
		"." + classNameCluster.order + " ." + classNameCluster.dropDownItem,
		application.binding.click.selectDropDown
	);
	$(document).on(
		"click",
		"." + classNameCluster.order + " ." + classNameCluster.dropDown,
		application.binding.click.stop
	);
	$(document).on(
		"click",
		"." + classNameCluster.errorWindowClose,
		application.binding.click.closeErrorWindow
	);
	$(document).on(
		"click",
		"." + classNameCluster.locationSwitchLink,
		application.binding.click.locationLink
	);
	$(document).on(
		"click",
		"." + classNameCluster.locationSwitchContainer,
		application.binding.click.stop
	);
	$(document).on(
		"click",
		"." + classNameCluster.confirmReplay,
		application.binding.click.confirmReplay
	);
	$(document).on(
		"click",
		"." + classNameCluster.confirmSend,
		application.binding.click.confirmSend
	);
	$(document).on(
		"click",
		"." + classNameCluster.couponButton,
		application.binding.click.setCoupon
	);
	$(document).on("click", "." + classNameCluster.remove, application.binding.click.deleteItem);
	$(document).on("click", "." + classNameCluster.minus, application.binding.click.quantityMinus);
	$(document).on("click", "." + classNameCluster.plus, application.binding.click.quantityPlus);
	$(document).on("click", "." + classNameCluster.orderMake, application.binding.click.orderMake);
	$(document).on("click", "." + classNameCluster.orderMove, application.binding.click.orderMove);
	$(document).on("click", "." + classNameCluster.clearAll, application.binding.click.clearAll);
	$(document).on("click", "", application.binding.click.closeout);

	//submit
	$(document).on(
		"submit",
		"." + classNameCluster.confirmForm,
		application.binding.submit.confirmForm
	);

	//keyup
	$(document).on(
		"keyup",
		"." + classNameCluster.locationField,
		application.binding.keyup.location
	);

	//input
	$(document).on("input", "." + classNameCluster.confirmField, application.binding.input.confirm);

	//keypress
	$(document).on(
		"keypress",
		"." + classNameCluster.quantityField,
		application.binding.keypress.quantity
	);
	$(document).on(
		"keypress",
		"." + classNameCluster.extraService,
		application.binding.keypress.quantity
	);

	//focus
	$(document).on(
		"focus",
		"." + classNameCluster.locationField,
		application.binding.focus.location
	);
	$(document).on("focus", "." + classNameCluster.couponField, application.binding.focus.coupon);

	//focus
	$(document).on("blur", "." + classNameCluster.locationField, application.binding.blur.location);

	//change
	$(document).on(
		"change",
		"." + classNameCluster.extraService,
		application.binding.change.extraService
	);
	$(document).on(
		"change",
		"." + classNameCluster.quantityField,
		application.binding.change.quantity
	);
	$(document).on(
		"change",
		"." + classNameCluster.paysystem,
		application.binding.change.paysystem
	);
	$(document).on("change", "." + classNameCluster.personType, application.binding.change.person);
	$(document).on("change", "." + classNameCluster.delivery, application.binding.change.delivery);
	$(document).on("change", "." + classNameCluster.budget, application.binding.change.budget);
});
