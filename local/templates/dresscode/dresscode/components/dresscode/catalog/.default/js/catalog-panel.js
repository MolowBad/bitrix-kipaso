$(function () {
	//vars
	var catalog = {};

	//get container
	catalog.container = $("#ajaxSection");

	//functions
	catalog.addLoader = function () {
		catalog.container.addClass("processing");
	};

	catalog.clearLoader = function () {
		catalog.container.removeClass("processing");
	};

	catalog.onChangePanel = function (event) {
		//vars
		var $this = $(this);

		//reload
		catalog.ajaxInsert($this.find(".selected").data("direction"));

		//block actions
		return event.preventDefault();
	};

	catalog.onClickPanel = function (event) {
		//vars
		var $this = $(this);

		//reload
		if (!$this.hasClass("selected")) {
			//clear selected
			$(".panel-click").removeClass("selected");

			//set selected
			$this.addClass("selected");

			//reload catalog section
			catalog.ajaxInsert($this.data("direction"));
		}

		//block actions
		return event.preventDefault();
	};

	catalog.ajaxInsert = function (direction) {
		//check ajax path
		if (typeof catalogAjaxPath != "undefined" && catalogAjaxPath != "") {
			//form data
			var sendObject = new FormData();

			//push request params
			sendObject.append("actionType", "getSection");

			//push arParams
			sendObject = catalog.pushFormData(sendObject, catalogSectionParams, "component");

			//push additonal
			sendObject = catalog.pushFormData(sendObject, catalogAdditonal, "additonal");

			//append direction
			sendObject = catalog.pushFormData(sendObject, direction, "direction");

			//append siteId
			sendObject = catalog.pushFormData(sendObject, catalogSiteId, "siteId");

			//append url path
			sendObject = catalog.pushFormData(sendObject, window.location.pathname, "urlPath");

			//send request
			$.ajax({
				beforeSend: catalog.addLoader,
				complete: catalog.clearLoader,
				success: dataProcessing,
				url: catalogAjaxPath,
				processData: false,
				contentType: false,
				data: sendObject,
				dataType: "json",
				cache: false,
				type: "post",
				error: function (jqXHR, textStatus, errorThrown) {
					console.error({ httpResponse: jqXHR.responseText, status: jqXHR.statusText });
					console.error(jqXHR, textStatus, errorThrown);
				}
			});

			//proccesing data after request
			function dataProcessing(jsonData) {
				//check empty
				if (
					typeof jsonData["COMPONENT_HTML"] != "undefined" &&
					jsonData["COMPONENT_HTML"] != ""
				) {
					catalogSectionParams = {};

					//push html
					catalog.container.html(jsonData["COMPONENT_HTML"]);
				}

				//error
				else {
					console.error("component html is empty");
				}
			}

			//update lazy
			checkLazyItems();
		}

		//error
		else {
			console.error("catalogAjaxPath is not defined");
		}
	};

	catalog.pushFormData = function (sendObject, values, name) {
		//sendObject(formData)
		//multi
		if (typeof values == "object") {
			//each values
			for (var index in values) {
				//recursion
				if (typeof values[index] == "object") {
					catalog.pushFormData(sendObject, values[index], name + "[" + index + "]");
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

	//binds
	$(document).on("change", ".panel-change", catalog.onChangePanel);
	$(document).on("click", ".panel-click", catalog.onClickPanel);
});
