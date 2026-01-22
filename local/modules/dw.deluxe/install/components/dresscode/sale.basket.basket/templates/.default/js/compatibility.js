//global vars
var compatibility = {};

//picpoint module compatibility
if (typeof submitForm == "undefined" && typeof DwBasket != "undefined") {
	//send picpoint form fields
	var submitForm = function () {
		compatibility.ajax.sendForm();
	};
}

//error
else {
	console.error("DwBasket undefined");
}

//jquery
$(function () {
	//modern mode
	"use strict";

	//picpoint
	compatibility.ajax = {
		//send picpoint fields for save to session
		sendForm: function () {
			//check ajax path
			if (typeof ajaxDir != "undefined" && ajaxDir != "") {
				//get class names
				var classNameCluster = DwBasket.tools.getNamesFromCluster();

				//jquery vars
				var $basket = $("." + classNameCluster.basket);
				var $orderForm = $("." + classNameCluster.orderFormActive);

				//create form data
				var sendObject = new FormData();

				//other
				var formData = {};

				//append action type
				sendObject.append("actionType", "compDeliveries");

				//get form data
				var formArray = $orderForm.serializeArray();

				//reindex
				$.map(formArray, function (next, index) {
					formData[next["name"]] = next["value"];
				});

				//pack to order index
				sendObject = DwBasket.push.formData(sendObject, formData, "order");

				//other
				var request = {
					url: ajaxDir + "/ajax.php"
				};

				//start loader
				DwBasket.tools.launchLoader($basket, "wait");

				//send data
				DwBasket.ajax.sendData(
					ajaxDir + "/ajax.php",
					sendObject,
					"post",
					"json",
					dataProcessing,
					false,
					false
				);

				//proccesing data after request
				function dataProcessing(jsonData) {
					//check success
					if (typeof jsonData["success"] != "undefined" && jsonData["success"] === true) {
						//remove loader
						DwBasket.tools.stopLoader($basket, "wait");
					}

					//check errors
					else {
						if (jsonData["error"] === true) {
							console.error(jsonData);
						}
					}
				}
			}

			return false;
		}
	};

	//sdek module compatibility
	if (typeof IPOLSDEK_pvz != "undefined" && IPOLSDEK_pvz.onLoad != "undefined") {
		//on event
		$(document).on("DwBasket.processing", function (event, data = {}) {
			//vars
			var sdekData = {
				order: {}
			};

			//modify data
			if (
				typeof data.compilation != "undefined" &&
				typeof data.compilation.order != "undefined"
			) {
				//deliveries
				if (typeof data.compilation.order.DELIVERIES != "undefined") {
					sdekData.order.DELIVERY = data.compilation.order.DELIVERIES;
				}

				//deliveries
				if (typeof data.sdek != "undefined") {
					sdekData.sdek = data.sdek;
				}
			}

			//sdek onload event
			IPOLSDEK_pvz.onLoad(sdekData);
		});
	}

	//delivery codes for ajax reload after load page
	var deliveriesReload = {
		0: "sdek:pickup"
	};

	//get class names
	var classNameCluster = DwBasket.tools.getNamesFromCluster();

	//get delivery select
	var $deliveryField = $(
		"." + classNameCluster.propActive + " ." + classNameCluster.delivery
	).find("option:selected");

	//check empty
	if (!$.isEmptyObject($deliveryField)) {
		var currentDeliveryCode = $deliveryField.data("code");
	}

	//check deliveries for reload
	$.each(deliveriesReload, function (index, nextCode) {
		if (currentDeliveryCode == nextCode) {
			return DwBasket.commit.basket.flush();
		}
	});
});

//bitrix sale order ajax send request compatibility
if (typeof BX.Sale == "undefined") {
	//contruct
	BX.Sale = {
		OrderAjaxComponent: {
			//functions
			switchOrderSaveButtons: function () {},

			sendRequest: function () {},

			//vars
			result: {
				ERROR: {}
			}
		}
	};
}
