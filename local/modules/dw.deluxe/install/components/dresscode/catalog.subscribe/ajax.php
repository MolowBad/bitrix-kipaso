<?
	//increase productivity
	define("STOP_STATISTICS", true);
	define("NO_AGENT_CHECK", true);

	//load bitrix core
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

	//langs
	\Bitrix\Main\Localization\Loc::loadMessages(dirname(__FILE__)."/ajax.php");

	//load modules
	if(!\Bitrix\Main\Loader::includeModule("subscribe")){
		die;
	}

	//globals
	global $USER;

	//vars
	$arReturn = array("status" => true, "errors" => false);

	//application
	$application = \Bitrix\Main\Application::getInstance();

	//context
	$context = $application->getContext();

	//get request
	$request = $context->getRequest();

	//get request vars
	$subscribeId = $request->getPost("subscribeId");
	$actionType = $request->getPost("actionType");
	$siteId = $request->getPost("siteId");
	$email = $request->getPost("email");

	//errors
	$arErrors = array();

	//get user id
	$userId = $USER->IsAuthorized() ? $USER->GetID() : false;

	//action type
	if(!empty($actionType) && $actionType == "subscribe"){

		//check transmitted
		if(!empty($subscribeId) && !empty($email) && !empty($siteId)){

			//subscribe
			$subscr = new CSubscription;
			$subscribeResult = $subscr->Add(array(
				"USER_ID" => $userId,
				"RUB_ID" => intval($subscribeId),
				"EMAIL" => $email,
				"ACTIVE" => "Y",
			), $siteId);

			//check result
			if(!empty($subscribeResult)){

				//subscribe authorize
				CSubscription::Authorize($subscribeResult);

				//push to result
				$arReturn["subscribeResult"] = $subscribeResult;
				$arReturn["success"] = true;

			}

			//error
			else{
				$arErrors["SUBSCRIBE_ERRORS"] = $subscr->LAST_ERROR;
			}

		}

		//error
		else{
			$arErrors["TRANSMITTED_DATA_ERROR"] = GetMessage("CATALOG_SUBSCRIBE_TRANSMITTED_DATA_ERROR");
		}

	}

	//error
	else{
		$arErrors["ACTION_TYPE_ERROR"] = GetMessage("CATALOG_SUBSCRIBE_ACTION_TYPE_ERROR");
	}

	//push errors
	if(!empty($arErrors)){
		$arReturn["error"] = true;
		$arReturn["errors"] = $arErrors;
	}

	//print json
	echo \Bitrix\Main\Web\Json::encode($arReturn);
?>