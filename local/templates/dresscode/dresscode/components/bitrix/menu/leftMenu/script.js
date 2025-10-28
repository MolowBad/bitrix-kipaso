//global vars
var __menuTimeoutID;

$(window).on("ready", function (event) {
	//jquery vars
	var $menu = $("#leftMenu");
	var $menuItems = $menu.children(".eChild");

	//other
	var viewport = 0;

	//functions container
	var events = {
		click: {}
	};
	var tools = {};

	//get browser viewport
	tools.getViewport = function () {
		//(mozilla/netscape/opera/IE7)
		if (typeof window.innerWidth != "undefined") {
			viewport = window.innerWidth;
		}

		//IE6
		else if (
			typeof document.documentElement != "undefined" &&
			typeof document.documentElement.clientWidth != "undefined" &&
			document.documentElement.clientWidth != 0
		) {
			viewport = document.documentElement.clientWidth;
		}

		//older versions of IE
		else {
			viewport = document.getElementsByTagName("body")[0].clientWidth;
		}

		return viewport;
	};

	//events functions
	events.click.open = function (event) {
		//check viewport
		if (tools.getViewport() <= 1024) {
			//jquery vars
			var $this = $(this);
			var $container = $this.parents("li");

			//other
			var state = $this.data("state");

			//check state
			if (typeof state == "undefined" || state === false) {
				//open
				$container.addClass("opened");

				//set state
				$this.data("state", true);

				//block actions
				return event.preventDefault();
			}
		}
	};

	events.click.close = function (event) {
		//check viewport
		if (tools.getViewport() <= 1024) {
			//jquery vars
			var $this = $(this);
			var $container = $this.parents(".opened");

			//close parent
			$container.removeClass("opened");

			//close by tree
			$container.find(".opened").removeClass("opened");

			//set state
			$container.find(".menuLink").data("state", false);

			//block actions
			event.preventDefault();
			event.stopimmediatepropagation();
		}
	};

	events.mouseover = function () {
		//check viewport
		if (viewport > 1024) {
			//jquery vars
			var $this = $(this);

			//display drop menu
			$menuItems.removeClass("activeDrop").find(".drop").hide();
			$this.addClass("activeDrop").find(".drop").css("display", "table");

			//
			return clearTimeout(__menuTimeoutID);
		}

		//clear
		else {
			$menuItems.removeClass("activeDrop");
			$menu.find(".drop").removeAttr("style");
		}
	};

	events.mouseout = function () {
		//check viewport
		if (viewport > 1024) {
			//jquery vars
			var $this = $(this);

			//hide drop menu
			__menuTimeoutID = setTimeout(function () {
				$this.removeClass("activeDrop").find(".drop").hide();
			}, 500);
		}

		//clear
		else {
			$menuItems.removeClass("activeDrop");
			$menu.find(".drop").removeAttr("style");
		}
	};

	//set start viewport
	tools.getViewport();

	//binds
	$(document).on("click", "#leftMenu .nested .back", events.click.close);
	$(document).on("click", "#leftMenu .nested > a", events.click.open);

	//mouse
	$(document).on("mouseover", "#leftMenu .eChild", events.mouseover);
	$(document).on("mouseout", "#leftMenu .eChild", events.mouseout);

	//resize control
	$(window).on("resize", tools.getViewport);
});
