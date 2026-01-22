<?
	//set siteId
	if(!empty($_REQUEST["SITE_ID"])){
		define("SITE_ID", $_REQUEST["SITE_ID"]);
	}

	//globals
	global $USER, $APPLICATION;

	//increase productivity
	define("STOP_STATISTICS", true);
	define("NO_AGENT_CHECK", true);

	//load bitrix core
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/captcha.php");

	//load captcha function
	require(dirname(__FILE__)."/include/functions.php");

	//langs
	\Bitrix\Main\Localization\Loc::loadMessages(dirname(__FILE__)."/ajax.php");
	\Bitrix\Main\Localization\Loc::loadMessages(dirname(__FILE__)."/template.php");

	//load modules
	if(!\Bitrix\Main\Loader::includeModule("dw.deluxe")){
		die;
	}

	//vars
	$arReturn = array();
	$arErrors = array();
	$needCaptcha = false;
	$userId = false;

	//application
	$application = \Bitrix\Main\Application::getInstance();

	//context
	$context = $application->getContext();

	//get request
	$request = $context->getRequest();

	//get request vars
	$actionType = $request->getPost("ACTION_TYPE");
	$authType = $request->getPost("AUTH_TYPE");
	$siteId = $request->getPost("SITE_ID");

	//get transmitted data
	$arTransmitted = $request->getPostList()->toArray();

	//default values
	if(empty($arTransmitted["USER_REMEMBER"])){
		$arTransmitted["USER_REMEMBER"] = "N";
	}

	//check action type
	if($actionType == "AUTH"){

		//check user auth
		if(!$USER->IsAuthorized()){

			//check auth type (login / phone)
			if($authType == "LOGIN"){

				//check fields
				if(!empty($arTransmitted["USER_LOGIN"]) && !empty($arTransmitted["USER_PASSWORD"])){

					//by email
					if(filter_var($arTransmitted["USER_LOGIN"], FILTER_VALIDATE_EMAIL)){

						//get arAccounts
						$rsAccounts = \Bitrix\Main\UserTable::getList(array(
							"select" => array("ID", "EMAIL", "LOGIN"),
							"filter" => array(
								"ACTIVE" => "Y",
								array(
									"LOGIC" => "OR",
									array("EMAIL" => $arTransmitted["USER_LOGIN"]),
									array("LOGIN" => $arTransmitted["USER_LOGIN"])
								)
							),
						));

						//get user account
						while($arAccount = $rsAccounts->fetch()){

							//check login
							if(!empty($arAccount["LOGIN"])){

								//check user global object
								if(!is_object($USER)){
									$USER = new CUser;
								}

								//authorization attempt
								$arAuthResult = $USER->Login($arAccount["LOGIN"], $arTransmitted["USER_PASSWORD"], $arTransmitted["USER_REMEMBER"], "Y");

								//push to application
								$APPLICATION->arAuthResult = $arAuthResult;

								//check authorization result
								if($arAuthResult === true){

									//set status
									$arReturn["AUTH_SUCCESS"] = "Y";
									$arReturn["AUTH_BY_EMAIL"] = "Y";

									//push redirect url
									if(!empty($arTransmitted["BACKURL"])){
										$arReturn["REDIRECT_URL"] = $arTransmitted["BACKURL"];
									}

								}

								else{

									//check captcha
									if($APPLICATION->NeedCAPTHAForLogin($arTransmitted["USER_LOGIN"])){
										$arReturn["CAPTCHA_SID"] = $APPLICATION->CaptchaGetCode();
										$arReturn["CAPTCHA_HTML"] = getCaptcha($arReturn["CAPTCHA_SID"], GetMessage("AUTH_CAPTCHA_PROMT"));
									}

								}

							}

							//block next actions
							if($arReturn["AUTH_SUCCESS"] == "Y"){
								break(1);
							}

						}

						//push error (AUTH_BY_LOGIN_ERROR)
						if(empty($arReturn["AUTH_SUCCESS"]) || $arReturn["AUTH_SUCCESS"] != "Y"){
							$arErrors["AUTH_BY_LOGIN_ERROR"] = GetMessage("AUTH_BY_LOGIN_ERROR");
						}

					}

					//by login
					else{

						//check user global object
						if(!is_object($USER)){
							$USER = new CUser;
						}

						//authorization attempt
						$arAuthResult = $USER->Login($arTransmitted["USER_LOGIN"], $arTransmitted["USER_PASSWORD"], $arTransmitted["USER_REMEMBER"], "Y");

						//push to application
						$APPLICATION->arAuthResult = $arAuthResult;

						//check authorization result
						if($arAuthResult === true){

							//set status
							$arReturn["AUTH_SUCCESS"] = "Y";
							$arReturn["AUTH_BY_LOGIN"] = "Y";

							if(!empty($arTransmitted["BACKURL"])){
								$arReturn["REDIRECT_URL"] = $arTransmitted["BACKURL"];
							}

						}

						else{
							//check captcha
							if($APPLICATION->NeedCAPTHAForLogin($arTransmitted["USER_LOGIN"])){
								$arReturn["CAPTCHA_SID"] = $APPLICATION->CaptchaGetCode();
								$arReturn["CAPTCHA_HTML"] = getCaptcha($arReturn["CAPTCHA_SID"], GetMessage("AUTH_CAPTCHA_PROMT"));
							}
						}

						//push error (AUTH_BY_LOGIN_ERROR)
						if(empty($arReturn["AUTH_SUCCESS"]) || $arReturn["AUTH_SUCCESS"] != "Y"){
							$arErrors["AUTH_BY_LOGIN_ERROR"] = GetMessage("AUTH_BY_LOGIN_ERROR");
						}

					}

				}

				//push error (AUTH_EMPTY_FIELDS_ERROR)
				else{
					$arErrors["EMPTY_FIELDS_ERROR"] = GetMessage("AUTH_EMPTY_FIELDS_ERROR");
				}

			}

			//by phone
			elseif($authType == "PHONE"){

				//check filling
				if(!empty($arTransmitted["USER_PHONE"])){

					//check password
					if(empty($arTransmitted["AUTH_BY"]) || $arTransmitted["AUTH_BY"] == "PASSWORD"){
						if(empty($arTransmitted["USER_PASSWORD"])){
							//push error (AUTH_EMPTY_FIELDS_ERROR)
							$arErrors["EMPTY_FIELDS_ERROR"] = GetMessage("AUTH_EMPTY_FIELDS_ERROR");
						}
					}

					//check errors
					if(empty($arErrors)){

						//normalize
						$arTransmitted["USER_PHONE"] = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($arTransmitted["USER_PHONE"]);

						//get user by phone
						$rsAuthTable = \Bitrix\Main\UserPhoneAuthTable::getList(
							array(
								"filter" => array(
									"PHONE_NUMBER" => $arTransmitted["USER_PHONE"]
								),
								"select" => array(
									"USER_ID", "PHONE_NUMBER", "USER.ID", "USER.ACTIVE"
								)
							)
						);

						//get result
						$authTableResult = $rsAuthTable->fetchObject();

						//check result
						if(!empty($authTableResult)){
							$userId = $authTableResult->getUserId();
						}

						//check user id
						if(!empty($userId)){

							//check auth type
							if(empty($arTransmitted["AUTH_BY"]) || $arTransmitted["AUTH_BY"] == "PASSWORD"){

								//get arAccount by id
								$rsAccounts = \Bitrix\Main\UserTable::getList(array(
									"select" => array("ID", "EMAIL", "LOGIN"),
									"filter" => array("ID" => $userId, "ACTIVE" => "Y"),
								));

								//get user account
								if($arAccount = $rsAccounts->fetch()){

									//check login
									if(!empty($arAccount["LOGIN"])){

										//check user global object
										if(!is_object($USER)){
											$USER = new CUser;
										}

										//authorization attempt
										$arAuthResult = $USER->Login($arAccount["LOGIN"], $arTransmitted["USER_PASSWORD"], $arTransmitted["USER_REMEMBER"], "Y");

										//push to application
										$APPLICATION->arAuthResult = $arAuthResult;

										//check authorization result
										if($arAuthResult === true){

											//set status
											$arReturn["AUTH_SUCCESS"] = "Y";
											$arReturn["AUTH_BY_PHONE"] = "Y";

											//push redirect url
											if(!empty($arTransmitted["BACKURL"])){
												$arReturn["REDIRECT_URL"] = $arTransmitted["BACKURL"];
											}

										}

										else{
											//check captcha
											if($APPLICATION->NeedCAPTHAForLogin($arTransmitted["USER_LOGIN"])){
												$arReturn["CAPTCHA_SID"] = $APPLICATION->CaptchaGetCode();
												$arReturn["CAPTCHA_HTML"] = getCaptcha($arReturn["CAPTCHA_SID"], GetMessage("AUTH_CAPTCHA_PROMT"));
											}
										}

									}

								}

								//push error (AUTH_BY_LOGIN_ERROR)
								if(empty($arReturn["AUTH_SUCCESS"]) || $arReturn["AUTH_SUCCESS"] != "Y"){
									$arErrors["AUTH_BY_LOGIN_ERROR"] = GetMessage("AUTH_BY_LOGIN_ERROR");
								}

							}

							else{

								//push to auth table
								\Bitrix\Main\UserPhoneAuthTable::add(array(
								    "=USER_ID" => $userId,
								    "PHONE_NUMBER" => $arTransmitted["USER_PHONE"],
								));

								//set vars
								list($code, $phoneNumber) = \CUser::GeneratePhoneCode($userId);

								//event
								$sms = new \Bitrix\Main\Sms\Event(
								    "SMS_USER_CONFIRM_NUMBER",
								    array(
								        "USER_PHONE" => $phoneNumber,
								        "CODE" => $code,
								    )
								);

								//push
								$sms->send(true);

								//set status
								$arReturn["CODE_SEND_SUCCESS"] = "Y";
								$arReturn["AUTH_BY_SMS_CODE"] = "Y";

								//set cookie 1 hour
								setcookie("SMS_AUTH_USER_ID", $userId, time() + 3600, "/");
								setcookie("SMS_AUTH_USER_PHONE", $phoneNumber, time() + 3600, "/");

							}

						}

						//push errors
						else{

							//telephone + password
							if(empty($arTransmitted["AUTH_BY"]) || $arTransmitted["AUTH_BY"] == "PASSWORD"){
								$arErrors["AUTH_BY_PHONE_ERROR"] = GetMessage("AUTH_BY_PHONE_ERROR");
							}

							//only phone error
							else{
								$arErrors["AUTH_BY_PHONE_ONLY_ERROR"] = GetMessage("AUTH_BY_PHONE_ONLY_ERROR");
							}

						}

					}

				}

				//push error (AUTH_EMPTY_FIELDS_ERROR)
				else{
					$arErrors["EMPTY_FIELDS_ERROR"] = GetMessage("AUTH_EMPTY_FIELDS_ERROR");
				}

			}

			//push error (AUTH_TYPE_ERROR)
			else{
				$arErrors["AUTH_TYPE_ERROR"] = GetMessage("AUTH_TYPE_ERROR");
			}
		}

		//push error (AUTH_ALREADY_ERROR)
		else{
			$arErrors["AUTH_ALREADY_ERROR"] = GetMessage("AUTH_ALREADY_ERROR");
		}

	}

	elseif($actionType == "SMS_REQUEST"){

		//check user auth
		if(!$USER->IsAuthorized()){

			//check filling
			if(!empty($arTransmitted["USER_SMS_CODE"])){

				//check cookie
				if(!empty($_COOKIE["SMS_AUTH_USER_ID"]) && !empty($_COOKIE["SMS_AUTH_USER_PHONE"])){

					$userId = \CUser::VerifyPhoneCode($_COOKIE["SMS_AUTH_USER_PHONE"], $arTransmitted["USER_SMS_CODE"]);

					//check code
					if((bool) $userId){

						//authorize user
						$USER->Authorize((int)$userId);

						//set status
						$arReturn["AUTH_SUCCESS"] = "Y";
						$arReturn["AUTH_BY_SMS_CODE"] = "Y";

						//push redirect url
						if(!empty($arTransmitted["BACKURL"])){
							$arReturn["REDIRECT_URL"] = $arTransmitted["BACKURL"];
						}

						//clear cookies
						setcookie("SMS_AUTH_USER_ID", null, -1, "/");
						setcookie("SMS_AUTH_USER_PHONE", null, -1, "/");

					}

					//wrong sms code
					else{

						//push error (AUTH_SMS_CODE_ERROR)
						$arErrors["AUTH_SMS_CODE_ERROR"] = GetMessage("AUTH_SMS_CODE_ERROR");

					}

				}

				//push error (AUTH_SMS_CODE_COOKIE_ERROR)
				else{
					$arErrors["AUTH_SMS_CODE_COOKIE_ERROR"] = GetMessage("AUTH_SMS_CODE_COOKIE_ERROR");
				}

			}

			//push error (AUTH_SMS_CODE_EMPTY_ERROR)
			else{
				$arErrors["AUTH_SMS_CODE_EMPTY_ERROR"] = GetMessage("AUTH_SMS_CODE_EMPTY_ERROR");
			}

		}
		//push error (AUTH_ALREADY_ERROR)
		else{
			$arErrors["AUTH_ALREADY_ERROR"] = GetMessage("AUTH_ALREADY_ERROR");
		}

	}

	//push error (ACTION_TYPE_ERROR)
	else{
		$arErrors["ACTION_TYPE_ERROR"] = GetMessage("AUTH_ACTION_TYPE_ERROR");
	}

	//push errors
	if(!empty($arErrors)){
		$arReturn["ERRORS"] = $arErrors;
	}

	//print json
	echo \Bitrix\Main\Web\Json::encode($arReturn);

?>
