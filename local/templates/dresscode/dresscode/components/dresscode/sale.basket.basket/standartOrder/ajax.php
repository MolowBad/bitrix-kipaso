<?
	//set siteId from request
	if(!empty($_REQUEST["siteId"])){
		define("SITE_ID", $_REQUEST["siteId"]);
	}

	//increase productivity
	define("STOP_STATISTICS", true);
	define("NO_AGENT_CHECK", true);

	//load bitrix core
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

	//load modules
	if(!\Bitrix\Main\Loader::includeModule("dw.deluxe")){
		die;
	}

	//langs
	\Bitrix\Main\Localization\Loc::loadMessages(dirname(__FILE__)."/template.php");

	//include template functions
	require(dirname(__FILE__)."/template_functions.php");

	//load component ajax class
	CBitrixComponent::includeComponentClass("dresscode:sale.basket.basket");

	//check ajax request
	$basketAjax = CBasketComponentAjax::getInstance();
	$basketAjax->checkAjaxRequest();

	//check data after processing request
	$arDataStorage = $basketAjax->getFromDataStorage();

	//get extra services html
	if(!empty($arDataStorage["compilation"]["order"]["DELIVERIES"])){
		foreach($arDataStorage["compilation"]["order"]["DELIVERIES"] as &$nextDelivery){
			if(!empty($nextDelivery["EXTRA_SERVICES"])){
				$nextDelivery["EXTRA_SERVICES_HTML"] = getExtraServicesHTML($nextDelivery["EXTRA_SERVICES"], $arDataStorage["compilation"]["currency"]);
			}
		}
	}

	//push result
	if(!empty($arDataStorage)){
		\DigitalWeb\Basket::pushResult($arDataStorage);
	}
?>