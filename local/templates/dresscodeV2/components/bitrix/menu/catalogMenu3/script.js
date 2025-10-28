//global vars
var menuTimeouts = { main: false, section: false, firstOpen: false };

//check ready
$(document).on("ready", function (event) {
	//jquery vars
	var $mainMenu = $("#mainMenu");
	var $sectionMenu = $("#menuCatalogSection");
	var $mainMenuItems = $mainMenu.children(".eChild");
	var $sectionMenuItems = $sectionMenu.children(".menuSection");

	//other
	var viewport = 0;

	//functions container
	var events = {
		click: {},
		mouseleave: {},
		mouseenter: {}
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

	// slice menu func
	tools.sliceMainMenu = function (resize = true) {
		//check window resize
		if (resize == true) {
			//clear removed items
			$mainMenu.find(".removed").each(function (i, nextElement) {
				//jquery vars
				var $nextElement = $(nextElement);

				//move item
				$mainMenu.append($nextElement.removeClass("removed"));
			});

			$mainMenu.find(".removedItemsLink").remove();
		}

		//other
		var visibleMenuWidth = $mainMenu.width() - 100;
		var totalSumMenuWidth = 0;

		//check desktop
		if (viewport > 1024) {
			$mainMenuItems.each(function (i, nextElement) {
				//jquery vars
				var $nextElement = $(nextElement);

				//other
				totalSumMenuWidth += $nextElement.outerWidth(true);

				//hide not fit items
				if (totalSumMenuWidth > visibleMenuWidth) {
					$nextElement.addClass("removed");
				}
			});

			//get hidden items
			var $removedItems = $mainMenu.find(".removed");

			//if exist hidden items
			if ($removedItems.length > 0) {
				//jquery vars
				var $removedItemsList = $("<ul/>").addClass("removedItemsList");
				var $removedItemsLink = $("<li/>").addClass("removedItemsLink").append($("<a/>"));

				//move hidden items to drop menu container
				$removedItems.each(function (i, nextElement) {
					$removedItemsList.append($(nextElement));
				});

				//join drop link to main menu
				$mainMenu.append($removedItemsLink.append($removedItemsList));

				//set drop menu position
				$removedItemsList.css({
					left: $removedItemsLink.offset().left + "px"
				});
			}
		}
	};

	tools.clearAttributes = function () {
		//check mobile
		if (viewport <= 1024) {
			//clear styles
			$mainMenu.find(".drop").removeAttr("style");
		} else {
			$mainMenu.removeAttr("style");
		}
	};

	//events functions
	events.click.openNested = function (event) {
		//check viewport
		if (tools.getViewport() <= 1024) {
			//jquery vars
			var $this = $(this);

			//other
			var state = $this.data("state");

			//check state
			if (typeof state == "undefined" || state === false) {
				//open & set state
				$this.data("state", true).addClass("opened");

				//block actions
				return event.preventDefault();
			}
		}
	};

	events.click.closeNested = function (event) {
		//check viewport
		if (tools.getViewport() <= 1024) {
			//jquery vars
			var $this = $(this);
			var $container = $this.parents(".opened");

			//set state
			$container.data("state", false).removeClass("opened");

			//close & close by tree
			$container.find(".opened").removeClass("opened").data("state", false);

			//block actions
			event.preventDefault();
			event.stopimmediatepropagation();
		}
	};

	events.click.toggleMenu = function (event) {
		//show / hide menu
		$mainMenu.slideToggle();

		//block actions
		return event.preventDefault();
	};

	events.mouseenter.mainMenuItems = function (event) {
		//check viewport
		if (viewport > 1024) {
			//jquery vars
			var $this = $(this);

			//check for hidden items
			if (!$this.hasClass("removed")) {
				//open menu
				menuTimeouts.firstOpen = setTimeout(function () {
					//check hover
					if ($this.is(":hover")) {
						//kill last events
						clearTimeout(menuTimeouts.firstOpen);

						//hide last opened items
						$sectionMenuItems.removeClass("activeDrop").find(".drop").hide();
						$mainMenuItems.removeClass("activeDrop").find(".drop").hide();

						//show current drop menu
						$this.addClass("activeDrop").find(".drop").css("display", "table");

						//clear events
						return clearTimeout(menuTimeouts.main);
					}
				}, 300);
			}
		}
	};

	events.mouseleave.mainMenuItems = function (event) {
		//check viewport
		if (viewport > 1024) {
			//jquery vars
			var $this = $(this);

			//hide
			menuTimeouts.main = setTimeout(function () {
				if (!$this.is(":hover")) {
					$this.removeClass("activeDrop").find(".drop").hide();
				}
			}, 500);
		}
	};

	events.mouseenter.button = function (event) {
		//check viewport
		if (viewport > 1024) {
			//jquery vars
			var $this = $(this);

			//open menu
			menuTimeouts.firstOpen = setTimeout(function () {
				//check hover
				if ($sectionMenu.is(":hover")) {
					//kill last events
					clearTimeout(menuTimeouts.firstOpen);

					//hide last opened items
					$sectionMenuItems.removeClass("activeDrop").find(".drop").hide();
					$mainMenuItems.removeClass("activeDrop").find(".drop").hide();

					//show current drop menu
					$this.addClass("activeDrop").find(".drop").css("display", "table");

					//clear events
					return clearTimeout(menuTimeouts.section);
				}
			}, 300);
		}
	};

	events.mouseleave.button = function (event) {
		//check viewport
		if (viewport > 1024) {
			//jquery vars
			var $this = $(this);

			//hide menu
			menuTimeouts.section = setTimeout(function () {
				$this.removeClass("activeDrop").find(".drop").hide();
			}, 500);
		}
	};

	events.resize = function (event) {
		tools.getViewport();
		tools.sliceMainMenu();
		tools.clearAttributes();
	};

	//set start viewport
	tools.getViewport();

	//collapse
	tools.sliceMainMenu(false);

	//clear styles & attribures
	tools.clearAttributes();

	//binds
	$(document).on("click", "#catalogSlideButton", events.click.toggleMenu);
	$(document).on("click", "#mainMenu .allow-dropdown", events.click.openNested);
	$(document).on("click", "#mainMenu .allow-dropdown .back", events.click.closeNested);

	//catalog button menu
	$(document).on("mouseenter", "#menuCatalogSection", events.mouseenter.button);
	$(document).on("mouseleave", "#menuCatalogSection", events.mouseleave.button);

	//main menu
	$(document).on("mouseenter", "#mainMenu .eChild", events.mouseenter.mainMenuItems);
	$(document).on("mouseleave", "#mainMenu .eChild", events.mouseleave.mainMenuItems);

	//resize control
	$(window).on("resize", events.resize);
});
