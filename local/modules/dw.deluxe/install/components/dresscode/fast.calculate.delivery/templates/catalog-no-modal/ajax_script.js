$(function () {
	//jquery vars
	var $paysystems = $(".detail-delivery-paysystems");
	var $paysystemsContainer = $paysystems.find(".detail-delivery-paysystems-container");
	var $deliveryItems = $(".delivery-items-container");
	var $window = $(window);

	//other
	var minResolution = 1366,
		waitDeliveryRequest = false,
		lastResolution = 0;

	//get browser viewport
	var getViewport = function () {
		//vars
		var viewportwidth;

		//(mozilla/netscape/opera/IE7)
		if (typeof window.innerWidth != "undefined") {
			viewportwidth = window.innerWidth;
		}

		//IE6
		else if (
			typeof document.documentElement != "undefined" &&
			typeof document.documentElement.clientWidth != "undefined" &&
			document.documentElement.clientWidth != 0
		) {
			viewportwidth = document.documentElement.clientWidth;
		}

		//older versions of IE
		else {
			viewportwidth = document.getElementsByTagName("body")[0].clientWidth;
		}

		return viewportwidth;
	};

	//functions
	var showDesciption = function (event) {
		//jquery vars
		var $this = $(this);
		var $parentItem = $this.parents(".delivery-item");
		var $description = $parentItem.find(".delivery-item-description");

		//show
		$description.toggle(100);

		//scroll event function
		payControl();

		//block actions
		return event.preventDefault();
	};

	var showDeliveries = function (event) {
		//jquery vars
		var $this = $(this),
			$deliveryGroup = $this.parents(".delivery-group-item");

		//get items
		$deliveryItems =
			$deliveryGroup.length > 0
				? $deliveryGroup.find(".delivery-item.limit")
				: $(".delivery-item.limit");

		//other
		var stateText = $this.data("state-text");

		//display / hide
		$deliveryItems.toggleClass("disabled");

		//button label
		$this.data("state-text", $this.text()).text(stateText);

		//reset jquery cached vars
		resetVars();

		//scroll event function
		payControl();

		//block actions
		return event.preventDefault();
	};

	var payControl = function () {
		//check exist paysystems block
		if (typeof $paysystems !== "object" || $paysystems.length <= 0) {
			return false;
		}

		//get window width
		var windowInnerWidth = (lastResolution = getViewport());

		//check resolution
		if (
			windowInnerWidth >= minResolution &&
			(windowInnerWidth > 1800 || windowInnerWidth < 1520)
		) {
			//get current scroll position
			var scrollTop = $(window).scrollTop();
			var paysystemsPosition = $paysystems.offset().top;
			var itemsHeight = $deliveryItems.innerHeight();
			var paysystemsHeight = $paysystemsContainer.innerHeight();
			var startPosition = paysystemsPosition + itemsHeight - paysystemsHeight;

			//check start
			if (scrollTop > paysystemsPosition && itemsHeight > paysystemsHeight) {
				//start
				if (startPosition >= scrollTop) {
					fix($paysystemsContainer);
					setWidth($paysystemsContainer, $paysystems.width());
				}

				//end
				else {
					ended($paysystemsContainer);
				}
			}

			//beyond
			else {
				clearFix($paysystemsContainer);
			}
		}

		//beyond
		else {
			clearFix($paysystemsContainer);
		}

		//tools functions
		function fix($element) {
			$element.removeClass("ended").addClass("fixed");
		}

		function clearFix($element) {
			$element.removeClass("fixed").removeClass("ended").removeAttr("style");
		}

		function ended($element) {
			$element.removeClass("fixed").addClass("ended");
		}

		function setWidth($element, width) {
			$element.css("width", width);
		}
	};

	var changeGroupItems = function (event) {
		//jquery vars
		var $this = $(this),
			$deliveryGroups = $(".delivery-group-item"),
			$deliveryTabItems = $(".deliveries-tab-item");

		//other
		var groupId = $this.data("group-id");

		//hide groups
		$deliveryGroups.removeClass("active");
		$deliveryTabItems.removeClass("selected");

		//set active
		$deliveryGroups.filter('[data-group-id="' + groupId + '"]').addClass("active");

		//set selected
		$this.parents(".deliveries-tab-item").addClass("selected");

		//block actions
		return event.preventDefault();
	};

	var addProduct = function (event) {
		//check ajax dir
		if (typeof deliveryAjaxDir == "undefined") {
			return console.error("delivery ajax dir not defined");
		}

		//jquery vars
		var $this = $(this);

		//other
		var productId = $this.data("product-id");
		var deliveryId = $this.data("delivery-id");
		var requestUrl = deliveryAjaxDir + "/ajax.php";

		//get site id
		var siteDir = typeof SITE_DIR != "undefined" ? SITE_DIR : "/";

		//check product id
		if (productId == "") {
			return console.error("product id not defined");
		}

		//add loader
		$this.addClass("loading");

		//request
		$.ajax({
			url: requestUrl,
			type: "POST",
			data: {
				actionType: "addProduct",
				productId: productId
			},
			success: afterRequest,
			dataType: "json"
		});

		function afterRequest(jsonData) {
			//check answer
			if (!$.isEmptyObject(jsonData)) {
				//check success
				if (typeof jsonData.success != "undefined" && jsonData.success == true) {
					return (window.location.href =
						siteDir + "personal/cart/?deliveryId=" + deliveryId);
				} else {
					//push errors
					console.error(jsonData.errors);
				}
			}

			//push error
			else {
				console.error("add product error (empty answer)");
			}

			//clear loader
			$this.removeClass("loading");
		}

		//block actions
		return event.preventDefault();
	};

	var resetVars = function () {
		//jquery vars
		$paysystems = $(".detail-delivery-paysystems");
		$paysystemsContainer = $paysystems.find(".detail-delivery-paysystems-container");
		$deliveryItems = $(".delivery-items-container");
		$window = $(window);
	};

	//global jquery functions
	$.extend({
		//get delivery component (ajax)
		getDeliveryComponent: function (
			productId,
			productQuantity,
			productAvailable,
			deffMode = "N"
		) {
			//jquery vars
			$ajaxContainer = $(".fast-deliveries-container");

			//other
			var requestUrl = deliveryAjaxDir + "/ajax.php";

			//request object
			var requestData = {
				actionType: "getDeliveryComponent",
				productAvailable: productAvailable,
				productQuantity: productQuantity,
				defferedMode: deffMode,
				productId: productId
			};

			//append buttons label
			if (typeof deliveryParams["GROUP_BUTTONS_LABELS"] != "undefined") {
				requestData.serviceButtons = deliveryParams["GROUP_BUTTONS_LABELS"];
			}

			//append display images params
			if (typeof deliveryParams["SHOW_DELIVERY_IMAGES"] != "undefined") {
				requestData.serviceImages = deliveryParams["SHOW_DELIVERY_IMAGES"];
			}

			//add loader
			$ajaxContainer.addClass("loading");

			//request
			$.ajax({
				url: requestUrl,
				type: "POST",
				data: requestData,
				dataType: "html",
				complete: afterRequest
			});

			function afterRequest(componentResult) {
				//check answer
				if (
					typeof componentResult == "object" &&
					typeof componentResult.responseText != "undefined"
				) {
					//push result
					if (componentResult.responseText != "") {
						$ajaxContainer.html(componentResult.responseText);
					}

					//push error
					else {
						console.error("error, empty response; get delivery component");
					}
				}

				//push error
				else {
					console.error("error, component result is undefined; get delivery component");
				}

				//clear loader
				$ajaxContainer.removeClass("loading");

				//reset jquery cached vars
				return resetVars();
			}
		}
	});

	var scrollHandler = function () {
		//get window width
		var windowInnerWidth = (lastResolution = getViewport());

		//check resolution
		if (
			windowInnerWidth >= minResolution &&
			(windowInnerWidth > 1800 || windowInnerWidth < 1520)
		) {
			$(window).on("scroll.delivery", payControl);
		}

		//unbind
		else {
			$(window).off("scroll.delivery", payControl);
		}
	};

	//binds
	$(document).on("click", ".deliveries-tab-switcher", changeGroupItems);
	$(document).on("click", ".delivery-item-question", showDesciption);
	$(document).on("click", ".show-all-deliveries", showDeliveries);
	$(document).on("click", ".delivery-item-buy", addProduct);

	//bind scroll events
	scrollHandler();

	//resize control
	$(window).on("resize", function () {
		//conditions for mobile
		if ($(window).innerWidth() != lastResolution) {
			resetVars();
			scrollHandler();
			payControl();
		}
	});
});
