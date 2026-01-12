$(function () {
	//modern mode
	"use strict";

	//on form submit
	var authFormSubmit = function (event) {
		//get form
		var $form = $(this);
		var $sendButton = $form.find(".sendForm");
		var $errorContainer = $form.find(".bx-auth-error-container");

		//get fields
		var $formFields = $form.find("input").removeClass("error");

		//error flag
		var emptyFields = false;

		//check filling
		$formFields.each(function (i, nextElement) {
			//get jquery object
			var $nextElement = $(nextElement);

			//check empty
			if ($nextElement.val() == "") {
				emptyFields = $nextElement.addClass("error");
			}
		});

		//send form
		if (!emptyFields) {
			//check ajax path
			if (typeof authAjaxPath != "undefined" && authAjaxPath != "") {
				//get form data
				var formData = new FormData($form[0]);

				//send data
				$.ajax({
					success: processing,
					processData: false,
					contentType: false,
					url: authAjaxPath,
					dataType: "json",
					data: formData,
					cache: false,
					type: "post",
					beforeSend: function (jqXHR, settings) {
						//add loader
						$sendButton.addClass("loading");

						//clear error container
						$errorContainer.empty();
					},
					complete: function (jqXHR, textStatus) {
						$sendButton.removeClass("loading");
					},
					error: function (jqXHR, textStatus, errorThrown) {
						console.error({
							httpResponse: jqXHR.responseText,
							status: jqXHR.statusText
						});
						console.error(jqXHR, textStatus, errorThrown);
					}
				});

				function processing(jsonData) {
					//check success auth
					if (
						typeof jsonData["AUTH_SUCCESS"] != "undefined" &&
						jsonData["AUTH_SUCCESS"] == "Y"
					) {
						//check redirect url
						if (
							typeof jsonData["REDIRECT_URL"] != "undefined" &&
							jsonData["REDIRECT_URL"] != ""
						) {
							window.location.href = jsonData["REDIRECT_URL"];
						}

						//reload page
						else {
							window.location.reload();
						}

						return true;
					}

					//captcha
					if (
						typeof jsonData["CAPTCHA_HTML"] != "undefined" &&
						jsonData["CAPTCHA_HTML"] != ""
					) {
						//get captcha container
						var $captchaContainer = $form.find(".bx-auth-captcha-container");

						//push captcha to page
						$captchaContainer.html(jsonData["CAPTCHA_HTML"]);
					}

					//auth by sms code
					if (
						typeof jsonData["CODE_SEND_SUCCESS"] != "undefined" &&
						jsonData["CODE_SEND_SUCCESS"] == "Y"
					) {
						$(".bx-auth-sms-container-first").addClass("hidden");
						$(".bx-auth-sms-container-next").removeClass("hidden");
					}

					//errors
					if (
						typeof jsonData["ERRORS"] != "undefined" &&
						!$.isEmptyObject(jsonData["ERRORS"])
					) {
						//push errors
						$.each(jsonData["ERRORS"], function () {
							$errorContainer.append(
								$("<span/>", { class: "bx-auth-error-item" }).html(this)
							);
						});

						return false;
					}
				}
			}

			//ajax path not found
			else {
				//set state
				$sendButton.removeClass("loading").addClass("error");
				console.error("ajax path not found");
			}
		}

		//error
		else {
			//set state
			$sendButton.removeClass("loading").addClass("error");
		}

		//block actions
		return event.preventDefault();
	};

	var selectTab = function (event) {
		//jquery vars
		var $this = $(this);
		var $auth = $this.parents(".bx-auth");

		//tabs select
		var $typeSelectorLinks = $auth.find(".bx-auth-type-select-link");
		var $typeCurrentSelector = $this.parents(".bx-auth-type-select-item");

		//tabs
		var $tabsParent = $auth.find(".bx-auth-type-items");
		var $tabs = $tabsParent.find(".bx-auth-type-item");

		//tab index
		var tabIndex = $typeCurrentSelector.index();

		//remove state class
		$tabs.removeClass("active");
		$typeSelectorLinks.addClass("btn-border");

		//set state class
		$tabs.eq(tabIndex).addClass("active");
		$this.removeClass("btn-border");

		//block actions
		return event.preventDefault();
	};

	//binds
	$(document).on("submit", ".bx-auth-form", authFormSubmit);
	$(document).on("click", ".bx-auth-type-select-link", selectTab);
});
