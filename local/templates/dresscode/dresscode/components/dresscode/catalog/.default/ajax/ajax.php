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

	//application
	$application = \Bitrix\Main\Application::getInstance();

	//context
	$context = $application->getContext();

	//get request
	$request = $context->getRequest();

	//get request vars
	$actionType = $request->getPost("actionType");

	//get transmitted data
	$arTransmitted = $request->getPostList()->toArray();

	//check encoding
	if(!defined("BX_UTF")){
		$arTransmitted = \DigitalWeb\Tools::convertEncoding($arTransmitted);
	}

	//check action type
	if($actionType == "getSection"){

		//check
		if(!empty($arTransmitted)){

			//vars
			$arReturn = array();

            //set filter
            if(!empty($arTransmitted["component"]["arParams"]["FILTER_NAME"]) && !empty($arTransmitted["component"]["filter"])){
                ${$arTransmitted["component"]["arParams"]["FILTER_NAME"]} = $arTransmitted["component"]["filter"];
            }

			//sort
			if(!empty($arTransmitted["direction"]["SORT_FIELD"]) && !empty($arTransmitted["additonal"]["SORT"][$arTransmitted["direction"]["SORT_FIELD"]["ID"]])){
				setcookie("CATALOG_SORT_FIELD", $arTransmitted["direction"]["SORT_FIELD"]["ID"], (time() + 60 * 60 * 24 * 30 * 12 * 2), "/");
				$arTransmitted["component"]["arParams"]["ELEMENT_SORT_FIELD"] = $arTransmitted["additonal"]["SORT"][$arTransmitted["direction"]["SORT_FIELD"]["ID"]]["CODE"];
				$arTransmitted["component"]["arParams"]["ELEMENT_SORT_ORDER"] = $arTransmitted["additonal"]["SORT"][$arTransmitted["direction"]["SORT_FIELD"]["ID"]]["ORDER"];
			}

			elseif(!empty($_COOKIE["CATALOG_SORT_FIELD"]) && !empty($arTransmitted["additonal"]["SORT"][$_COOKIE["CATALOG_SORT_FIELD"]])){ // COOKIE
				$arTransmitted["component"]["arParams"]["ELEMENT_SORT_FIELD"] = $arTransmitted["additonal"]["SORT"][$_COOKIE["CATALOG_SORT_FIELD"]]["CODE"];
				$arTransmitted["component"]["arParams"]["ELEMENT_SORT_ORDER"] = $arTransmitted["additonal"]["SORT"][$_COOKIE["CATALOG_SORT_FIELD"]]["ORDER"];
			}

			//sort to
			if(!empty($arTransmitted["direction"]["SORT_TO"]) && $arTransmitted["additonal"]["SORT_NUMBER"][$arTransmitted["direction"]["SORT_TO"]]){
				setcookie("CATALOG_SORT_TO", $arTransmitted["direction"]["SORT_TO"], (time() + 60 * 60 * 24 * 30 * 12 * 2), "/");
				$arTransmitted["component"]["arParams"]["PAGE_ELEMENT_COUNT"] = $arTransmitted["direction"]["SORT_TO"];
			}

			elseif(!empty($_COOKIE["CATALOG_SORT_TO"]) && $arTransmitted["additonal"]["SORT_NUMBER"][$_COOKIE["CATALOG_SORT_TO"]]){
				$arTransmitted["component"]["arParams"]["PAGE_ELEMENT_COUNT"] = $_COOKIE["CATALOG_SORT_TO"];
			}

			//view
			if(!empty($arTransmitted["direction"]["VIEW"]) && $arTransmitted["additonal"]["TEMPLATES"][$arTransmitted["direction"]["VIEW"]]){
				setcookie("CATALOG_VIEW", $arTransmitted["direction"]["VIEW"], (time() + 60 * 60 * 24 * 30 * 12 * 2), "/");
				$arTransmitted["component"]["arParams"]["CATALOG_TEMPLATE"] = $arTransmitted["direction"]["VIEW"];
			}

			elseif(!empty($_COOKIE["CATALOG_VIEW"]) && $arTransmitted["additonal"]["TEMPLATES"][$_COOKIE["CATALOG_VIEW"]]){
				$arTransmitted["component"]["arParams"]["CATALOG_TEMPLATE"] = $_COOKIE["CATALOG_VIEW"];
			}

			//set base link
			if(!empty($arTransmitted["urlPath"])){
				$arTransmitted["component"]["arParams"]["PAGER_BASE_LINK"] = $arTransmitted["urlPath"];
			}

			//push component
			$arReturn["COMPONENT_HTML"] = \DigitalWeb\Tools::getComponentHTML(
				$arTransmitted["component"]["name"],
				!empty($arTransmitted["component"]["arParams"]["CATALOG_TEMPLATE"]) ? strtolower($arTransmitted["component"]["arParams"]["CATALOG_TEMPLATE"]) : $arTransmitted["component"]["template"],
				$arTransmitted["component"]["arParams"]
			);

			//print json
			echo \Bitrix\Main\Web\Json::encode($arReturn);

		}

	}

?>