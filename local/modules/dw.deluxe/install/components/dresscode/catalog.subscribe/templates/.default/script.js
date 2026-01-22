$(function () {
	//modern mode
	"use strict";

	//functions
	var validateEmail = function (email) {
		//vars
		var regularEx =
			/[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;

		//check empty
		if (typeof email === "string" && email != "") {
			return regularEx.test(String(email).toLowerCase());
		}

		return false;
	};

	//ajax functions
	var sendData = function (url, data, type, dataType, success) {
		$.ajax({
			dataType: dataType,
			processData: false,
			contentType: false,
			cache: false,
			url: url,
			type: type,
			data: data,
			success: success,
			error: function (jqXHR, textStatus, errorThrown) {
				console.error({ httpResponse: jqXHR.responseText, status: jqXHR.statusText });
				console.error(jqXHR, textStatus, errorThrown);
			}
		});
	};

	var sendSubscribe = function (event) {
		//jquery vars
		var $this = $(this);
		var $subscribe = $this.parents(".catalogSubscribe");
		var $rotator = $subscribe.find(".catalogSubscribeRotatorBg");
		var $emailField = $subscribe.find(".catalogSubscribeEmail");
		var $personInfo = $subscribe.find('input[name="catalogSubscribePersonalInfo"]');
		var $subscribeForm = $subscribe.find(".catalogSubscribeForm");

		//other
		var email = $emailField.val();
		var consent = $personInfo.prop("checked");
		var ajaxPath = $subscribeForm.attr("action");
		var subscribeId = $subscribeForm.data("subscribe-id");
		var errors = false;

		//clear state
		$rotator.removeClass("error");
		$rotator.removeClass("success");
		$personInfo.removeClass("error");

		//check filling
		//email
		if (!validateEmail(email) || ajaxPath == "" || subscribeId == "") {
			$rotator.addClass("error");
			errors = true;
		}

		//consent
		if (consent == false) {
			$personInfo.addClass("error");
			errors = true;
		}

		//send ajax
		if (errors === false) {
			//create formData
			var sendObject = new FormData();

			//push request params
			sendObject.append("actionType", "subscribe");
			sendObject.append("subscribeId", subscribeId);
			sendObject.append("email", email);

			//push siteId from global
			if (typeof subscribeSiteId != "undefined") {
				sendObject.append("siteId", subscribeSiteId);
			}

			//send data
			sendData(ajaxPath, sendObject, "post", "json", dataProcessing, false);

			//proccesing data after request
			function dataProcessing(jsonData) {
				//check state
				if (jsonData["status"] === true && jsonData["success"] === true) {
					//display success window
					$(".catalogSubscribeSuccess").addClass("open");
					$rotator.addClass("success");
					// $subscribeForm[0].reset();
				}

				//check errors
				else {
					if (jsonData["error"] === true) {
						//vars
						var $errorWindow = $(".catalogSubscribeError");
						var $errorWindowText = $errorWindow.find(".catalogSubscribeErrorText");

						//check error messages
						if (
							typeof jsonData["errors"] == "object" &&
							!$.isEmptyObject(jsonData["errors"])
						) {
							$.each(jsonData["errors"], function () {
								$errorWindowText.html(this.toString());
							});
						}

						//display error window
						$errorWindow.addClass("open");
						$rotator.addClass("error");

						//set errors
						console.error(jsonData);
					}
				}
			}
		}

		//block action
		return event.preventDefault();
	};

	var closeSubscribeWindow = function (event) {
		//vars
		var $subscribeWindows = $(".catalogSubscribeSuccess, .catalogSubscribeError");

		//hide
		$subscribeWindows.removeClass("open");

		//block actions
		return event.preventDefault();
	};

	//binding
	$(document).on("click", ".catalogSubscribeSuccessCloseButton", closeSubscribeWindow);
	$(document).on("click", ".catalogSubscribeErrorCloseButton", closeSubscribeWindow);
	$(document).on("click", ".catalogSubscribeSend", sendSubscribe);
	$(document).on("submit", ".catalogSubscribeForm", sendSubscribe);
});
