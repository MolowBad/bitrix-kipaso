<?php

//disable agents
define("STOP_STATISTICS", true);
define("NO_AGENT_CHECK", true);

//load bitrix core
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

//load modules
foreach(array("sale", "dw.deluxe") as $moduleName){
	if(!\Bitrix\Main\Loader::includeModule($moduleName)){
		return false;
	}
}

//application
$application = \Bitrix\Main\Application::getInstance();

//context
$context = $application->getContext();

//get request
$request = $context->getRequest();

//get transmitted data
$arTransmitted = $request->getPostList()->toArray();

//check action
if(!empty($arTransmitted["actionType"])){

	//allocation
	if($arTransmitted["actionType"] === "addProduct"){

		//check product id
		if(!empty($arTransmitted["productId"])){

				//get product id
				$productId = intval($arTransmitted["productId"]);
				$quantity = \DigitalWeb\Basket::getMeasureRatio($productId);

				//addProduct
				$basketResult = \Bitrix\Catalog\Product\Basket::addProduct(array(
				    "PRODUCT_ID" => $productId,
				    "QUANTITY" => $quantity,
				    "PROPS" => array(),
				));

				//check result
				if(!$basketResult->isSuccess()){
					$errors[$productId] = $basketResult->getErrorMessages();
				}

				//check errors
				if(!empty($errors)){

					//print json
					echo \Bitrix\Main\Web\Json::encode(array(
						"errors" => $errors,
						"success" => false
					));

				}

				//success
				else{

					//print json
					echo \Bitrix\Main\Web\Json::encode(array(
						"success" => true
					));

				}

		}

		//error
		else{
			echo \Bitrix\Main\Web\Json::encode(
				array("success" => false, "errors" => array("product id is empty"))
			);
		}

	}

	elseif($arTransmitted["actionType"] == "getDeliveryComponent"){

		//check product id
		if(!empty($arTransmitted["productId"]) && !empty($arTransmitted["productQuantity"])){

			//set def mode
			$arTransmitted["defferedMode"] = !empty($arTransmitted["defferedMode"]) ? $arTransmitted["defferedMode"] : "N";

			//push component result
			$APPLICATION->IncludeComponent(
				"dresscode:fast.calculate.delivery",
				"catalog-no-modal",
				array(
					"GROUP_BUTTONS_LABELS" => $arTransmitted["serviceButtons"],
					"SHOW_DELIVERY_IMAGES" => $arTransmitted["serviceImages"],
					"PRODUCT_AVAILABLE" => $arTransmitted["productAvailable"],
					"PRODUCT_QUANTITY" => $arTransmitted["productQuantity"],
					"DEFERRED_MODE" => $arTransmitted["defferedMode"],
					"PRODUCT_ID" => $arTransmitted["productId"],
					"CONVERT_ENCODING" => "Y",
					"LOAD_SCRIPT" => "N"
				),
				false
			);

		}

	}

}

?>